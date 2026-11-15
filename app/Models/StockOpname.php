<?php

namespace App\Models;

use App\Enums\StockOpnameScope;
use App\Enums\StockOpnameStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    use HasFactory;

    protected $fillable = [
        'opname_number',
        'branch_id',
        'user_id',
        'approved_by_id',
        'scope_type',
        'scope_value',
        'status',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scope_type' => StockOpnameScope::class,
            'status' => StockOpnameStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
