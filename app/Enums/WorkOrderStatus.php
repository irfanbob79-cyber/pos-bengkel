<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case Queued = 'queued';
    case InProgress = 'in_progress';
    case ReadyForPayment = 'ready_for_payment';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Antrean (Queued)',
            self::InProgress => 'Sedang Dikerjakan (In-Progress)',
            self::ReadyForPayment => 'Selesai Servis / Siap Bayar',
            self::Completed => 'Lunas / Selesai (Completed)',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
