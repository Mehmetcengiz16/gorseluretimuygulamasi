<?php

namespace App\Enums;

enum AdminRole: string
{
    case SuperAdmin = 'super_admin';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Süper Admin',
            self::Editor => 'Editör',
        };
    }
}
