<?php

namespace App\Http\Resources;

use App\Models\LightingPreset;
use App\Models\QualityLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Stüdyo stili, sahne türü, ışık ön ayarı, kalite seviyesi ve kategori için ortak çıktı.
 * prompt_fragment kasıtlı olarak istemciye gönderilmez.
 */
class CatalogItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $r = $this->resource;

        if ($r instanceof QualityLevel) {
            return [
                'id' => $r->id,
                'key' => $r->key,
                'name' => $r->name,
                'credit_multiplier' => $r->credit_multiplier,
                'image_size' => $r->image_size,
                'is_pro' => $r->is_pro,
            ];
        }

        return array_filter([
            'id' => $r->id,
            'name' => $r->name,
            'slug' => $r->slug ?? null,
            'subtitle' => $r instanceof LightingPreset ? $r->subtitle : null,
            'icon' => $r->icon ?? null,
            'thumbnail_url' => method_exists($r, 'thumbnailUrl') ? $r->thumbnailUrl() : null,
            'is_pro' => $r->is_pro ?? null,
        ], fn ($v) => $v !== null);
    }
}
