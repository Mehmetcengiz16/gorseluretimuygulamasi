<?php

namespace App\Services\Ai;

use App\Models\AiRequestLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OpenRouterClient implements AiClient
{
    public function __construct(private readonly string $apiKey, private readonly string $baseUrl) {}

    public function generateImage(string $model, string $systemPrompt, string $userPrompt, string $inputImageBinary, string $inputMime, array $meta = []): AiImageResult
    {
        $payload = [
            'model' => $model,
            'modalities' => ['image', 'text'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => [
                    ['type' => 'text', 'text' => $userPrompt],
                    ['type' => 'image_url', 'image_url' => ['url' => 'data:'.$inputMime.';base64,'.base64_encode($inputImageBinary)]],
                ]],
            ],
        ];

        // image_size (1K/2K/4K) Gemini'ye özgü; diğer sağlayıcılara yalnızca oran gönderilir.
        // Her model her boyutu desteklemez (ör. flash-lite 4K vermez): desteklenmeyen boyutta
        // bir alt boyutla tekrar denenir, öğrenilen üst sınır model başına önbelleğe alınır.
        $size = str_starts_with($model, 'google/') ? $this->clampSize($model, $meta['image_size'] ?? null) : null;

        while (true) {
            $imageConfig = array_filter(['aspect_ratio' => $meta['aspect_ratio'] ?? null, 'image_size' => $size]);
            if ($imageConfig) {
                $payload['image_config'] = $imageConfig;
            } else {
                unset($payload['image_config']);
            }

            try {
                $json = $this->send($payload, $meta + ['purpose' => 'generate'], $model);
                break;
            } catch (AiRequestFailed $e) {
                if ($size === null || ! $this->isImageSizeError($e)) {
                    throw $e;
                }
                $size = self::SIZES[array_search($size, self::SIZES, true) - 1] ?? null;
                Cache::forever('openrouter.max_image_size.'.$model, $size ?? 'none');
            }
        }

        $url = data_get($json, 'choices.0.message.images.0.image_url.url');
        if (! is_string($url) || ! str_contains($url, 'base64,')) {
            $text = (string) data_get($json, 'choices.0.message.content', '');
            throw new AiRequestFailed('Model görsel döndürmedi. '.mb_substr($text, 0, 200), retryable: true);
        }

        $binary = base64_decode(substr($url, strpos($url, 'base64,') + 7), true);
        if ($binary === false) {
            throw new AiRequestFailed('Görsel verisi çözülemedi.', retryable: true);
        }

        return new AiImageResult($binary, data_get($json, 'id'), $this->cost($json));
    }

    public function generateText(string $model, string $systemPrompt, string $userPrompt, array $meta = []): string
    {
        $json = $this->send([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => 400,
        ], $meta + ['purpose' => 'enhance_prompt'], $model);

        return trim((string) data_get($json, 'choices.0.message.content', ''));
    }

    private function send(array $payload, array $meta, string $model): array
    {
        $started = microtime(true);
        $log = [
            'user_id' => $meta['user_id'] ?? null,
            'generation_image_id' => $meta['generation_image_id'] ?? null,
            'purpose' => $meta['purpose'],
            'model_id' => $model,
            // Gerçekte gönderilen değerler (geri çekilmede image_size istenenden farklı olabilir).
            'request_meta' => array_filter([
                'aspect_ratio' => $payload['image_config']['aspect_ratio'] ?? null,
                'image_size' => $payload['image_config']['image_size'] ?? null,
                'requested_size' => ($meta['image_size'] ?? null) !== ($payload['image_config']['image_size'] ?? null) ? ($meta['image_size'] ?? null) : null,
            ]),
        ];

        try {
            /** @var Response $response */
            $response = $this->http()->post('/chat/completions', $payload);
        } catch (ConnectionException $e) {
            AiRequestLog::create($log + ['duration_ms' => $this->ms($started), 'error' => $e->getMessage()]);
            throw new AiRequestFailed('OpenRouter bağlantı hatası: '.$e->getMessage(), retryable: true);
        }

        $json = $response->json() ?? [];

        AiRequestLog::create($log + [
            'status_code' => $response->status(),
            'duration_ms' => $this->ms($started),
            'prompt_tokens' => data_get($json, 'usage.prompt_tokens'),
            'completion_tokens' => data_get($json, 'usage.completion_tokens'),
            'cost_usd' => $this->cost($json),
            'error' => $response->successful() ? null : mb_substr($response->body(), 0, 2000),
        ]);

        if (! $response->successful()) {
            $status = $response->status();
            throw new AiRequestFailed($this->errorMessage($json, $status), retryable: $status === 429 || $status >= 500, statusCode: $status);
        }

        // OpenRouter bazı sağlayıcı hatalarını 200 + error gövdesiyle döndürebilir.
        if ($error = data_get($json, 'error.message')) {
            throw new AiRequestFailed($error, retryable: true);
        }

        return $json;
    }

    /**
     * "Provider returned error" gibi genel mesajlarda asıl neden metadata.raw içindedir
     * (ör. "Image size 2K is not supported for this model"); ikisi birleştirilir.
     */
    private function errorMessage(array $json, int $status): string
    {
        $message = (string) data_get($json, 'error.message', 'OpenRouter hatası ('.$status.')');
        $raw = data_get($json, 'error.metadata.raw');
        if (is_string($raw)) {
            $inner = data_get(json_decode($raw, true), 'error.message') ?? trim($raw);
            if ($inner && ! str_contains($message, (string) $inner)) {
                $message .= ': '.mb_substr((string) $inner, 0, 300);
            }
        }

        return $message;
    }

    /** "image_size", "Image size" vb. — boyut desteklenmiyor hatası mı? */
    private function isImageSizeError(AiRequestFailed $e): bool
    {
        return $e->statusCode === 400
            && str_contains(str_replace([' ', '_'], '', strtolower($e->getMessage())), 'imagesize');
    }

    private const SIZES = ['1K', '2K', '4K'];

    /** İstenen boyutu, bu modelin daha önce desteklediği öğrenilmiş üst sınıra indirir. */
    private function clampSize(string $model, ?string $requested): ?string
    {
        if (! in_array($requested, self::SIZES, true)) {
            return null;
        }
        $max = Cache::get('openrouter.max_image_size.'.$model);
        if ($max === 'none') {
            return null;
        }
        if ($max && array_search($requested, self::SIZES, true) > array_search($max, self::SIZES, true)) {
            return $max;
        }

        return $requested;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->withHeaders([
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])
            ->acceptJson()
            ->timeout(170)
            ->connectTimeout(15);
    }

    private function cost(array $json): ?float
    {
        $cost = data_get($json, 'usage.cost');

        return is_numeric($cost) ? (float) $cost : null;
    }

    private function ms(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
