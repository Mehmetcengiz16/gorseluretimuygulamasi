<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Project */
class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'original_url' => $this->originalUrl(),
            'original_width' => $this->original_width,
            'original_height' => $this->original_height,
            'cutout_url' => $this->cutoutUrl(),
            'cutout_status' => $this->cutout_status->value,
            'cutout_error' => $this->cutout_error,
            'scene_type_id' => $this->scene_type_id,
            'shadow_enabled' => $this->shadow_enabled,
            'template_id' => $this->template_id,
            'template' => $this->whenLoaded('template', fn () => $this->template ? new TemplateResource($this->template) : null),
            'latest_generation' => $this->whenLoaded('latestGeneration', fn () => $this->latestGeneration ? [
                'id' => $this->latestGeneration->id,
                'status' => $this->latestGeneration->status->value,
                'variant_count' => $this->latestGeneration->variant_count,
                'master_thumb_url' => $this->latestGeneration->masterImage?->thumbUrl(),
            ] : null),
            'generations' => GenerationResource::collection($this->whenLoaded('generations')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
