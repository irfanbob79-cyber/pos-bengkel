<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case Ordered = 'ordered';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Ordered => 'Dipesan (Ordered)',
            self::Received => 'Diterima di Gudang (Received)',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
