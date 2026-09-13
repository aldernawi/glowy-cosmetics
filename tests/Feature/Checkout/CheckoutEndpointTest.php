<?php

namespace Tests\Feature\Checkout;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\DeliverySetting;
use App\Models\DeliveryTier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_uses_a_server_quote_and_ignores_browser_totals(): void
    {
        config(['services.google_routes.key' => 'server-key']);
        $variant = Product::query()->create(['brand_id' => Brand::query()->create(['name_ar' => 'ب', 'slug' => 'b'])->id, 'category_id' => Category::query()->create(['name_ar' => 'ت', 'slug' => 'c'])->id, 'name_ar' => 'كريم', 'slug' => 'cream'])->createDefaultVariant('CREAM-1', '20.000');
        $variant->update(['stock_quantity' => 2]);
        $user = User::factory()->create();
        $cart = Cart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);
        DeliverySetting::query()->create(['store_latitude' => '32.8872000', 'store_longitude' => '13.1913000']);
        DeliveryTier::query()->create(['min_distance_km' => '0.000', 'max_distance_km' => '20.000', 'fee' => '10.000', 'is_active' => true]);
        Http::fake(['routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 5000, 'duration' => '600s']]])]);

        $this->actingAs($user)->post(route('checkout.store'), ['customer_name' => 'عميلة', 'phone' => '0910000000', 'latitude' => '32.9000000', 'longitude' => '13.2000000', 'total' => '1.000', 'delivery_fee' => '0.000'])
            ->assertRedirect(route('checkout.index'));

        $this->assertDatabaseHas('orders', ['subtotal' => '20.000', 'delivery_fee' => '10.000', 'total' => '30.000']);
    }
}
