<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSaleItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:3', 'catalog_price' => 'decimal:3', 'subtotal' => 'decimal:3', 'quantity' => 'integer', 'stock_deducted' => 'integer'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function shortages(): HasMany
    {
        return $this->hasMany(PosStockShortage::class);
    }

    /** The device may have sold at a price that changed on the server while it was offline. */
    public function hasPriceDifference(): bool
    {
        return $this->catalog_price !== null && $this->catalog_price !== $this->unit_price;
    }
}
