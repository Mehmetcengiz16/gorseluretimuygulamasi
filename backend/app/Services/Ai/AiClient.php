<?php

namespace App\Services\Ai;

interface AiClient
{
    /**
     * Girdi görseli + prompt ile yeni görsel üretir.
     *
     * @param  array{purpose?: string, user_id?: int, generation_image_id?: int, aspect_ratio?: string, image_size?: string}  $meta
     */
    public function generateImage(string $model, string $systemPrompt, string $userPrompt, string $inputImageBinary, string $inputMime, array $meta = []): AiImageResult;

    /** Metin üretir (Prompt'u Geliştir). */
    public function generateText(string $model, string $systemPrompt, string $userPrompt, array $meta = []): string;
}
