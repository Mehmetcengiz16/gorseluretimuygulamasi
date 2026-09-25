<?php

namespace App\Jobs;

use App\Actions\FinalizeGeneration;
use App\Enums\GenerationStatus;
use App\Enums\ProcessStatus;
use App\Models\GenerationImage;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiRequestFailed;
use App\Services\Ai\PromptBuilder;
use App\Services\ImageStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateVariantJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public GenerationImage $image)
    {
        $this->onQueue('ai');
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(AiClient $ai, ImageStorage $storage, PromptBuilder $prompts, FinalizeGeneration $finalize): void
    {
        $image = $this->image->fresh(['generation.project', 'generation.qualityLevel', 'source']);
        if (! $image || $image->status === ProcessStatus::Done) {
            return;
        }

        $generation = $image->generation;
        $project = $generation->project;

        if ($generation->status === GenerationStatus::Queued) {
            $generation->update(['status' => GenerationStatus::Processing, 'started_at' => now()]);
        }
        $image->update(['status' => ProcessStatus::Processing]);

        $isEdit = $image->source_image_id !== null;
        $source = $isEdit ? $image->source : null;
        if ($isEdit && ! $source?->path) {
            $this->markFailed($image, 'Düzenlenecek kaynak görsel bulunamadı.', $finalize);

            return;
        }

        // Düzenlemede kaynak varyant; yeni çekimde temiz dekupe (yoksa orijinal) girdi olur.
        $inputPath = $isEdit ? $source->path : ($project->cutout_path ?: $project->original_path);

        try {
            $result = $ai->generateImage(
                model: $generation->model_id,
                systemPrompt: $isEdit ? $prompts->editSystemPrompt() : $prompts->systemPrompt(),
                userPrompt: $isEdit ? $image->edit_prompt : $prompts->forVariant($generation->final_prompt, $image->variant_index),
                inputImageBinary: $storage->read($inputPath),
                inputMime: $storage->mime($inputPath),
                meta: [
                    'purpose' => $isEdit ? 'edit' : 'generate',
                    'user_id' => $generation->user_id,
                    'generation_image_id' => $image->id,
                    'aspect_ratio' => $image->aspect_ratio ?? $generation->aspect_ratio,
                    'image_size' => $image->edit_tool === 'upscale' ? '4K' : $generation->qualityLevel?->image_size,
                    'edit_tool' => $image->edit_tool,
                    'edit_option' => $image->edit_option,
                ],
            );
        } catch (AiRequestFailed $e) {
            if (! $e->retryable || $this->attempts() >= $this->tries) {
                $this->markFailed($image, $e->getMessage(), $finalize);
                $this->delete();

                return;
            }
            $image->update(['status' => ProcessStatus::Pending, 'error_message' => $e->getMessage()]);
            throw $e;
        }

        $stored = $storage->storeResult($result->binary, 'generations/'.$generation->id, 'v'.$image->variant_index.'-'.now()->timestamp);

        $image->update($stored + [
            'status' => ProcessStatus::Done,
            'openrouter_generation_id' => $result->generationId,
            'cost_usd' => $result->costUsd,
            'error_message' => null,
        ]);

        $finalize($generation);
    }

    public function failed(?Throwable $e): void
    {
        $this->markFailed($this->image, $e?->getMessage() ?? 'Bilinmeyen hata', app(FinalizeGeneration::class));
    }

    private function markFailed(GenerationImage $image, string $error, FinalizeGeneration $finalize): void
    {
        $image->update(['status' => ProcessStatus::Failed, 'error_message' => mb_substr($error, 0, 1000)]);
        $finalize($image->generation);
    }
}
