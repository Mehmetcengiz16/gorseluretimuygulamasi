<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudioStyle extends Model
{
    protected $fillable = ['name', 'slug', 'thumbnail_path', 'prompt_fragment', 'is_pro', 'sort_order', 'is_active'];

    protected $casts = ['is_pro' => 'boolean', 'is_active' => 'boolean'];

    public function thumbnailUrl(): ?string
    {
        return Media::url($this->thumbnail_path);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
