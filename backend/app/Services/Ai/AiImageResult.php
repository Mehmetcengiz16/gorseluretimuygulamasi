<?php

namespace App\Services\Ai;

final class AiImageResult
{
    public function __construct(
        public readonly string $binary,
        public readonly ?string $generationId = null,
        public readonly ?float $costUsd = null,
    ) {}
}
