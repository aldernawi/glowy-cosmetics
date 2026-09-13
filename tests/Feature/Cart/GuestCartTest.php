<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cart_uses_a_hashed_random_token_and_secure_cookie_defaults(): void
    {
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertCookie(config('cart.cookie'));

        $cart = Cart::query()->sole();

        $this->assertNull($cart->user_id);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $cart->guest_token_hash);
        $this->assertFalse(config('cart.secure') && ! app()->environment('production'));
    }
}
