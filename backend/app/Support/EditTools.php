<?php

namespace App\Support;

/**
 * Sonuç ekranındaki düzenleme araçları. Her araç seçenekleri ve modele giden talimatı tanımlar.
 * Mobil uygulamadaki lib/features/results/edit_tools.dart ile aynı anahtarları kullanır.
 */
final class EditTools
{
    public const TOOLS = [
        'retouch' => [
            'label' => 'Rötuş',
            'options' => [
                'clean' => ['Genel Temizlik', 'Retouch the product: remove dust, scratches, fingerprints, smudges and small imperfections from the product and the surface. Keep the product shape, label and text unchanged.'],
                'reflections' => ['Yansıma & Parlaklık', 'Enhance the specular highlights and reflections on the product so it looks premium and glossy, while keeping it realistic.'],
                'sharpen' => ['Keskinlik & Detay', 'Increase micro-detail, texture definition and edge sharpness of the product without adding noise or halos.'],
            ],
        ],
        'light' => [
            'label' => 'Işık Yönü',
            'options' => [
                'left' => ['Soldan', 'Relight the scene so the key light comes from the left side; shadows fall to the right.'],
                'right' => ['Sağdan', 'Relight the scene so the key light comes from the right side; shadows fall to the left.'],
                'top' => ['Üstten', 'Relight the scene with a soft top-down key light; short shadows directly under the product.'],
                'back' => ['Arkadan', 'Relight the scene with a strong backlight that creates a glowing rim around the product edges.'],
            ],
        ],
        'ratio' => [
            'label' => 'En/Boy Oranı',
            'options' => [
                '1:1' => ['1:1 Kare', null],
                '4:5' => ['4:5 Portre', null],
                '3:4' => ['3:4 Portre', null],
                '9:16' => ['9:16 Hikâye', null],
                '16:9' => ['16:9 Yatay', null],
            ],
        ],
        'color' => [
            'label' => 'Renk Sıcaklığı',
            'options' => [
                'warm' => ['Sıcak', 'Shift the color grading to a warmer temperature (around 4500K): amber highlights, cozy tones.'],
                'neutral' => ['Nötr', 'Neutralize the color temperature to a clean daylight balance (around 5500K) with accurate whites.'],
                'cool' => ['Soğuk', 'Shift the color grading to a cooler temperature (around 7000K): crisp bluish tones, fresh look.'],
                'golden' => ['Altın', 'Apply a rich golden cinematic color grade with warm gold highlights and deep contrast.'],
            ],
        ],
        'upscale' => [
            'label' => '4K Yükseltme',
            'options' => [
                '4k' => ['4K Ultra HD', 'Upscale this image to 4K resolution. Enhance fine details and textures and remove compression artifacts. Do not change the composition, colors, lighting or the product.'],
            ],
        ],
    ];

    public static function tools(): array
    {
        return array_keys(self::TOOLS);
    }

    public static function options(string $tool): array
    {
        return array_keys(self::TOOLS[$tool]['options'] ?? []);
    }

    public static function label(?string $tool, ?string $option = null): ?string
    {
        if (! $tool || ! isset(self::TOOLS[$tool])) {
            return null;
        }
        $label = self::TOOLS[$tool]['label'];
        $optionLabel = self::TOOLS[$tool]['options'][$option][0] ?? null;

        return $optionLabel && $tool !== 'upscale' ? $label.' · '.$optionLabel : $label;
    }

    /** Modele giden düzenleme talimatı. */
    public static function instruction(string $tool, string $option, ?string $note = null): string
    {
        $text = $tool === 'ratio'
            ? 'Recompose this image to a '.$option.' aspect ratio by naturally extending the background and set (outpainting). Keep the product the same, fully visible and centered; do not crop the product.'
            : self::TOOLS[$tool]['options'][$option][1];

        $text .= "\nKeep everything else exactly the same: the product, its shape, label, logo and printed text must not change.";

        if (filled($note)) {
            $text .= "\nAdditional note from the user (may be in Turkish): \"".trim($note).'"';
        }

        return $text;
    }
}
