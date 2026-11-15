<?php

namespace App\Enums;

enum CashRegisterStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Buka (Aktif)',
            self::Closed => 'Tutup (Closed)',
        };
    }
}
