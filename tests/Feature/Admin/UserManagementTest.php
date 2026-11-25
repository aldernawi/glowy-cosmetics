<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\AdminRoleService;
use Database\Seeders\RolesAndPermissionsSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_a_catalog_manager_account(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');

        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'مديرة الكتالوج', 'email' => 'catalog@example.test', 'password' => 'password123', 'role' => 'catalog-manager'])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue(User::query()->where('email', 'catalog@example.test')->firstOrFail()->hasRole('catalog-manager'));
    }

    public function test_an_administrator_can_change_and_revoke_a_staff_role_from_the_users_page(): void
    {
        $admin = $this->administrator();
        $staff = User::factory()->create();
        $staff->assignRole('catalog-manager');

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('action="'.route('admin.users.update', $staff).'"', false);
        $this->actingAs($admin)->patch(route('admin.users.update', $staff), ['role' => 'administrator'])->assertSessionHasNoErrors();
        $this->assertTrue($staff->fresh()->can('admin.access'));
        $this->actingAs($admin)->patch(route('admin.users.update', $staff), ['role' => ''])->assertSessionHasNoErrors();
        $this->assertCount(0, $staff->fresh()->roles);
        $this->actingAs($staff->fresh())->get(route('admin.products.index'))->assertForbidden();
    }

    public function test_user_managers_cannot_escalate_privileges(): void
    {
        $admin = $this->administrator();
        $manager = User::factory()->create();
        $manager->givePermissionTo('users.manage');
        $staff = User::factory()->create();

        $this->actingAs($manager)->patch(route('admin.users.update', $manager), ['role' => 'administrator'])->assertForbidden();
        $this->actingAs($manager)->patch(route('admin.users.update', $admin), ['role' => ''])->assertForbidden();
        $this->actingAs($manager)->patch(route('admin.users.update', $staff), ['role' => 'administrator'])->assertSessionHasErrors('role');
        $this->actingAs($manager)->post(route('admin.users.store'), ['name' => 'دخيل', 'email' => 'x@example.test', 'password' => 'password123', 'role' => 'administrator'])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'x@example.test']);

        $this->actingAs($manager)->patch(route('admin.users.update', $staff), ['role' => 'catalog-manager'])->assertSessionHasNoErrors();
        $this->assertTrue($staff->fresh()->hasRole('catalog-manager'));
        $this->assertTrue($admin->fresh()->hasRole('administrator'));
        $this->assertFalse($manager->fresh()->can('admin.access'));
    }

    public function test_an_administrator_cannot_demote_themselves(): void
    {
        $admin = $this->administrator();
        $this->administrator();

        $this->actingAs($admin)->patch(route('admin.users.update', $admin), ['role' => ''])->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->hasRole('administrator'));
    }

    public function test_the_last_administrator_cannot_be_demoted(): void
    {
        $admin = $this->administrator();

        $this->expectException(DomainException::class);
        app(AdminRoleService::class)->assign($admin, null);
    }

    public function test_the_last_administrator_cannot_delete_their_account_but_others_can(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrorsIn('userDeletion', 'password');
        $this->assertModelExists($admin);
        $this->assertAuthenticatedAs($admin);

        $this->administrator();
        $this->actingAs($admin)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');
        $this->assertModelMissing($admin);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => $admin->email]);
    }

    private function administrator(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        return $admin;
    }
}
