<?php

namespace App\Actions;

use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\Enums\ProcessStatus;
use App\Exceptions\ApiException;
use App\Jobs\GenerateVariantJob;
use App\Models\AiRequestLog;
use App\Models\Generation;
use App\Models\GenerationImage;
use App\Support\EditTools;
use App\Models\LightingPreset;
use App\Models\Project;
use App\Models\QualityLevel;
use App\Models\SceneType;
use App\Models\Setting;
use App\Models\StudioStyle;
use App\Models\Template;
use App\Models\User;
use App\Services\Ai\PromptBuilder;
use App\Services\Credits\CreditService;
use Illuminate\Support\Facades\DB;

class StartGeneration
{
    public function __construct(
        private readonly CreditService $credits,
        private readonly PromptBuilder $prompts,
    ) {}

    /**
     * @param  array{user_prompt?: ?string, studio_style_id?: ?int, lighting_preset_id?: ?int, quality_level_id: int, scene_type_id?: ?int, shadow_enabled?: ?bool, aspect_ratio?: ?string, variant_count: int, template_id?: ?int}  $data
     */
    public function __invoke(User $user, Project $project, array $data): Generation
    {
        $this->ensureBudget();

        $style = isset($data['studio_style_id']) ? StudioStyle::find($data['studio_style_id']) : null;
        $lighting = isset($data['lighting_preset_id']) ? LightingPreset::find($data['lighting_preset_id']) : null;
        $scene = isset($data['scene_type_id']) ? SceneType::find($data['scene_type_id']) : $project->sceneType;
        $quality = QualityLevel::findOrFail($data['quality_level_id']);
        $template = isset($data['template_id']) ? Template::find($data['template_id']) : $project->template;
        $variants = (int) $data['variant_count'];
        $shadow = (bool) ($data['shadow_enabled'] ?? $project->shadow_enabled);
        $ratio = $data['aspect_ratio'] ?? '4:5';

        $this->ensureAllowed($user, $variants, [$style, $lighting, $scene, $quality, $template]);

        $credits = $quality->creditsFor($variants);
        $finalPrompt = $this->prompts->build($scene, $style, $lighting, $quality, $shadow, $data['user_prompt'] ?? null, $ratio);

        $generation = DB::transaction(function () use ($user, $project, $data, $style, $lighting, $scene, $quality, $template, $variants, $shadow, $ratio, $credits, $finalPrompt) {
            $generation = Generation::create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'user_prompt' => $data['user_prompt'] ?? null,
                'final_prompt' => $finalPrompt,
                'studio_style_id' => $style?->id,
                'scene_type_id' => $scene?->id,
                'lighting_preset_id' => $lighting?->id,
                'quality_level_id' => $quality->id,
                'template_id' => $template?->id,
                'shadow_enabled' => $shadow,
                'aspect_ratio' => $ratio,
                'variant_count' => $variants,
                'model_id' => $quality->model_id ?: (string) Setting::get('ai.image_model'),
                'status' => GenerationStatus::Queued,
                'credits_charged' => $credits,
            ]);

            $this->credits->debit($user, $credits, CreditTransactionType::Generation, $generation, 'Stüdyo çekimi (#'.$generation->id.', '.$variants.' varyant)');

            for ($i = 1; $i <= $variants; $i++) {
                $generation->images()->create(['variant_index' => $i, 'status' => ProcessStatus::Pending]);
            }

            if ($template) {
                $template->increment('uses_count');
            }

            return $generation;
        });

        $this->dispatchPending($generation);

        return $generation;
    }

    /** "+4 Üret": aynı ayarlarla mevcut üretime yeni varyantlar ekler. */
    public function more(User $user, Generation $generation, int $count): Generation
    {
        $this->ensureBudget();

        $quality = $generation->qualityLevel ?? QualityLevel::firstOrFail();
        $total = $generation->variant_count + $count;
        $this->ensureAllowed($user, min($total, 8), [$generation->studioStyle, $generation->lightingPreset, $generation->sceneType, $quality]);

        if ($total > 16) {
            throw new ApiException('Bir çekimde en fazla 16 varyant üretilebilir.', 'VARIANT_LIMIT', 422);
        }

        $credits = $quality->creditsFor($count);

        DB::transaction(function () use ($user, $generation, $count, $credits) {
            $this->credits->debit($user, $credits, CreditTransactionType::Generation, $generation, 'Ek varyant (#'.$generation->id.', +'.$count.')');

            $start = (int) $generation->images()->max('variant_index');
            for ($i = 1; $i <= $count; $i++) {
                $generation->images()->create(['variant_index' => $start + $i, 'status' => ProcessStatus::Pending]);
            }

            $generation->update([
                'variant_count' => $generation->variant_count + $count,
                'credits_charged' => $generation->credits_charged + $credits,
                'status' => GenerationStatus::Processing,
                'completed_at' => null,
            ]);
        });

        $this->dispatchPending($generation);

        return $generation->refresh();
    }

    /**
     * Sonuç ekranı aracı: seçili varyantı girdi alıp düzenlenmiş yeni bir varyant üretir (1 varyant kredisi).
     */
    public function edit(User $user, GenerationImage $source, string $tool, string $option, ?string $note = null): Generation
    {
        $this->ensureBudget();

        if ($source->status !== ProcessStatus::Done || ! $source->path) {
            throw new ApiException('Bu varyant henüz hazır değil.', 'IMAGE_NOT_READY', 422);
        }

        $generation = $source->generation;
        if ($generation->variant_count >= 24) {
            throw new ApiException('Bu çekimde düzenleme sınırına ulaşıldı.', 'VARIANT_LIMIT', 422);
        }

        $quality = $generation->qualityLevel ?? QualityLevel::firstOrFail();
        $credits = $quality->creditsFor(1);

        DB::transaction(function () use ($user, $generation, $source, $tool, $option, $note, $credits) {
            $this->credits->debit($user, $credits, CreditTransactionType::Generation, $generation,
                EditTools::label($tool, $option).' (#'.$generation->id.' V'.$source->variant_index.')');

            $generation->images()->create([
                'variant_index' => (int) $generation->images()->max('variant_index') + 1,
                'status' => ProcessStatus::Pending,
                'source_image_id' => $source->id,
                'edit_tool' => $tool,
                'edit_option' => $option,
                'edit_prompt' => EditTools::instruction($tool, $option, $note),
                'aspect_ratio' => $tool === 'ratio' ? $option : ($source->aspect_ratio ?? $generation->aspect_ratio),
            ]);

            $generation->update([
                'variant_count' => $generation->variant_count + 1,
                'credits_charged' => $generation->credits_charged + $credits,
                'status' => GenerationStatus::Processing,
                'completed_at' => null,
            ]);
        });

        $this->dispatchPending($generation);

        return $generation->refresh();
    }

    private function dispatchPending(Generation $generation): void
    {
        $generation->images()->where('status', ProcessStatus::Pending)->get()
            ->each(fn ($image) => GenerateVariantJob::dispatch($image)->afterCommit());
    }

    private function ensureAllowed(User $user, int $variants, array $items): void
    {
        if ($user->isPro()) {
            return;
        }

        if ($variants > (int) Setting::get('limits.max_variants_free')) {
            throw ApiException::proRequired();
        }

        foreach ($items as $item) {
            if ($item && ($item->is_pro ?? false)) {
                throw ApiException::proRequired();
            }
        }
    }

    private function ensureBudget(): void
    {
        $budget = (float) Setting::get('ai.daily_budget_usd');
        if ($budget > 0 && AiRequestLog::whereDate('created_at', today())->sum('cost_usd') >= $budget) {
            throw ApiException::aiBudgetExceeded();
        }
    }
}
