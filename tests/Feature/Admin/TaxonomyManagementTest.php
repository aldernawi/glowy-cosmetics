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

    public function test_an_administrator_can_create_a_category_from_the_taxonomy_workspace(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), ['name_ar' => 'واقي الشمس', 'slug' => 'sun-care', 'is_active' => true])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name_ar' => 'واقي الشمس', 'slug' => 'sun-care', 'is_active' => true]);
    }

    public function test_an_administrator_can_create_a_skin_concern(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');

        $this->actingAs($admin)
            ->post(route('admin.concerns.store'), ['name_ar' => 'الجفاف', 'slug' => 'dryness', 'is_active' => true])
            ->assertRedirect(route('admin.concerns.index'));

        $this->assertDatabaseHas('concerns', ['name_ar' => 'الجفاف', 'slug' => 'dryness', 'is_active' => true]);
    }

    public function test_an_administrator_can_remove_an_empty_category(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $category = Category::query()->create(['name_ar' => 'الجسم', 'slug' => 'body']);

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))->assertRedirect(route('admin.categories.index'));
        $this->assertModelMissing($category);
    }
}
