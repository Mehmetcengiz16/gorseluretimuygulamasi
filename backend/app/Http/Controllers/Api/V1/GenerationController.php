<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\StartGeneration;
use App\Enums\ProcessStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\GenerationImageResource;
use App\Http\Resources\GenerationResource;
use App\Models\Generation;
use App\Models\GenerationImage;
use App\Models\LightingPreset;
use App\Models\Project;
use App\Models\QualityLevel;
use App\Models\Setting;
use App\Models\StudioStyle;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiRequestFailed;
use App\Services\Ai\PromptBuilder;
use App\Support\EditTools;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerationController extends Controller
{
    public function store(Request $request, Project $project, StartGeneration $start): JsonResponse
    {
        abort_unless($project->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'user_prompt' => ['nullable', 'string', 'max:500'],
            'studio_style_id' => ['nullable', 'integer', 'exists:studio_styles,id'],
            'lighting_preset_id' => ['nullable', 'integer', 'exists:lighting_presets,id'],
            'quality_level_id' => ['required', 'integer', 'exists:quality_levels,id'],
            'scene_type_id' => ['nullable', 'integer', 'exists:scene_types,id'],
            'shadow_enabled' => ['nullable', 'boolean'],
            'aspect_ratio' => ['nullable', Rule::in(['1:1', '4:5', '3:4', '9:16', '16:9'])],
            'variant_count' => ['required', 'integer', Rule::in([1, 2, 4, 8])],
            'template_id' => ['nullable', 'integer', 'exists:templates,id'],
        ]);

        $generation = $start($request->user(), $project, $data);

        return $this->respond($generation, 202, $request->user()->fresh()->credit_balance);
    }

    public function show(Request $request, Generation $generation): JsonResponse
    {
        $this->authorizeOwner($request, $generation);

        return $this->respond($generation);
    }

    public function more(Request $request, Generation $generation, StartGeneration $start): JsonResponse
    {
        $this->authorizeOwner($request, $generation);
        $data = $request->validate(['count' => ['required', 'integer', Rule::in([1, 2, 4])]]);

        $generation = $start->more($request->user(), $generation, $data['count']);

        return $this->respond($generation, 202, $request->user()->fresh()->credit_balance);
    }

    public function estimate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'variant_count' => ['required', 'integer', 'min:1', 'max:8'],
            'quality_level_id' => ['required', 'integer', 'exists:quality_levels,id'],
        ]);

        $credits = QualityLevel::findOrFail($data['quality_level_id'])->creditsFor($data['variant_count']);
        $balance = $request->user()->credit_balance;

        return response()->json(['data' => ['credits' => $credits, 'balance' => $balance, 'enough' => $balance >= $credits]]);
    }

    public function enhancePrompt(Request $request, AiClient $ai, PromptBuilder $prompts): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['nullable', 'string', 'max:500'],
            'studio_style_id' => ['nullable', 'integer', 'exists:studio_styles,id'],
            'lighting_preset_id' => ['nullable', 'integer', 'exists:lighting_presets,id'],
        ]);

        $style = isset($data['studio_style_id']) ? StudioStyle::find($data['studio_style_id']) : null;
        $lighting = isset($data['lighting_preset_id']) ? LightingPreset::find($data['lighting_preset_id']) : null;

        $input = trim(implode("\n", array_filter([
            'Kullanıcı açıklaması: '.($data['prompt'] ?: '(boş — ürün için etkileyici bir stüdyo sahnesi öner)'),
            $style ? 'Seçilen stil: '.$style->name : null,
            $lighting ? 'Seçilen ışık: '.$lighting->name.' ('.$lighting->subtitle.')' : null,
        ])));

        try {
            $text = $ai->generateText((string) Setting::get('ai.text_model'), $prompts->enhanceSystemPrompt(), $input, ['user_id' => $request->user()->id]);
        } catch (AiRequestFailed $e) {
            return response()->json(['message' => 'Prompt şu anda geliştirilemedi, lütfen tekrar dene.', 'code' => 'AI_UNAVAILABLE', 'errors' => (object) []], 503);
        }

        return response()->json(['data' => ['prompt' => mb_substr(trim($text, " \t\n\r\"'"), 0, 500)]]);
    }

    public function updateImage(Request $request, GenerationImage $image): GenerationImageResource
    {
        $this->authorizeOwner($request, $image->generation);

        $data = $request->validate([
            'is_favorite' => ['sometimes', 'boolean'],
            'is_master' => ['sometimes', 'accepted'],
        ]);

        if (! empty($data['is_master'])) {
            abort_unless($image->status === ProcessStatus::Done, 422, 'Görsel henüz hazır değil.');
            $image->generation->images()->where('id', '!=', $image->id)->update(['is_master' => false]);
            $image->is_master = true;
        }
        if (array_key_exists('is_favorite', $data)) {
            $image->is_favorite = $data['is_favorite'];
        }
        $image->save();

        return new GenerationImageResource($image);
    }

    /** Sonuç ekranı araçları: rötuş, ışık yönü, en/boy oranı, renk sıcaklığı, 4K yükseltme. */
    public function editImage(Request $request, GenerationImage $image, StartGeneration $start): JsonResponse
    {
        $this->authorizeOwner($request, $image->generation);

        $data = $request->validate([
            'tool' => ['required', Rule::in(EditTools::tools())],
            'option' => ['required', 'string', Rule::in(EditTools::options((string) $request->input('tool')))],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $generation = $start->edit($request->user(), $image, $data['tool'], $data['option'], $data['note'] ?? null);

        return $this->respond($generation, 202, $request->user()->fresh()->credit_balance);
    }

    public function download(Request $request, GenerationImage $image): StreamedResponse
    {
        $this->authorizeOwner($request, $image->generation);
        abort_unless($image->path && Storage::disk('public')->exists($image->path), 404);

        $name = 'studioai-'.$image->generation_id.'-v'.$image->variant_index.'.png';

        return Storage::disk('public')->download($image->path, $name);
    }

    private function respond(Generation $generation, int $status = 200, ?int $balance = null): JsonResponse
    {
        $generation->load(['images', 'project', 'qualityLevel']);

        return (new GenerationResource($generation))
            ->additional($balance !== null ? ['meta' => ['credit_balance' => $balance]] : [])
            ->response()
            ->setStatusCode($status);
    }

    private function authorizeOwner(Request $request, Generation $generation): void
    {
        abort_unless($generation->user_id === $request->user()->id, 404);
    }
}
