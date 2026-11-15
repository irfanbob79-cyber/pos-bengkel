<?php

namespace App\Enums;

enum PaymentType: string
{
    case Cash = 'cash';
    case Tempo = 'tempo';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Tempo => 'Tempo / Kredit',
        };
    }
}
