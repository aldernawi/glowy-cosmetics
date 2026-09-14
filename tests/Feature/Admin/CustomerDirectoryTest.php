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
}
