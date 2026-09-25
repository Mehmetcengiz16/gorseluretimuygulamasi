<?php

namespace App\Http\Resources;

use App\Enums\ProcessStatus;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Generation */
class GenerationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = $this->whenLoaded('images');

        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'status' => $this->status->value,
            'variant_count' => $this->variant_count,
            'ready_count' => $this->relationLoaded('images') ? $this->images->where('status', ProcessStatus::Done)->count() : null,
            'user_prompt' => $this->user_prompt,
            'aspect_ratio' => $this->aspect_ratio,
            'shadow_enabled' => $this->shadow_enabled,
            'studio_style_id' => $this->studio_style_id,
            'lighting_preset_id' => $this->lighting_preset_id,
            'scene_type_id' => $this->scene_type_id,
            'quality' => $this->whenLoaded('qualityLevel', fn () => $this->qualityLevel ? [
                'id' => $this->qualityLevel->id,
                'key' => $this->qualityLevel->key,
                'name' => $this->qualityLevel->name,
            ] : null),
            'model_label' => (string) Setting::get('ai.model_label'),
            'credits_charged' => $this->credits_charged,
            'credits_refunded' => $this->credits_refunded,
            'error_message' => $this->error_message,
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project->id,
                'title' => $this->project->title,
                'original_url' => $this->project->originalUrl(),
                'cutout_url' => $this->project->cutoutUrl(),
            ]),
            'images' => GenerationImageResource::collection($images),
            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
