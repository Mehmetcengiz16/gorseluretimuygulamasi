<?php

namespace App\Services\Ai;

use App\Models\AiRequestLog;
use Intervention\Image\Laravel\Facades\Image;

/**
 * AI_FAKE=true iken kullanılır: OpenRouter'a istek atmadan, girdi görselini sıcak bir stüdyo
 * zemini üzerine yerleştirerek sahte bir sonuç üretir. Geliştirme ve testlerde para harcanmaz.
 */
class FakeAiClient implements AiClient
{
    private const BACKDROPS = ['#1a1410', '#221a12', '#14110d', '#2a1f14', '#18130f', '#201810', '#110e0b', '#261c11'];

    public function generateImage(string $model, string $systemPrompt, string $userPrompt, string $inputImageBinary, string $inputMime, array $meta = []): AiImageResult
    {
        usleep(random_int(600, 1500) * 1000);

        if (($meta['purpose'] ?? null) === 'edit') {
            return $this->fakeEdit($inputImageBinary, $model, $meta);
        }

        [$w, $h] = $this->canvasSize($meta['aspect_ratio'] ?? '4:5');
        $isCutout = ($meta['purpose'] ?? null) === 'cutout';

        $canvas = Image::create($w, $h)->fill($isCutout ? '#ffffff' : self::BACKDROPS[array_rand(self::BACKDROPS)]);

        if (! $isCutout) {
            // Amber ışık halesi + podyum hissi veren zemin şeridi
            $canvas->drawEllipse((int) ($w / 2), (int) ($h * 0.45), function ($e) use ($w) {
                $e->size((int) ($w * 0.9), (int) ($w * 0.9));
                $e->background('rgba(255, 184, 0, 0.10)');
            });
            $canvas->drawRectangle(0, (int) ($h * 0.78), function ($r) use ($w, $h) {
                $r->size($w, (int) ($h * 0.22));
                $r->background('rgba(0, 0, 0, 0.35)');
            });
        }

        $product = Image::read($inputImageBinary)->scaleDown((int) ($w * 0.72), (int) ($h * 0.68));
        $canvas->place($product, 'center', 0, $isCutout ? 0 : (int) ($h * 0.04));

        AiRequestLog::create([
            'user_id' => $meta['user_id'] ?? null,
            'generation_image_id' => $meta['generation_image_id'] ?? null,
            'purpose' => $meta['purpose'] ?? 'generate',
            'model_id' => 'fake/'.$model,
            'status_code' => 200,
            'duration_ms' => 0,
            'cost_usd' => 0,
        ]);

        return new AiImageResult((string) $canvas->toPng(), 'fake-'.uniqid(), 0.0);
    }

    public function generateText(string $model, string $systemPrompt, string $userPrompt, array $meta = []): string
    {
        return 'Lüks siyah mermer podyumda fasetli kristal ürün, sinematik amber rim ışıklandırması, '
            .'altın parıltılı sis partikülleri, yumuşak yansımalar, hiper-ayrıntılı 8K makro reklam stüdyosu çekimi.';
    }

    /** Düzenleme araçlarının sahte karşılığı: her araç görünür, basit bir efekt uygular. */
    private function fakeEdit(string $binary, string $model, array $meta): AiImageResult
    {
        $image = Image::read($binary);
        $option = $meta['edit_option'] ?? null;

        switch ($meta['edit_tool'] ?? null) {
            case 'retouch':
                $image->sharpen(15)->contrast(6);
                break;
            case 'light':
                $image->brightness($option === 'back' ? -6 : 10)->gamma($option === 'top' ? 1.15 : 1.05);
                break;
            case 'color':
                match ($option) {
                    'warm' => $image->colorize(14, 4, -12),
                    'cool' => $image->colorize(-12, 0, 16),
                    'golden' => $image->colorize(20, 10, -18)->contrast(8),
                    default => $image->contrast(4),
                };
                break;
            case 'ratio':
                [$w, $h] = $this->canvasSize($meta['aspect_ratio'] ?? '4:5');
                $canvas = Image::create($w, $h)->fill('#16120e');
                $canvas->place($image->scaleDown($w, $h), 'center');
                $image = $canvas;
                break;
            case 'upscale':
                $image->scale(width: min(2160, $image->width() * 2))->sharpen(8);
                break;
        }

        AiRequestLog::create([
            'user_id' => $meta['user_id'] ?? null,
            'generation_image_id' => $meta['generation_image_id'] ?? null,
            'purpose' => 'edit',
            'model_id' => 'fake/'.$model,
            'status_code' => 200,
            'duration_ms' => 0,
            'cost_usd' => 0,
        ]);

        return new AiImageResult((string) $image->toPng(), 'fake-'.uniqid(), 0.0);
    }

    /** @return array{int, int} */
    private function canvasSize(string $ratio): array
    {
        return match ($ratio) {
            '1:1' => [1080, 1080],
            '3:4' => [1080, 1440],
            '9:16' => [1080, 1920],
            '4:3' => [1440, 1080],
            '16:9' => [1920, 1080],
            default => [1080, 1350],
        };
    }
}
