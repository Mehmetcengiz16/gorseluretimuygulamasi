<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Template */
class TemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'cover_url' => $this->coverUrl(),
            'badge' => $this->badge,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'studio_style_id' => $this->studio_style_id,
            'scene_type_id' => $this->scene_type_id,
            'lighting_preset_id' => $this->lighting_preset_id,
            'default_prompt' => $this->default_prompt,
            'quality_key' => $this->quality_key,
            'is_pro' => $this->is_pro,
            'likes_count' => $this->likes_count,
            'uses_count' => $this->uses_count,
            'liked' => (bool) ($this->liked ?? false),
        ];
    }
}
