<?php

namespace Tests\Feature\Cart;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartItemManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_add_an_available_variant_to_their_cart(): void
    {
        $variant = Product::query()->create([
            'brand_id' => Brand::query()->create(['name_ar' => 'اختبار', 'slug' => 'test-brand'])->id,
            'category_id' => Category::query()->create(['name_ar' => 'اختبار', 'slug' => 'test-category'])->id,
            'name_ar' => 'منتج اختبار', 'slug' => 'test-product',
        ])->createDefaultVariant('GLW-ADD-1', '10.000');
        $variant->update(['stock_quantity' => 3]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 2]);
    }
}
