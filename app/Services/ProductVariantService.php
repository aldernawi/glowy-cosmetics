<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductVariantService
{
    public function __construct(private StockService $stock) {}

    /** @param array<string, mixed> $attributes */
    public function create(Product $product, array $attributes, ?User $actor = null): ProductVariant
    {
        return DB::transaction(function () use ($product, $attributes, $actor): ProductVariant {
            $variant = $product->variants()->create(Arr::except($attributes, ['stock_quantity']) + ['is_default' => false, 'stock_quantity' => 0]);
            $this->recordOpeningStock($variant, (int) ($attributes['stock_quantity'] ?? 0), $actor);

            return $variant;
        });
    }

    /**
     * Stock is changed by the difference the editor made, so sales that happen
     * while the form is open are never overwritten.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(ProductVariant $variant, array $attributes, ?int $stockQuantity = null, ?int $originalStockQuantity = null, ?User $actor = null): void
    {
        DB::transaction(function () use ($variant, $attributes, $stockQuantity, $originalStockQuantity, $actor): void {
            $lockedVariant = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $lockedVariant->update(Arr::except($attributes, ['stock_quantity', 'stock_quantity_original']));

            $delta = $stockQuantity === null ? 0 : $stockQuantity - ($originalStockQuantity ?? $lockedVariant->stock_quantity);

            if ($delta !== 0) {
                $this->stock->adjust($lockedVariant, $delta, StockMovementType::Adjustment, (string) Str::uuid(), $actor, 'variant-edit');
            }
        });
    }

    public function recordOpeningStock(ProductVariant $variant, int $quantity, ?User $actor = null): void
    {
        if ($quantity > 0) {
            $this->stock->adjust($variant, $quantity, StockMovementType::Opening, 'opening-'.$variant->id, $actor, 'opening-stock');
        }
    }

    /** Returns true when the variant was deleted, false when it was archived to keep its history. */
    public function deleteOrArchive(ProductVariant $variant): bool
    {
        return DB::transaction(function () use ($variant): bool {
            $lockedVariant = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            CartItem::query()->where('product_variant_id', $lockedVariant->id)->delete();

            if ($lockedVariant->hasHistory()) {
                $lockedVariant->update(['is_active' => false]);

                return false;
            }

            $lockedVariant->delete();

            return true;
        });
    }
}
