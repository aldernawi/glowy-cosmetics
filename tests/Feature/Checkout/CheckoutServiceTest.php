<?php

namespace Tests\Feature\Checkout;

use App\Data\DeliveryQuote;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\DeliverySetting;
use App\Models\DeliveryTier;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_snapshot_and_reduces_variant_stock(): void
    {
        $variant = Product::query()->create(['brand_id' => Brand::query()->create(['name_ar' => 'ب', 'slug' => 'b'])->id, 'category_id' => Category::query()->create(['name_ar' => 'ت', 'slug' => 'c'])->id, 'name_ar' => 'سيروم', 'slug' => 'serum'])->createDefaultVariant('SKU-1', '12.500');
        $variant->update(['stock_quantity' => 4]);
        $user = User::factory()->create();
        $cart = Cart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 2]);
        $setting = DeliverySetting::query()->create(['store_latitude' => '32.8872000', 'store_longitude' => '13.1913000', 'store_formatted_address' => 'مخزن Glowy']);
        $tier = DeliveryTier::query()->create(['min_distance_km' => '0.000', 'max_distance_km' => '10.000', 'fee' => '10.000', 'is_active' => true]);
        $quote = new DeliveryQuote('5.000', 600, '10.000', $tier->id, $setting->id);

        $order = app(CheckoutService::class)->create($cart, $user, $quote, ['customer_name' => 'عميلة', 'phone' => '0910000000', 'secondary_phone' => '0920000000', 'city_region' => 'طرابلس - الأندلس', 'address' => 'حي الأندلس', 'landmark' => 'بجوار الصيدلية', 'place_id' => 'place-1', 'notes' => 'اتصلي قبل الوصول', 'latitude' => '32.9000000', 'longitude' => '13.2000000']);

        $this->assertSame('35.000', $order->total);
        $this->assertSame(2, $variant->refresh()->stock_quantity);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertSame('place-1', $order->delivery_place_id);
        $this->assertSame('طرابلس - الأندلس', $order->delivery_city_region);
        $this->assertSame('بجوار الصيدلية', $order->delivery_landmark);
        $this->assertSame(10, $order->delivery_duration_minutes);
        $this->assertSame((string) $setting->store_formatted_address, $order->store_address_snapshot);
    }

    public function test_checkout_rejects_a_variant_when_its_product_was_unpublished(): void
    {
        $product = Product::query()->create(['name_ar' => 'منتج مخفي', 'slug' => 'hidden-at-checkout', 'is_active' => false]);
        $variant = $product->createDefaultVariant('HIDDEN-CHECKOUT', '12.000');
        $variant->update(['stock_quantity' => 2]);
        $cart = Cart::query()->create();
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);
        $setting = DeliverySetting::query()->create(['store_latitude' => '32.8872000', 'store_longitude' => '13.1913000']);
        $tier = DeliveryTier::query()->create(['min_distance_km' => '0.000', 'max_distance_km' => '10.000', 'fee' => '10.000', 'is_active' => true]);

        $this->expectException(DomainException::class);
        app(CheckoutService::class)->create($cart, null, new DeliveryQuote('5.000', 600, '10.000', $tier->id, $setting->id), [
            'customer_name' => 'عميلة', 'phone' => '0910000000', 'latitude' => '32.9000000', 'longitude' => '13.2000000',
        ]);
    }
}
