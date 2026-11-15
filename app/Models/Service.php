<?php

namespace App\Models;

use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'default_price',
        'commission_type',
        'commission_value',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'commission_type' => CommissionType::class,
            'commission_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function calculateCommission(float $actualPrice): float
    {
        if ($this->commission_type === CommissionType::Percentage) {
            return round(($actualPrice * (float) $this->commission_value) / 100, 2);
        }

        return (float) $this->commission_value;
    }
}
