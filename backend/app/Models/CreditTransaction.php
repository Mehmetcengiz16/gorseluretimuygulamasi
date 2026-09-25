<?php

namespace App\Models;

use App\Enums\CreditTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CreditTransaction extends Model
{
    protected $fillable = ['user_id', 'type', 'amount', 'balance_after', 'reference_type', 'reference_id', 'description', 'admin_id'];

    protected $casts = ['type' => CreditTransactionType::class];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
