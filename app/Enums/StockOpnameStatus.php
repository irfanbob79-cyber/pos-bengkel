<?php

namespace App\Enums;

enum StockOpnameStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Sedang Berjalan (Scanning)',
            self::Completed => 'Selesai & Disetujui',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
