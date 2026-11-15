<?php

namespace App\Models;

use App\Enums\PaymentType;
use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_number',
        'branch_id',
        'supplier_id',
        'user_id',
        'payment_type',
        'due_date',
        'status',
        'total_amount',
        'notes',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_type' => PaymentType::class,
            'status' => PurchaseStatus::class,
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
