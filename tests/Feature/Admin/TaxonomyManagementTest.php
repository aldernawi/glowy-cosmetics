<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxonomyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_cannot_make_a_category_a_child_of_its_own_descendant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $parent = Category::query()->create(['name_ar' => 'الأم', 'slug' => 'parent']);
        $child = Category::query()->create(['name_ar' => 'الابن', 'slug' => 'child', 'parent_id' => $parent->id]);

        $this->actingAs($admin)
            ->patch(route('admin.categories.update', $parent), ['name_ar' => 'الأم', 'slug' => 'parent', 'parent_id' => $child->id, 'is_active' => true])
            ->assertSessionHasErrors('parent_id');

        $this->assertNull($parent->refresh()->parent_id);
    }
}
