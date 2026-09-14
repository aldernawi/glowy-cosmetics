<?php

namespace Tests\Feature\Admin;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_low_stock_variants_and_recent_movements(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'واقي', 'slug' => 'sunscreen']);
        $variant = ProductVariant::query()->create(['product_id' => $product->id, 'sku' => 'SUN-1', 'price' => '20.000', 'stock_quantity' => 2, 'low_stock_threshold' => 5]);
        StockMovement::query()->create(['product_variant_id' => $variant->id, 'user_id' => $admin->id, 'type' => StockMovementType::Opening, 'delta' => 2, 'quantity_before' => 0, 'quantity_after' => 2, 'reference' => 'opening', 'idempotency_key' => 'inventory-workspace-opening']);

        $this->actingAs($admin)->get(route('admin.stock.low.index'))
            ->assertOk()->assertSee('SUN-1')->assertSee('منخفض المخزون');
        $this->actingAs($admin)->get(route('admin.stock.movements.index'))
            ->assertOk()->assertSee('SUN-1')->assertSee('رصيد افتتاحي');
    }
}
