<?php

namespace Tests\Feature\Admin;

use App\Models\DeliveryTier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliverySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_save_an_origin_and_only_contiguous_tiers(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');

        $this->actingAs($administrator)->put(route('admin.delivery.origin.update'), [
            'store_latitude' => '32.8872000', 'store_longitude' => '13.1913000', 'store_formatted_address' => 'طرابلس',
        ])->assertRedirect(route('admin.delivery.index'));

        $this->post(route('admin.delivery.tiers.store'), ['min_distance_km' => '0.000', 'max_distance_km' => '10.000', 'fee' => '10.000'])
            ->assertRedirect(route('admin.delivery.index'));

        $this->post(route('admin.delivery.tiers.store'), ['min_distance_km' => '11.000', 'max_distance_km' => '20.000', 'fee' => '15.000'])
            ->assertSessionHasErrors('delivery_tier');

        $this->assertDatabaseCount('delivery_tiers', 1);
        $this->assertDatabaseHas('delivery_settings', ['store_formatted_address' => 'طرابلس']);
    }

    public function test_an_administrator_can_edit_or_deactivate_a_delivery_tier_without_creating_an_invalid_active_range(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');
        $tier = DeliveryTier::query()->create(['min_distance_km' => '0.000', 'max_distance_km' => '10.000', 'fee' => '10.000', 'is_active' => true]);

        $this->actingAs($administrator)->patch(route('admin.delivery.tiers.update', $tier), [
            'min_distance_km' => '0.000', 'max_distance_km' => '10.000', 'fee' => '12.500', 'is_active' => true,
        ])->assertRedirect(route('admin.delivery.index'));

        $this->assertDatabaseHas('delivery_tiers', ['id' => $tier->id, 'fee' => '12.500']);
    }

    public function test_the_delivery_origin_screen_exposes_an_explicit_current_location_control(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');

        $this->actingAs($administrator)->get(route('admin.delivery.index'))
            ->assertOk()
            ->assertSee('id="origin-use-location"', false)
            ->assertSee('id="origin-refresh-address"', false)
            ->assertSee('data-map-provider="maplibre"', false)
            ->assertSee('لا يُطلب موقع المتصفح إلا عند الضغط على الزر.');
    }
}
