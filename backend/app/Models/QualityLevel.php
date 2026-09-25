<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityLevel extends Model
{
    protected $fillable = ['key', 'name', 'credit_multiplier', 'image_size', 'model_id', 'prompt_fragment', 'is_pro', 'sort_order'];

    protected $casts = ['credit_multiplier' => 'float', 'is_pro' => 'boolean'];

    public function creditsFor(int $variants): int
    {
        return (int) max(1, ceil($variants * $this->credit_multiplier));
    }
}
