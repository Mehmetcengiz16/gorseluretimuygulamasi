<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/** Mobil istemcinin `code` alanına göre davranabildiği iş kuralı hataları. */
class ApiException extends Exception
{
    public function __construct(string $message, public string $errorCode, public int $status = 400)
    {
        parent::__construct($message);
    }

    public static function insufficientCredits(): self
    {
        return new self('Yeterli kredin yok.', 'INSUFFICIENT_CREDITS', 402);
    }

    public static function proRequired(): self
    {
        return new self('Bu özellik PRO üyelere özeldir.', 'PRO_REQUIRED', 403);
    }

    public static function accountDisabled(): self
    {
        return new self('Hesabın devre dışı bırakıldı.', 'ACCOUNT_DISABLED', 403);
    }

    public static function aiBudgetExceeded(): self
    {
        return new self('Sistem şu anda yoğun, lütfen daha sonra tekrar dene.', 'AI_BUDGET_EXCEEDED', 503);
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage(), 'code' => $this->errorCode, 'errors' => (object) []], $this->status);
    }
}
