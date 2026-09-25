<?php

namespace Tests\Feature;

use App\Services\Ai\AiRequestFailed;
use App\Services\Ai\OpenRouterClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterClientTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private function imageResponse(): array
    {
        return ['id' => 'gen-1', 'choices' => [['message' => ['images' => [['image_url' => ['url' => 'data:image/png;base64,'.self::PNG]]]]]], 'usage' => ['cost' => 0.03]];
    }

    public function test_unsupported_image_size_falls_back_and_is_remembered(): void
    {
        $sizes = [];
        Http::fake(function (Request $request) use (&$sizes) {
            $size = $request->data()['image_config']['image_size'] ?? null;
            $sizes[] = $size;

            return $size === '4K'
                ? Http::response(['error' => ['message' => "image_size '4K' is not supported"]], 400)
                : Http::response($this->imageResponse());
        });

        $client = new OpenRouterClient('key', 'https://openrouter.ai/api/v1');
        $model = 'google/gemini-3.1-flash-lite-image';

        $result = $client->generateImage($model, 's', 'u', 'img', 'image/png', ['aspect_ratio' => '4:5', 'image_size' => '4K']);
        $this->assertSame(0.03, $result->costUsd);
        $this->assertSame(['4K', '2K'], $sizes);
        $this->assertSame('2K', Cache::get('openrouter.max_image_size.'.$model));

        // İkinci istek doğrudan desteklenen boyutla gider, boşuna 400 alınmaz.
        $client->generateImage($model, 's', 'u', 'img', 'image/png', ['image_size' => '4K']);
        $this->assertSame(['4K', '2K', '2K'], $sizes);
    }

    public function test_nested_provider_size_error_falls_back_to_1k(): void
    {
        $sizes = [];
        Http::fake(function (Request $request) use (&$sizes) {
            $size = $request->data()['image_config']['image_size'] ?? null;
            $sizes[] = $size;

            return match ($size) {
                '4K' => Http::response(['error' => ['message' => "image_size '4K' is not supported"]], 400),
                // OpenRouter'ın gerçek yanıtı: genel mesaj + asıl neden metadata.raw içinde
                '2K' => Http::response(['error' => ['message' => 'Provider returned error', 'code' => 400, 'metadata' => [
                    'raw' => json_encode(['error' => ['code' => 400, 'message' => 'Image size 2K is not supported for this model', 'status' => 'INVALID_ARGUMENT']]),
                ]]], 400),
                default => Http::response($this->imageResponse()),
            };
        });

        $model = 'google/gemini-3.1-flash-lite-image';
        (new OpenRouterClient('key', 'https://openrouter.ai/api/v1'))
            ->generateImage($model, 's', 'u', 'img', 'image/png', ['aspect_ratio' => '4:5', 'image_size' => '4K']);

        $this->assertSame(['4K', '2K', '1K'], $sizes);
        $this->assertSame('1K', Cache::get('openrouter.max_image_size.'.$model));
        $this->assertStringContainsString('Image size 2K is not supported', \App\Models\AiRequestLog::where('request_meta->image_size', '2K')->value('error'));
    }

    public function test_other_400_errors_are_not_retried(): void
    {
        Http::fake(fn () => Http::response(['error' => ['message' => 'Invalid API key']], 400));

        $this->expectException(AiRequestFailed::class);
        (new OpenRouterClient('key', 'https://openrouter.ai/api/v1'))
            ->generateImage('google/gemini-3.1-flash-lite-image', 's', 'u', 'img', 'image/png', ['image_size' => '4K']);
    }

    public function test_non_google_models_never_receive_image_size(): void
    {
        Http::fake(fn () => Http::response($this->imageResponse()));

        (new OpenRouterClient('key', 'https://openrouter.ai/api/v1'))
            ->generateImage('openai/gpt-5-image-mini', 's', 'u', 'img', 'image/png', ['aspect_ratio' => '1:1', 'image_size' => '4K']);

        Http::assertSent(fn (Request $r) => ($r->data()['image_config'] ?? []) === ['aspect_ratio' => '1:1']);
    }
}
