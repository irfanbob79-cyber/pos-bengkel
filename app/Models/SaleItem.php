<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'item_type',
        'product_id',
        'service_id',
        'mechanic_id',
        'cost_snapshot',
        'unit_price',
        'qty',
        'subtotal',
        'mechanic_commission',
    ];

    protected function casts(): array
    {
        return [
            'cost_snapshot' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'qty' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'mechanic_commission' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }
}
