<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryTier extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'min_distance_km' => 'decimal:3',
            'max_distance_km' => 'decimal:3',
            'fee' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }
}
