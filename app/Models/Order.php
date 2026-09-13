<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => OrderStatus::class, 'payment_status' => PaymentStatus::class, 'delivery_latitude' => 'decimal:7', 'delivery_longitude' => 'decimal:7', 'delivery_distance_km' => 'decimal:3', 'delivery_fee' => 'decimal:3', 'subtotal' => 'decimal:3', 'discount' => 'decimal:3', 'total' => 'decimal:3'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
