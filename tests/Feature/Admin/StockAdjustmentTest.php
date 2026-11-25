<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_adjust_variant_stock(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');
        $variant = Product::query()->create([
            'brand_id' => Brand::query()->create(['name_ar' => 'اختبار', 'slug' => 'test-brand'])->id,
            'category_id' => Category::query()->create(['name_ar' => 'اختبار', 'slug' => 'test-category'])->id,
            'name_ar' => 'منتج اختبار', 'slug' => 'test-product',
        ])->createDefaultVariant('GLW-ADJ-1', '10.000');

        $this->actingAs($administrator)
            ->post(route('admin.stock.adjustments.store'), [
                'product_variant_id' => $variant->id,
                'delta' => 8,
                'type' => 'manual_in',
                'notes' => 'رصيد افتتاحي',
            ])
            ->assertRedirect(route('admin.stock.adjustments.index'));

        $this->assertSame(8, $variant->refresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_variant_id' => $variant->id, 'delta' => 8, 'type' => 'manual_in']);
    }

    public function test_an_adjustment_below_zero_is_rejected_with_a_message(): void
    {
        [$administrator, $variant] = $this->variantWithStock(2);

        $this->actingAs($administrator)->from(route('admin.stock.adjustments.index'))
            ->post(route('admin.stock.adjustments.store'), ['product_variant_id' => $variant->id, 'delta' => -5, 'type' => 'damage'])
            ->assertRedirect(route('admin.stock.adjustments.index'))
            ->assertSessionHasErrors(['delta' => 'لا يمكن تنفيذ الحركة لأن الرصيد المتاح لا يكفي.']);

        $this->assertSame(2, $variant->refresh()->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_the_movement_type_must_match_the_direction_of_the_change(): void
    {
        [$administrator, $variant] = $this->variantWithStock(5);

        foreach ([['damage', 3], ['manual_out', 3], ['manual_in', -3], ['opening', -3]] as [$type, $delta]) {
            $this->actingAs($administrator)
                ->post(route('admin.stock.adjustments.store'), ['product_variant_id' => $variant->id, 'delta' => $delta, 'type' => $type])
                ->assertSessionHasErrors('delta');
        }

        $this->assertSame(5, $variant->refresh()->stock_quantity);
        $this->actingAs($administrator)
            ->post(route('admin.stock.adjustments.store'), ['product_variant_id' => $variant->id, 'delta' => -2, 'type' => 'damage'])
            ->assertSessionHasNoErrors();
        $this->assertSame(3, $variant->refresh()->stock_quantity);
    }

    /** @return array{User, ProductVariant} */
    private function variantWithStock(int $quantity): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');
        $variant = Product::query()->create(['name_ar' => 'منتج', 'slug' => 'stocked'])->createDefaultVariant('STOCKED-1', '10.000');
        $variant->update(['stock_quantity' => $quantity]);

        return [$administrator, $variant];
    }
}
