<?php

namespace App\Enums;

enum PosSaleStatus: string
{
    case Completed = 'completed';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'مكتمل',
            self::Voided => 'ملغي',
        };
    }
}
