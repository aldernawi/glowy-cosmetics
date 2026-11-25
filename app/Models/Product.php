<?php

namespace App\Models;

use App\Services\SitemapCacheService;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;
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

    protected static function booted(): void
    {
        static::saved(fn () => app(SitemapCacheService::class)->forget());
        static::deleted(fn () => app(SitemapCacheService::class)->forget());
    }

    public function scopeSellable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('variants', fn (Builder $variant) => $variant->where('is_active', true));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
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

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order');
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

    public function catalogVariant(): ?ProductVariant
    {
        return $this->variants
            ->where('is_active', true)
            ->sort(fn (ProductVariant $left, ProductVariant $right) => BigDecimal::of($left->price)->compareTo(BigDecimal::of($right->price)))
            ->first();
    }

    public function hasAvailableStock(): bool
    {
        return $this->variants->contains(fn (ProductVariant $variant) => $variant->is_active && $variant->stock_quantity > 0);
    }
}
