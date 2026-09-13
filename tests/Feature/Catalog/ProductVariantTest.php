<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_uses_an_internal_default_variant_as_its_sellable_item(): void
    {
        $product = Product::query()->create([
            'brand_id' => Brand::query()->create(['name_ar' => 'سيرافي', 'slug' => 'cerave'])->id,
            'category_id' => Category::query()->create(['name_ar' => 'غسولات', 'slug' => 'cleansers'])->id,
            'name_ar' => 'غسول لطيف',
            'slug' => 'gentle-cleanser',
        ]);

        $variant = $product->createDefaultVariant('GLW-CLN-001', '95.000');

        $this->assertTrue($variant->is_active);
        $this->assertSame('GLW-CLN-001', $variant->sku);
        $this->assertSame('95.000', $variant->price);
        $this->assertSame(0, $variant->stock_quantity);
        $this->assertTrue($product->variants->contains($variant));

        $image = $product->images()->create(['path' => 'products/cleanser.webp', 'is_primary' => true]);

        $this->assertTrue($image->is_primary);
    }
}
