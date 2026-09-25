<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LightingPreset extends Model
{
    protected $fillable = ['name', 'slug', 'subtitle', 'icon', 'prompt_fragment', 'is_pro', 'sort_order', 'is_active'];

    protected $casts = ['is_pro' => 'boolean', 'is_active' => 'boolean'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
