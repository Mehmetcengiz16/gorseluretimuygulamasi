<?php

namespace App\Models;

use App\Enums\ProcessStatus;
use App\Support\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'original_path', 'original_width', 'original_height',
        'cutout_path', 'cutout_status', 'cutout_error', 'scene_type_id', 'shadow_enabled', 'template_id',
    ];

    protected $casts = [
        'cutout_status' => ProcessStatus::class,
        'shadow_enabled' => 'boolean',
    ];

    public function originalUrl(): ?string
    {
        return Media::url($this->original_path);
    }

    public function cutoutUrl(): ?string
    {
        return Media::url($this->cutout_path);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sceneType(): BelongsTo
    {
        return $this->belongsTo(SceneType::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }

    public function latestGeneration(): HasOne
    {
        return $this->hasOne(Generation::class)->latestOfMany();
    }
}
