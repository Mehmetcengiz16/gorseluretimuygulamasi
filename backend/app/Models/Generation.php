<?php

namespace App\Models;

use App\Enums\GenerationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Generation extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'user_prompt', 'final_prompt',
        'studio_style_id', 'scene_type_id', 'lighting_preset_id', 'quality_level_id', 'template_id',
        'shadow_enabled', 'aspect_ratio', 'variant_count', 'model_id', 'status',
        'credits_charged', 'credits_refunded', 'cost_usd', 'error_message', 'started_at', 'completed_at',
    ];

    protected $attributes = [
        'credits_refunded' => 0,
        'cost_usd' => 0,
    ];

    protected $casts = [
        'status' => GenerationStatus::class,
        'shadow_enabled' => 'boolean',
        'cost_usd' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function qualityLevel(): BelongsTo
    {
        return $this->belongsTo(QualityLevel::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(GenerationImage::class)->orderBy('variant_index');
    }

    public function masterImage(): HasOne
    {
        return $this->hasOne(GenerationImage::class)->where('is_master', true);
    }
}
