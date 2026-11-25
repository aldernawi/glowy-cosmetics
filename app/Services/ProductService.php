<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(private ProductImageService $images, private ProductVariantService $variants) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, UploadedFile>  $files
     */
    public function createWithDefaultVariant(array $attributes, array $files = [], ?User $actor = null): Product
    {
        $storedPaths = collect();
        try {
            return DB::transaction(function () use ($attributes, $files, $storedPaths, $actor): Product {
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

                $variant = $product->createDefaultVariant($attributes['sku'], $attributes['price']);
                $variant->update([
                    'barcode' => $attributes['barcode'] ?? null,
                    'name' => $attributes['default_variant_name'] ?? null,
                    'size' => $attributes['default_variant_size'] ?? null,
                    'compare_at_price' => $attributes['compare_at_price'] ?? null,
                    'low_stock_threshold' => $attributes['low_stock_threshold'] ?? 5,
                ]);
                $this->variants->recordOpeningStock($variant, (int) ($attributes['stock_quantity'] ?? 0), $actor);
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

    /**
     * Returns the image paths of a deleted product, or null when it was archived to keep its history.
     *
     * @return Collection<int, string>|null
     */
    public function deleteOrArchive(Product $product): ?Collection
    {
        return DB::transaction(function () use ($product): ?Collection {
            $variants = ProductVariant::query()->where('product_id', $product->id)->lockForUpdate()->get();
            CartItem::query()->whereIn('product_variant_id', $variants->modelKeys())->delete();

            if ($variants->contains(fn (ProductVariant $variant) => $variant->hasHistory())) {
                $product->update(['is_active' => false]);
                $product->variants()->update(['is_active' => false]);

                return null;
            }

            $paths = $product->images()->pluck('path')->unique();
            $product->delete();

            return $paths;
        });
    }

    /** @param array<string, mixed> $attributes */
    private function syncTaxonomies(Product $product, array $attributes): void
    {
        $product->skinTypes()->sync($attributes['skin_type_ids'] ?? []);
        $product->concerns()->sync($attributes['concern_ids'] ?? []);
        $product->ingredients()->sync($attributes['ingredient_ids'] ?? []);
    }
}
