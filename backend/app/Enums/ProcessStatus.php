<?php

namespace App\Enums;

/** Dekupe ve tekil varyant işlerinin durumu. */
enum ProcessStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Done = 'done';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Bekliyor',
            self::Processing => 'İşleniyor',
            self::Done => 'Hazır',
            self::Failed => 'Başarısız',
        };
    }
}
