<?php

namespace App\Enums;

enum StockTransferStatus: string
{
    case Draft = 'draft';
    case InTransit = 'in_transit';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft / Pengajuan',
            self::InTransit => 'Dalam Pengiriman (In-Transit)',
            self::Completed => 'Diterima Lengkap',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
