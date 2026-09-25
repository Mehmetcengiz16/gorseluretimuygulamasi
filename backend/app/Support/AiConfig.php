<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * OpenRouter anahtarı ve sahte mod çözümü.
 * Öncelik: admin paneldeki ayar → .env (OPENROUTER_API_KEY / AI_FAKE).
 */
final class AiConfig
{
    public static function apiKey(): ?string
    {
        $stored = Setting::get('ai.api_key');
        if (is_string($stored) && $stored !== '') {
            try {
                return Crypt::decryptString($stored);
            } catch (DecryptException) {
                // APP_KEY değiştiyse eski şifreli değer okunamaz; .env'e düş.
            }
        }

        return config('services.openrouter.key') ?: null;
    }

    public static function storeApiKey(?string $key): void
    {
        Setting::put('ai.api_key', filled($key) ? Crypt::encryptString(trim($key)) : null);
    }

    public static function hasStoredKey(): bool
    {
        return filled(Setting::get('ai.api_key'));
    }

    /** Anahtarın maskelenmiş hali: "sk-or-v1-…a1b2". */
    public static function maskedKey(): ?string
    {
        $key = self::apiKey();
        if (! $key) {
            return null;
        }

        return mb_substr($key, 0, 9).'…'.mb_substr($key, -4);
    }

    /** Admin ayarı (açık/kapalı) varsa o, yoksa .env AI_FAKE. */
    public static function fakeModeSetting(): bool
    {
        $setting = Setting::get('ai.fake_mode');

        return $setting === null ? (bool) config('services.openrouter.fake') : (bool) $setting;
    }

    /** Sahte mod açıksa veya anahtar yoksa gerçek istek atılmaz. */
    public static function isFake(): bool
    {
        return self::fakeModeSetting() || blank(self::apiKey());
    }

    public static function baseUrl(): string
    {
        return rtrim((string) config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
    }
}
