<?php

namespace Tests\Feature\Admin;

use App\Enums\StockMovementType;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_add_an_active_sellable_variant_to_a_product(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'مرطب', 'slug' => 'moisturizer']);

        $this->actingAs($admin)->post(route('admin.products.variants.store', $product), ['sku' => 'MOIST-50', 'name' => '50ml', 'price' => '18.500', 'stock_quantity' => 4, 'low_stock_threshold' => 2, 'is_active' => true])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('product_variants', ['product_id' => $product->id, 'sku' => 'MOIST-50', 'price' => '18.500', 'stock_quantity' => 4]);
    }

    public function test_an_administrator_can_update_and_remove_a_non_default_variant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'مرطب', 'slug' => 'moisturizer']);
        $variant = $product->variants()->create(['sku' => 'MOIST-100', 'price' => '20.000', 'stock_quantity' => 1]);

        $this->actingAs($admin)->patch(route('admin.variants.update', $variant), ['sku' => 'MOIST-100', 'price' => '22.000', 'stock_quantity' => 3, 'low_stock_threshold' => 2, 'is_active' => true])->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'price' => '22.000', 'stock_quantity' => 3]);
        $unused = $product->variants()->create(['sku' => 'MOIST-UNUSED', 'price' => '20.000']);
        $this->actingAs($admin)->delete(route('admin.variants.destroy', $unused))->assertRedirect(route('admin.products.index'));
        $this->assertModelMissing($unused);
    }

    public function test_stock_entered_with_a_product_or_variant_is_recorded_as_opening_stock(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin)->post(route('admin.products.store'), ['name_ar' => 'غسول', 'slug' => 'opening', 'sku' => 'OPEN-1', 'price' => '5.000', 'stock_quantity' => 50])->assertSessionHasNoErrors();
        $product = Product::query()->where('slug', 'opening')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.products.variants.store', $product), ['sku' => 'OPEN-2', 'price' => '9.000', 'stock_quantity' => 7, 'low_stock_threshold' => 2, 'is_active' => true])->assertSessionHasNoErrors();

        foreach (['OPEN-1' => 50, 'OPEN-2' => 7] as $sku => $quantity) {
            $variant = ProductVariant::query()->where('sku', $sku)->firstOrFail();
            $this->assertSame($quantity, $variant->stock_quantity);
            $this->assertDatabaseHas('stock_movements', ['product_variant_id' => $variant->id, 'type' => 'opening', 'delta' => $quantity, 'quantity_before' => 0, 'quantity_after' => $quantity, 'user_id' => $admin->id]);
        }
    }

    public function test_editing_stock_records_a_movement_without_overwriting_sales_made_meanwhile(): void
    {
        $admin = $this->administrator();
        $variant = $this->variant(10);
        app(StockService::class)->adjust($variant, -4, StockMovementType::OrderSale, 'sale-while-editing');

        $this->actingAs($admin)->patch(route('admin.variants.update', $variant), $this->payload($variant, ['stock_quantity' => 12, 'stock_quantity_original' => 10]))->assertSessionHasNoErrors();

        $this->assertSame(8, $variant->refresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_variant_id' => $variant->id, 'type' => 'adjustment', 'delta' => 2, 'user_id' => $admin->id]);

        $this->actingAs($admin)->patch(route('admin.variants.update', $variant), $this->payload($variant, ['stock_quantity' => 8, 'stock_quantity_original' => 8]));
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_an_impossible_stock_edit_saves_nothing(): void
    {
        $admin = $this->administrator();
        $variant = $this->variant(1);

        $this->actingAs($admin)->patch(route('admin.variants.update', $variant), $this->payload($variant, ['price' => '99.000', 'stock_quantity' => 0, 'stock_quantity_original' => 5]))->assertSessionHasErrors('stock_quantity');

        $this->assertSame(1, $variant->refresh()->stock_quantity);
        $this->assertSame('20.000', $variant->price);
    }

    public function test_staff_without_inventory_permission_cannot_change_stock_from_the_variant_form(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $manager = User::factory()->create();
        $manager->assignRole('catalog-manager');
        $variant = $this->variant(3);

        $this->actingAs($manager)->patch(route('admin.variants.update', $variant), $this->payload($variant, ['price' => '25.000', 'stock_quantity' => 900]))->assertSessionHasNoErrors();

        $this->assertSame(3, $variant->refresh()->stock_quantity);
        $this->assertSame('25.000', $variant->price);
    }

    public function test_a_variant_with_orders_is_archived_and_removed_from_carts(): void
    {
        $admin = $this->administrator();
        $variant = $this->variant(3);
        $this->orderFor($variant);
        $cart = Cart::query()->create(['guest_token_hash' => 'guest']);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->actingAs($admin)->delete(route('admin.variants.destroy', $variant))->assertRedirect(route('admin.products.index'));

        $this->assertFalse($variant->refresh()->is_active);
        $this->assertDatabaseHas('order_items', ['product_variant_id' => $variant->id]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_a_variant_with_stock_activity_is_archived_to_keep_its_ledger(): void
    {
        $admin = $this->administrator();
        $variant = $this->variant(3);
        app(StockService::class)->adjust($variant, 2, StockMovementType::ManualIn, 'ledger-entry');

        $this->actingAs($admin)->delete(route('admin.variants.destroy', $variant));

        $this->assertModelExists($variant);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_an_unsold_variant_or_product_in_a_cart_can_be_deleted(): void
    {
        $admin = $this->administrator();
        $variant = $this->variant(3);
        $cart = Cart::query()->create(['guest_token_hash' => 'guest']);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->actingAs($admin)->delete(route('admin.variants.destroy', $variant))->assertRedirect(route('admin.products.index'));
        $this->assertModelMissing($variant);

        $product = Product::query()->create(['name_ar' => 'منتج', 'slug' => 'in-cart']);
        $cart->items()->create(['product_variant_id' => $product->createDefaultVariant('IN-CART', '9.000')->id, 'quantity' => 1]);
        $this->actingAs($admin)->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'تم حذف المنتج.');
        $this->assertModelMissing($product);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_the_database_refuses_to_delete_a_variant_that_was_ordered(): void
    {
        $variant = $this->variant(3);
        $this->orderFor($variant);

        $this->expectException(QueryException::class);
        $variant->delete();
    }

    private function administrator(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');

        return $admin;
    }

    private function variant(int $stock): ProductVariant
    {
        return Product::query()->create(['name_ar' => 'مرطب', 'slug' => 'variant-product'])
            ->variants()->create(['sku' => 'VARIANT-1', 'price' => '20.000', 'stock_quantity' => $stock, 'is_active' => true]);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(ProductVariant $variant, array $overrides): array
    {
        return $overrides + ['sku' => $variant->sku, 'price' => '20.000', 'low_stock_threshold' => 2, 'is_active' => true];
    }

    private function orderFor(ProductVariant $variant): void
    {
        $order = Order::query()->create(['number' => 'GLW-VARIANT', 'status' => 'pending', 'customer_name' => 'عميلة', 'phone' => '09', 'delivery_latitude' => '1.0000000', 'delivery_longitude' => '1.0000000', 'delivery_distance_km' => '1.000', 'delivery_fee' => '0.000', 'store_latitude_snapshot' => '1.0000000', 'store_longitude_snapshot' => '1.0000000', 'subtotal' => '20.000', 'total' => '20.000']);
        $order->items()->create(['product_variant_id' => $variant->id, 'product_name' => 'مرطب', 'sku' => $variant->sku, 'unit_price' => '20.000', 'quantity' => 1, 'subtotal' => '20.000']);
    }
}
