<?php

namespace Tests\Feature\Cart;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_merging_a_guest_cart_caps_quantity_at_available_stock(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $variant = Product::query()->create([
            'brand_id' => Brand::query()->create(['name_ar' => 'اختبار', 'slug' => 'test-brand'])->id,
            'category_id' => Category::query()->create(['name_ar' => 'اختبار', 'slug' => 'test-category'])->id,
            'name_ar' => 'منتج اختبار',
            'slug' => 'test-product',
        ])->createDefaultVariant('GLW-CART-1', '10.000');
        $variant->update(['stock_quantity' => 5]);
        $token = bin2hex(random_bytes(32));
        $guestCart = Cart::query()->create(['guest_token_hash' => hash('sha256', $token)]);
        $guestCart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 4]);
        $user = User::factory()->create();
        $userCart = Cart::query()->create(['user_id' => $user->id]);
        $userCart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 3]);

        $result = app(CartService::class)->mergeGuestCartIntoUserCart($user, $token);

        $this->assertSame([$variant->id], $result->adjustedVariantIds);
        $this->assertSame(5, $userCart->refresh()->items()->sole()->quantity);
        $this->assertDatabaseMissing('carts', ['id' => $guestCart->id]);
    }
}
