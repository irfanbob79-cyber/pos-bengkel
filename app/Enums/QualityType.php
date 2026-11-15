<?php

namespace App\Enums;

enum QualityType: string
{
    case Oem = 'oem';
    case Aftermarket = 'aftermarket';
    case Local = 'local';

    public function label(): string
    {
        return match ($this) {
            self::Oem => 'Original / OEM',
            self::Aftermarket => 'Aftermarket',
            self::Local => 'Lokal',
        };
    }
}
