<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_default' => 'boolean', 'stock_quantity' => 'integer', 'low_stock_threshold' => 'integer', 'price' => 'decimal:3', 'compare_at_price' => 'decimal:3'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Orders or stock activity beyond the opening balance must survive catalog clean-up. */
    public function hasHistory(): bool
    {
        return $this->orderItems()->exists()
            || $this->stockMovements()->where('type', '!=', StockMovementType::Opening->value)->exists();
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null
            && BigDecimal::of($this->compare_at_price)->isGreaterThan(BigDecimal::of($this->price));
    }
}
