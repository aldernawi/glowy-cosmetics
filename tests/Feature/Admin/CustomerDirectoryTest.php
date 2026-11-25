<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_view_registered_customers(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $customer = User::factory()->create(['name' => 'عميلة Glowy']);

        $this->actingAs($admin)->get(route('admin.customers.index'))
            ->assertOk()->assertSee('عميلة Glowy');
    }

    public function test_the_directory_links_to_each_customer_profile(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $customer = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.customers.index'))->assertSee('href="'.route('admin.customers.show', $customer).'"', false);
        $this->actingAs($admin)->get(route('admin.customers.show', $customer))->assertOk();
    }
}
