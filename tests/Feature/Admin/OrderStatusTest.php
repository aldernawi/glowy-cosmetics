<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_an_order_restocks_only_once(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $variant = Product::query()->create(['brand_id' => Brand::query()->create(['name_ar' => 'ب', 'slug' => 'b'])->id, 'category_id' => Category::query()->create(['name_ar' => 'ت', 'slug' => 'c'])->id, 'name_ar' => 'م', 'slug' => 'p'])->createDefaultVariant('X', '10.000');
        $variant->update(['stock_quantity' => 0]);
        $order = Order::query()->create(['number' => 'GLW-X', 'status' => OrderStatus::Pending, 'customer_name' => 'ع', 'phone' => '09', 'delivery_latitude' => '1.0000000', 'delivery_longitude' => '1.0000000', 'delivery_distance_km' => '1.000', 'delivery_fee' => '1.000', 'store_latitude_snapshot' => '1.0000000', 'store_longitude_snapshot' => '1.0000000', 'subtotal' => '10.000', 'discount' => '0.000', 'total' => '11.000']);
        $order->items()->create(['product_variant_id' => $variant->id, 'product_name' => 'م', 'sku' => 'X', 'unit_price' => '10.000', 'quantity' => 2, 'subtotal' => '20.000']);
        $service = app(OrderService::class);
        $service->transition($order, OrderStatus::Cancelled, $admin);
        $service->transition($order, OrderStatus::Cancelled, $admin);
        $this->assertSame(2, $variant->refresh()->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_an_administrator_can_update_an_order_status_from_the_dashboard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');
        $order = Order::query()->create(['number' => 'GLW-HTTP', 'status' => OrderStatus::Pending, 'customer_name' => 'عميلة', 'phone' => '09', 'delivery_latitude' => '1.0000000', 'delivery_longitude' => '1.0000000', 'delivery_distance_km' => '1.000', 'delivery_fee' => '1.000', 'store_latitude_snapshot' => '1.0000000', 'store_longitude_snapshot' => '1.0000000', 'subtotal' => '1.000', 'discount' => '0.000', 'total' => '2.000']);

        $this->actingAs($administrator)->patch(route('admin.orders.update', $order), ['status' => 'confirmed'])
            ->assertRedirect(route('admin.orders.index'));

        $this->assertSame(OrderStatus::Confirmed, $order->refresh()->status);
    }

    public function test_an_order_cannot_skip_its_lifecycle(): void
    {
        $order = Order::query()->create(['number' => 'GLW-RULE', 'status' => OrderStatus::Pending, 'customer_name' => 'عميلة', 'phone' => '09', 'delivery_latitude' => '1.0000000', 'delivery_longitude' => '1.0000000', 'delivery_distance_km' => '1.000', 'delivery_fee' => '1.000', 'store_latitude_snapshot' => '1.0000000', 'store_longitude_snapshot' => '1.0000000', 'subtotal' => '1.000', 'discount' => '0.000', 'total' => '2.000']);
        $this->expectException(DomainException::class);
        app(OrderService::class)->transition($order, OrderStatus::Delivered);
    }
}
