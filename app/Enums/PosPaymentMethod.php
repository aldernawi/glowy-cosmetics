<?php

namespace App\Enums;

enum PosPaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'نقدًا',
            self::Card => 'بطاقة مصرفية',
            self::Transfer => 'تحويل أو خدمة مصرفية',
        };
    }
}
