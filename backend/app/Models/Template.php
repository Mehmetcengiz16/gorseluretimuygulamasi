<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Template extends Model
{
    protected $fillable = [
        'category_id', 'title', 'subtitle', 'cover_path', 'badge',
        'studio_style_id', 'scene_type_id', 'lighting_preset_id',
        'default_prompt', 'quality_key', 'is_pro', 'is_featured',
        'likes_count', 'uses_count', 'sort_order', 'is_active',
    ];

    protected $casts = ['is_pro' => 'boolean', 'is_featured' => 'boolean', 'is_active' => 'boolean'];

    public const BADGES = ['pro' => 'PRO', 'trend' => 'TREND', 'new' => 'YENİ'];

    public function coverUrl(): ?string
    {
        return Media::url($this->cover_path);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function studioStyle(): BelongsTo
    {
        return $this->belongsTo(StudioStyle::class);
    }

    public function sceneType(): BelongsTo
    {
        return $this->belongsTo(SceneType::class);
    }

    public function lightingPreset(): BelongsTo
    {
        return $this->belongsTo(LightingPreset::class);
    }

    public function likers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'template_likes')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
