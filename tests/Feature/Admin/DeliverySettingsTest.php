<?php

namespace Tests\Feature\Admin;

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
}
