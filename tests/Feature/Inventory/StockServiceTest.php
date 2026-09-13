<?php

namespace Tests\Feature\Inventory;

use App\Enums\StockMovementType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stock_adjustment_is_applied_only_once_for_the_same_idempotency_key(): void
    {
        $variant = ProductVariant::query()->create([
            'product_id' => Product::query()->create([
                'brand_id' => Brand::query()->create(['name_ar' => 'اختبار', 'slug' => 'test-brand'])->id,
                'category_id' => Category::query()->create(['name_ar' => 'اختبار', 'slug' => 'test-category'])->id,
                'name_ar' => 'منتج اختبار',
                'slug' => 'test-product',
            ])->id,
            'sku' => 'GLW-TEST-001',
            'price' => '10.000',
            'stock_quantity' => 5,
        ]);

        $service = app(StockService::class);
        $first = $service->adjust($variant, -2, StockMovementType::OrderSale, 'order:1001:sale');
        $second = $service->adjust($variant, -2, StockMovementType::OrderSale, 'order:1001:sale');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(3, $variant->refresh()->stock_quantity);
    }
}
