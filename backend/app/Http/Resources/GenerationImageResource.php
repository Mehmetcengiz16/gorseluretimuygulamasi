<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\GenerationImage */
class GenerationImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'generation_id' => $this->generation_id,
            'variant_index' => $this->variant_index,
            'status' => $this->status->value,
            'is_master' => $this->is_master,
            'is_favorite' => $this->is_favorite,
            'url' => $this->url(),
            'thumb_url' => $this->path ? $this->thumbUrl() : null,
            'width' => $this->width,
            'height' => $this->height,
            'aspect_ratio' => $this->aspect_ratio,
            'source_image_id' => $this->source_image_id,
            'edit_tool' => $this->edit_tool,
            'edit_option' => $this->edit_option,
            'edit_label' => $this->editLabel(),
        ];
    }
}
