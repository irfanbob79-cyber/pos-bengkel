<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Suspended = 'suspended';
    case Completed = 'completed';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Suspended => 'Tertunda (Hold / Suspend)',
            self::Completed => 'Selesai (Lunas)',
            self::Void => 'Dibatalkan (Void)',
        };
    }
}
