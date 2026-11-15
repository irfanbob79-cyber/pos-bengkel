<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MotorModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'name',
        'engine_capacity',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_motor_compatibility')
            ->withPivot('notes')
            ->withTimestamps();
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
