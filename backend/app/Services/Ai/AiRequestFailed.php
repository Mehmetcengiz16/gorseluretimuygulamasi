<?php

namespace App\Services\Ai;

use RuntimeException;

class AiRequestFailed extends RuntimeException
{
    /** @param bool $retryable 429/5xx/zaman aşımı gibi tekrar denenebilir hatalar */
    public function __construct(string $message, public readonly bool $retryable = true, public readonly ?int $statusCode = null)
    {
        parent::__construct($message);
    }
}
