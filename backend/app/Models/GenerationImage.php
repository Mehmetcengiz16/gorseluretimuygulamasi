<?php

namespace App\Models;

use App\Enums\ProcessStatus;
use App\Support\EditTools;
use App\Support\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerationImage extends Model
{
    protected $fillable = [
        'generation_id', 'source_image_id', 'edit_tool', 'edit_option', 'edit_prompt', 'aspect_ratio',
        'variant_index', 'status', 'path', 'thumb_path', 'width', 'height',
        'is_master', 'is_favorite', 'openrouter_generation_id', 'cost_usd', 'error_message',
    ];

    protected $casts = [
        'status' => ProcessStatus::class,
        'is_master' => 'boolean',
        'is_favorite' => 'boolean',
        'cost_usd' => 'float',
    ];

    public function url(): ?string
    {
        return Media::url($this->path);
    }

    public function thumbUrl(): ?string
    {
        return Media::url($this->thumb_path ?? $this->path);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    /** Düzenleme aracıyla üretildiyse, girdi olarak kullanılan varyant. */
    public function source(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_image_id');
    }

    public function editLabel(): ?string
    {
        return EditTools::label($this->edit_tool, $this->edit_option);
    }
}
