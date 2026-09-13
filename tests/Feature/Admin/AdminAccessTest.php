<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
