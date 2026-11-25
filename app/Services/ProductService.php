<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private ProductImageService $images) {}

    /** @param array<int, UploadedFile> $files */
    public function createWithDefaultVariant(array $attributes, array $files = []): Product
    {
        $storedPaths = collect();
        try {
            return DB::transaction(function () use ($attributes, $files, $storedPaths): Product {
                $product = Product::query()->create([
                    'brand_id' => $attributes['brand_id'] ?? null,
                    'category_id' => $attributes['category_id'] ?? null,
                    'name_ar' => $attributes['name_ar'],
                    'name_en' => $attributes['name_en'] ?? null,
                    'slug' => $attributes['slug'],
                    'description' => $attributes['description'] ?? null,
                    'benefits' => $attributes['benefits'] ?? null,
                    'usage' => $attributes['usage'] ?? null,
                    'warnings' => $attributes['warnings'] ?? null,
                    'is_active' => $attributes['is_active'] ?? true,
                    'is_featured' => $attributes['is_featured'] ?? false,
                    'is_new' => $attributes['is_new'] ?? false,
                ]);

                $product->createDefaultVariant($attributes['sku'], $attributes['price'])->update([
                    'name' => $attributes['default_variant_name'] ?? null,
                    'size' => $attributes['default_variant_size'] ?? null,
                    'compare_at_price' => $attributes['compare_at_price'] ?? null,
                    'stock_quantity' => $attributes['stock_quantity'] ?? 0,
                    'low_stock_threshold' => $attributes['low_stock_threshold'] ?? 5,
                ]);
                $this->syncTaxonomies($product, $attributes);
                foreach ($files as $file) {
                    $storedPaths->push($this->images->store($product, $file, $product->name_ar)->path);
                }

                return $product;
            });
        } catch (\Throwable $exception) {
            $storedPaths->each(fn (string $path) => Storage::disk('public')->delete($path));
            throw $exception;
        }
    }

    /** @param array<string, mixed> $attributes */
    /** @param array<int, UploadedFile> $files */
    public function update(Product $product, array $attributes, array $files = []): Product
    {
        $storedPaths = collect();
        try {
            return DB::transaction(function () use ($product, $attributes, $files, $storedPaths): Product {
                $product->update([
                    'brand_id' => $attributes['brand_id'] ?? null,
                    'category_id' => $attributes['category_id'] ?? null,
                    'name_ar' => $attributes['name_ar'],
                    'name_en' => $attributes['name_en'] ?? null,
                    'slug' => $attributes['slug'],
                    'description' => $attributes['description'] ?? null,
                    'benefits' => $attributes['benefits'] ?? null,
                    'usage' => $attributes['usage'] ?? null,
                    'warnings' => $attributes['warnings'] ?? null,
                    'is_active' => $attributes['is_active'] ?? false,
                    'is_featured' => $attributes['is_featured'] ?? false,
                    'is_new' => $attributes['is_new'] ?? false,
                ]);
                $this->syncTaxonomies($product, $attributes);
                $product->variants()->where('is_default', true)->first()?->update([
                    'name' => $attributes['default_variant_name'] ?? null,
                    'size' => $attributes['default_variant_size'] ?? null,
                    'compare_at_price' => $attributes['compare_at_price'] ?? null,
                ]);
                foreach ($files as $file) {
                    $storedPaths->push($this->images->store($product, $file, $product->name_ar)->path);
                }

                return $product;
            });
        } catch (\Throwable $exception) {
            $storedPaths->each(fn (string $path) => Storage::disk('public')->delete($path));
            throw $exception;
        }
    }

    /** @return Collection<int, string> */
    public function deleteOrArchive(Product $product): Collection
    {
        if ($product->variants()->whereHas('orderItems')->exists()) {
            DB::transaction(function () use ($product): void {
                $product->update(['is_active' => false]);
                $product->variants()->update(['is_active' => false]);
            });

            return collect();
        }

        $paths = $product->images()->pluck('path')->unique();
        DB::transaction(fn () => $product->delete());

        return $paths;
    }

    /** @param array<string, mixed> $attributes */
    private function syncTaxonomies(Product $product, array $attributes): void
    {
        $product->skinTypes()->sync($attributes['skin_type_ids'] ?? []);
        $product->concerns()->sync($attributes['concern_ids'] ?? []);
        $product->ingredients()->sync($attributes['ingredient_ids'] ?? []);
    }
}
