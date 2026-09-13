<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_featured' => 'boolean', 'is_new' => 'boolean'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function skinTypes(): BelongsToMany
    {
        return $this->belongsToMany(SkinType::class);
    }

    public function concerns(): BelongsToMany
    {
        return $this->belongsToMany(Concern::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class);
    }

    public function createDefaultVariant(string $sku, string $price): ProductVariant
    {
        return $this->variants()->create([
            'sku' => $sku,
            'price' => $price,
            'stock_quantity' => 0,
            'is_default' => true,
            'is_active' => true,
        ]);
    }
}
