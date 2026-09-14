<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
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
}
