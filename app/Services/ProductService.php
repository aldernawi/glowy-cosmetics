<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /** @param array<string, mixed> $attributes */
    public function createWithDefaultVariant(array $attributes): Product
    {
        return DB::transaction(function () use ($attributes): Product {
            $product = Product::query()->create([
                'brand_id' => $attributes['brand_id'] ?? null,
                'category_id' => $attributes['category_id'] ?? null,
                'name_ar' => $attributes['name_ar'],
                'name_en' => $attributes['name_en'] ?? null,
                'slug' => $attributes['slug'],
                'description' => $attributes['description'] ?? null,
                'is_active' => $attributes['is_active'] ?? true,
                'is_featured' => $attributes['is_featured'] ?? false,
                'is_new' => $attributes['is_new'] ?? false,
            ]);

            $product->createDefaultVariant($attributes['sku'], $attributes['price']);

            return $product;
        });
    }
}
