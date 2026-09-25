<?php

namespace App\Services\Ai;

use App\Models\LightingPreset;
use App\Models\QualityLevel;
use App\Models\SceneType;
use App\Models\Setting;
use App\Models\StudioStyle;

/**
 * Nihai prompt'u katmanlardan birleştirir:
 * sistem → sahne → stil → ışık → gölge → kalite → kullanıcı metni → oran (+ varyant ipucu).
 */
class PromptBuilder
{
    /** Aynı ayarlarla birbirinin kopyası olmayan varyantlar için kamera ipuçları. */
    private const VARIANT_HINTS = [
        1 => 'Camera: eye-level hero shot, product centered.',
        2 => 'Camera: slightly elevated 30 degree angle.',
        3 => 'Camera: close-up hero composition with shallow depth of field.',
        4 => 'Camera: wider composition showing more of the set.',
        5 => 'Camera: low angle, product appears monumental.',
        6 => 'Camera: three-quarter view from the left.',
        7 => 'Camera: three-quarter view from the right.',
        8 => 'Camera: dramatic top-down 60 degree angle.',
    ];

    public function systemPrompt(): string
    {
        return (string) Setting::get('ai.system_prompt');
    }

    public function build(
        ?SceneType $scene,
        ?StudioStyle $style,
        ?LightingPreset $lighting,
        ?QualityLevel $quality,
        bool $shadow,
        ?string $userPrompt,
        string $aspectRatio,
    ): string {
        $parts = array_filter([
            $scene?->prompt_fragment ? 'Scene: '.$scene->prompt_fragment.'.' : null,
            $style?->prompt_fragment ? 'Style: '.$style->prompt_fragment.'.' : null,
            $lighting?->prompt_fragment ? 'Lighting: '.$lighting->prompt_fragment.'.' : null,
            $shadow
                ? 'Add a realistic contact shadow under the product and a subtle floor reflection.'
                : 'No cast shadow and no reflection under the product.',
            $quality?->prompt_fragment ? $quality->prompt_fragment.'.' : null,
            'Aspect ratio: '.$aspectRatio.'.',
            filled($userPrompt) ? 'Additional art direction from the user (may be in Turkish): "'.trim($userPrompt).'"' : null,
        ]);

        return implode("\n", $parts);
    }

    public function forVariant(string $finalPrompt, int $variantIndex): string
    {
        $hint = self::VARIANT_HINTS[(($variantIndex - 1) % 8) + 1];

        return $finalPrompt."\n".$hint;
    }

    /** Sonuç ekranı araçları için sistem mesajı: yalnızca istenen değişikliği yap. */
    public function editSystemPrompt(): string
    {
        return "You are an expert commercial product photo retoucher.\n"
            ."Edit the provided studio product photo exactly as instructed and change nothing else.\n"
            ."The product must stay IDENTICAL: same shape, proportions, colors, materials, label, logo and printed text.\n"
            ."Photorealistic, high-end advertising quality. No people, no watermarks, no added text. Output a single image.";
    }

    public function enhanceSystemPrompt(): string
    {
        return 'Sen profesyonel bir ticari ürün fotoğrafçısı ve prompt yazarısın. Kullanıcının ürün çekimi açıklamasını, '
            .'verilen stil ve ışık bilgisini dikkate alarak tek paragraf halinde, en fazla 450 karakter, Türkçe, '
            .'ticari fotoğraf terimleriyle (ışık, yüzey, atmosfer, lens, detay) zenginleştir. '
            .'Sadece yeni prompt metnini döndür; açıklama, tırnak veya başlık ekleme.';
    }
}
