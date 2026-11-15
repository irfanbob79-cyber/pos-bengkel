<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'motor_model_id',
        'plate_number',
        'chassis_number',
        'engine_number',
        'last_km',
        'next_service_date',
    ];

    protected function casts(): array
    {
        return [
            'last_km' => 'integer',
            'next_service_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function motorModel(): BelongsTo
    {
        return $this->belongsTo(MotorModel::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
