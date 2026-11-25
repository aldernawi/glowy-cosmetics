<?php

namespace Tests\Feature\Cart;

use App\Models\Brand;
use App\Models\Cart;
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

    public function test_cart_addition_returns_the_updated_count_for_an_ajax_request(): void
    {
        $variant = Product::query()->create(['name_ar' => 'مرطب فوري', 'slug' => 'instant-moisturizer'])
            ->createDefaultVariant('AJAX-CART-1', '11.000');
        $variant->update(['stock_quantity' => 3]);

        $this->postJson(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk()
            ->assertJsonPath('message', 'تمت إضافة المنتج إلى سلتك.')
            ->assertJsonPath('cart_count', 1);
    }

    public function test_cart_addition_returns_a_json_stock_error_for_an_ajax_request(): void
    {
        $variant = Product::query()->create(['name_ar' => 'منتج نافد', 'slug' => 'sold-out-product'])
            ->createDefaultVariant('AJAX-CART-OUT', '11.000');

        $this->postJson(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'الكمية المطلوبة غير متوفرة حاليًا.');
    }

    public function test_customer_can_update_and_remove_only_items_in_their_cart(): void
    {
        $variant = Product::query()->create(['name_ar' => 'مرطب', 'slug' => 'moisturizer'])
            ->createDefaultVariant('GLW-CART-UPDATE', '0.100');
        $variant->update(['stock_quantity' => 5]);
        $user = User::factory()->create();
        $cart = Cart::query()->create(['user_id' => $user->id]);
        $item = $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->actingAs($user)->patch(route('cart.items.update', $item), ['quantity' => 3])
            ->assertRedirect(route('cart.index'));
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 3]);

        $this->actingAs($user)->get(route('cart.index'))
            ->assertOk()->assertSee('0.300 د.ل');

        $this->actingAs($user)->delete(route('cart.items.destroy', $item))
            ->assertRedirect(route('cart.index'));
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_cart_update_rejects_quantities_above_current_stock(): void
    {
        $variant = Product::query()->create(['name_ar' => 'واقي شمس', 'slug' => 'sunscreen'])
            ->createDefaultVariant('GLW-CART-STOCK', '12.000');
        $variant->update(['stock_quantity' => 2]);
        $user = User::factory()->create();
        $item = Cart::query()->create(['user_id' => $user->id])->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->actingAs($user)->patch(route('cart.items.update', $item), ['quantity' => 3])
            ->assertRedirect(route('cart.index'))->assertSessionHasErrors('cart');
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 1]);
    }

    public function test_customer_cannot_change_an_item_owned_by_another_cart(): void
    {
        $variant = Product::query()->create(['name_ar' => 'منتج خاص', 'slug' => 'private-product'])->createDefaultVariant('PRIVATE-1', '8.000');
        $variant->update(['stock_quantity' => 4]);
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = Cart::query()->create(['user_id' => $owner->id])->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->actingAs($otherUser)->patch(route('cart.items.update', $item), ['quantity' => 2])->assertNotFound();
        $this->actingAs($otherUser)->delete(route('cart.items.destroy', $item))->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 1]);
    }

    public function test_active_variant_of_an_inactive_product_cannot_be_added(): void
    {
        $product = Product::query()->create(['name_ar' => 'منتج مخفي', 'slug' => 'hidden-product', 'is_active' => false]);
        $variant = $product->createDefaultVariant('HIDDEN-1', '9.000');
        $variant->update(['stock_quantity' => 4]);

        $this->actingAs(User::factory()->create())->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.index'))->assertSessionHasErrors('cart');

        $this->assertDatabaseMissing('cart_items', ['product_variant_id' => $variant->id]);
    }
}
