<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_a_brand(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');

        $this->actingAs($administrator)
            ->post(route('admin.brands.store'), [
                'name_ar' => 'لاروش بوزيه',
                'slug' => 'la-roche-posay',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.brands.index'));

        $this->assertDatabaseHas('brands', ['slug' => 'la-roche-posay']);
    }
}
