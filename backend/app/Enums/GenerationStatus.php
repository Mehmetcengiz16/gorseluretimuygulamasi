<?php

namespace App\Enums;

enum GenerationStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Partial = 'partial';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Kuyrukta',
            self::Processing => 'İşleniyor',
            self::Completed => 'Tamamlandı',
            self::Partial => 'Kısmi',
            self::Failed => 'Başarısız',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Partial, self::Failed], true);
    }
}
