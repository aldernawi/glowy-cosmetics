<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cart_uses_a_hashed_random_token_and_secure_cookie_defaults(): void
    {
        $variant = Product::query()->create(['name_ar' => 'مرطب', 'slug' => 'guest-cart'])->createDefaultVariant('GUEST-1', '9.000');
        $variant->update(['stock_quantity' => 1]);

        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1])
            ->assertCookie(config('cart.cookie'));

        $cart = Cart::query()->sole();

        $this->assertNull($cart->user_id);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $cart->guest_token_hash);
        $this->assertFalse(config('cart.secure') && ! app()->environment('production'));
    }

    public function test_browsing_the_cart_or_checkout_does_not_create_carts(): void
    {
        $this->get(route('cart.index'))->assertOk()->assertSee('سلتك فارغة الآن')->assertCookieMissing(config('cart.cookie'));
        $this->get(route('checkout.index'))->assertRedirect(route('cart.index'));
        $this->post(route('checkout.store'), ['customer_name' => 'عميلة', 'phone' => '0910000000', 'latitude' => '32.9', 'longitude' => '13.2', 'location_confirmed' => '1'])->assertRedirect(route('cart.index'));

        $this->assertDatabaseCount('carts', 0);
    }
}
