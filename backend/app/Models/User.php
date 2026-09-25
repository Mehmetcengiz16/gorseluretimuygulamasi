<?php

namespace App\Models;

use App\Support\Media;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_path',
        'is_active',
        'pro_expires_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Veritabanı varsayılanlarıyla aynı; yeni oluşturulan nesnede de dolu olsun. */
    protected $attributes = [
        'is_active' => true,
        'credit_balance' => 0,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'pro_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'credit_balance' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function isPro(): bool
    {
        return $this->pro_expires_at !== null && $this->pro_expires_at->isFuture();
    }

    public function avatarUrl(): ?string
    {
        return Media::url($this->avatar_path);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function likedTemplates(): BelongsToMany
    {
        return $this->belongsToMany(Template::class, 'template_likes')->withTimestamps();
    }
}
