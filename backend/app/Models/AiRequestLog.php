<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequestLog extends Model
{
    protected $fillable = [
        'user_id', 'generation_image_id', 'purpose', 'model_id', 'status_code', 'duration_ms',
        'prompt_tokens', 'completion_tokens', 'cost_usd', 'error', 'request_meta',
    ];

    protected $casts = ['request_meta' => 'array', 'cost_usd' => 'float'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
