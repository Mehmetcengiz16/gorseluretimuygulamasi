<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    protected $casts = ['value' => 'json'];

    /** Tabloda kayıt yoksa kullanılan varsayılanlar. */
    public const DEFAULTS = [
        'ai.image_model' => 'google/gemini-3.1-flash-lite-image',
        'ai.cutout_model' => 'google/gemini-3.1-flash-lite-image',
        'ai.text_model' => 'openai/gpt-5.4-nano',
        'ai.api_key' => null,   // AiConfig ile şifreli saklanır
        'ai.fake_mode' => null, // null = .env AI_FAKE
        'ai.model_label' => 'Studio Diffusion XL',
        'ai.daily_budget_usd' => 20,
        'ai.system_prompt' => "You are a world-class commercial product photographer and retoucher.\n"
            ."Take the product in the provided image and place it in a new professional studio scene.\n"
            ."STRICT RULES:\n"
            ."- Keep the product IDENTICAL: same shape, proportions, colors, materials, label, logo and all printed text. Do not redraw, translate or invent text.\n"
            ."- Only change the environment: background, surface, props, lighting and atmosphere.\n"
            ."- Photorealistic, high-end advertising quality, sharp focus on the product, physically correct lighting, reflections and shadows.\n"
            ."- No people, no hands, no watermarks, no extra text, no borders.\n"
            .'- Output a single image.',
        'ai.cutout_prompt' => 'Isolate the product exactly as it is. Remove the entire background and replace it with a perfectly uniform pure white (#FFFFFF) background. Keep crisp, clean edges. Do not alter, redraw or recolor the product in any way. Output a single image.',
        'credits.signup_bonus' => 10,
        'credits.cutout_cost' => 0,
        'credits.enhance_cost' => 0,
        'limits.max_variants_free' => 4,
        'app.maintenance' => false,
        'app.min_version' => '1.0.0',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', fn () => static::query()->pluck('value', 'key')->all());

        if (array_key_exists($key, $all) && $all[$key] !== null) {
            return $all[$key];
        }

        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => explode('.', $key)[0]]);
        Cache::forget('settings.all');
    }
}
