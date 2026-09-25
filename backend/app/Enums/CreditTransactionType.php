<?php

namespace App\Enums;

enum CreditTransactionType: string
{
    case SignupBonus = 'signup_bonus';
    case Generation = 'generation';
    case Refund = 'refund';
    case AdminGrant = 'admin_grant';
    case AdminDeduct = 'admin_deduct';
    case Purchase = 'purchase';

    public function label(): string
    {
        return match ($this) {
            self::SignupBonus => 'Kayıt Bonusu',
            self::Generation => 'Üretim',
            self::Refund => 'İade',
            self::AdminGrant => 'Admin Ekleme',
            self::AdminDeduct => 'Admin Düşme',
            self::Purchase => 'Satın Alma',
        };
    }
}
