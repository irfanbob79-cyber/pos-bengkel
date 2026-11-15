<?php

namespace App\Enums;

enum StockOpnameScope: string
{
    case All = 'all';
    case Category = 'category';
    case BinLocation = 'bin_location';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Seluruh Gudang',
            self::Category => 'Berdasarkan Kategori',
            self::BinLocation => 'Berdasarkan Lokasi Rak (Bin)',
        };
    }
}
