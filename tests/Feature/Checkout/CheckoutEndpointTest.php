<?php

namespace Tests\Feature\Checkout;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\DeliverySetting;
use App\Models\DeliveryTier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_uses_a_server_quote_and_ignores_browser_totals(): void
    {
        config(['services.openrouteservice.key' => 'server-key']);
        $variant = Product::query()->create(['brand_id' => Brand::query()->create(['name_ar' => 'ب', 'slug' => 'b'])->id, 'category_id' => Category::query()->create(['name_ar' => 'ت', 'slug' => 'c'])->id, 'name_ar' => 'كريم', 'slug' => 'cream'])->createDefaultVariant('CREAM-1', '20.000');
        $variant->update(['stock_quantity' => 2]);
        $user = User::factory()->create();
        $cart = Cart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);
        DeliverySetting::query()->create(['store_latitude' => '32.8872000', 'store_longitude' => '13.1913000']);
        DeliveryTier::query()->create(['min_distance_km' => '0.000', 'max_distance_km' => '20.000', 'fee' => '10.000', 'is_active' => true]);
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::response(['routes' => [['summary' => ['distance' => 5000, 'duration' => 600]]]])]);

        $this->actingAs($user)->post(route('checkout.store'), ['customer_name' => 'عميلة', 'phone' => '0910000000', 'latitude' => '32.9000000', 'longitude' => '13.2000000', 'location_confirmed' => '1', 'total' => '1.000', 'delivery_fee' => '0.000'])
            ->assertRedirect(route('checkout.success'));

        $this->assertDatabaseHas('orders', ['subtotal' => '20.000', 'delivery_fee' => '10.000', 'total' => '30.000']);
    }

    public function test_checkout_requires_an_explicitly_confirmed_delivery_location_and_exposes_the_saved_origin(): void
    {
        $user = User::factory()->create();
        $variant = Product::query()->create(['name_ar' => 'منظف', 'slug' => 'cleanser-confirmation'])->createDefaultVariant('CONFIRM-1', '8.000');
        $variant->update(['stock_quantity' => 1]);
        $cart = Cart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);
        DeliverySetting::query()->create(['store_latitude' => '32.1000000', 'store_longitude' => '13.1000000', 'store_formatted_address' => 'مخزن Glowy']);

        $this->actingAs($user)->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('data-origin-latitude="32.1000000"', false)
            ->assertSee('data-map-provider="maplibre"', false)
            ->assertSee('مخزن Glowy');

        $this->actingAs($user)->post(route('checkout.store'), [
            'customer_name' => 'عميلة', 'phone' => '0910000000', 'latitude' => '32.9000000', 'longitude' => '13.2000000',
        ])->assertSessionHasErrors('location_confirmed');
    }

    public function test_checkout_rejects_a_location_outside_the_delivery_range(): void
    {
        [$user, $variant] = $this->readyCart();
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::response(['routes' => [['summary' => ['distance' => 900000, 'duration' => 30000]]]])]);
        $location = ['latitude' => '30.0000000', 'longitude' => '13.2000000'];

        $this->actingAs($user)->postJson(route('checkout.quote'), $location)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'موقعك خارج نطاق التوصيل الحالي. نأسف لذلك، ويسعدنا تواصلك معنا لنساعدك.');

        $this->actingAs($user)->from(route('checkout.index'))
            ->post(route('checkout.store'), $location + ['customer_name' => 'عميلة', 'phone' => '0910000000', 'location_confirmed' => '1'])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHasErrors(['checkout' => 'موقعك خارج نطاق التوصيل الحالي. نأسف لذلك، ويسعدنا تواصلك معنا لنساعدك.']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $variant->refresh()->stock_quantity);
    }

    public function test_checkout_fails_gracefully_when_the_route_service_is_unreachable(): void
    {
        [$user, $variant] = $this->readyCart();
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $location = ['latitude' => '32.9000000', 'longitude' => '13.2000000'];

        $this->actingAs($user)->postJson(route('checkout.quote'), $location)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'تعذر حساب رسوم التوصيل لهذا الموقع.');

        $this->actingAs($user)->from(route('checkout.index'))
            ->post(route('checkout.store'), $location + ['customer_name' => 'عميلة', 'phone' => '0910000000', 'location_confirmed' => '1'])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHasErrors('checkout');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $variant->refresh()->stock_quantity);
    }

    /** @return array{User, ProductVariant} */
    private function readyCart(): array
    {
        config(['services.openrouteservice.key' => 'server-key']);
        $variant = Product::query()->create(['name_ar' => 'كريم', 'slug' => 'ready-cream'])->createDefaultVariant('READY-1', '20.000');
        $variant->update(['stock_quantity' => 2]);
        $user = User::factory()->create();
        Cart::query()->create(['user_id' => $user->id])->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);
        DeliverySetting::query()->create(['store_latitude' => '32.8872000', 'store_longitude' => '13.1913000']);
        DeliveryTier::query()->create(['min_distance_km' => '0.000', 'max_distance_km' => '20.000', 'fee' => '10.000', 'is_active' => true]);

        return [$user, $variant];
    }
}
