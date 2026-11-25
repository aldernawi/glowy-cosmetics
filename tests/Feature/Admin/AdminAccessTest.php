<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_admin_dashboard_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_without_admin_access_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_administrators_can_view_the_dashboard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');

        $this->actingAs($administrator)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('لوحة التحكم');
    }

    public function test_catalog_manager_can_manage_catalog_but_cannot_access_operational_administration(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $manager = User::factory()->create();
        $manager->assignRole(Role::findByName('catalog-manager'));

        $this->actingAs($manager)->get(route('admin.products.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_a_granular_catalog_permission_does_not_grant_product_changes(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('products.view');

        $this->actingAs($viewer)->get(route('admin.products.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.products.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('admin.orders.index'))->assertForbidden();
    }
}
