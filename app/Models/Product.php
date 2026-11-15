<?php

namespace App\Models;

use App\Enums\QualityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'brand_id',
        'sku',
        'barcode',
        'name',
        'unit',
        'quality_type',
        'moving_avg_cost',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quality_type' => QualityType::class,
            'moving_avg_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function motorModels(): BelongsToMany
    {
        return $this->belongsToMany(MotorModel::class, 'product_motor_compatibility')
            ->withPivot('notes')
            ->withTimestamps();
    }

    public function branchStocks(): HasMany
    {
        return $this->hasMany(BranchStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Dapatkan harga untuk tier tertentu dengan memperhitungkan min_qty
     */
    public function getPriceForTier(?int $priceTierId, float $qty = 1): float
    {
        if (! $priceTierId) {
            $defaultTier = PriceTier::where('is_default', true)->first();
            $priceTierId = $defaultTier?->id;
        }

        $tierPrice = $this->prices
            ->where('price_tier_id', $priceTierId)
            ->where('min_qty', '<=', $qty)
            ->sortByDesc('min_qty')
            ->first();

        if ($tierPrice) {
            return (float) $tierPrice->price;
        }

        // Fallback to default tier price
        $defaultPrice = $this->prices()
            ->whereHas('priceTier', fn ($q) => $q->where('is_default', true))
            ->first();

        return $defaultPrice ? (float) $defaultPrice->price : 0.0;
    }
}
