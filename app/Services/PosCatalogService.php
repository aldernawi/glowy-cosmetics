<?php

namespace App\Services;

use App\Models\ProductVariant;

class PosCatalogService
{
    /** @return array<int, array<string, mixed>> */
    public function variants(): array
    {
        return ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($product) => $product->where('is_active', true))
            ->with(['product.brand', 'product.category', 'product.images'])
            ->orderBy('id')
            ->get()
            ->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'name' => $variant->product->name_ar,
                'name_en' => $variant->product->name_en,
                'variant' => $variant->name ?: $variant->size,
                'brand' => $variant->product->brand?->name_ar,
                'category' => $variant->product->category?->name_ar,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'price' => $variant->price,
                'stock' => $variant->stock_quantity,
                'image' => $variant->product->images->first()?->url,
            ])
            ->all();
    }
}
