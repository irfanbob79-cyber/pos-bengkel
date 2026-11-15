<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'system_qty',
        'scanned_qty',
        'difference_qty',
        'cost_snapshot',
        'is_scanned',
    ];

    protected function casts(): array
    {
        return [
            'system_qty' => 'decimal:2',
            'scanned_qty' => 'decimal:2',
            'difference_qty' => 'decimal:2',
            'cost_snapshot' => 'decimal:2',
            'is_scanned' => 'boolean',
        ];
    }

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
