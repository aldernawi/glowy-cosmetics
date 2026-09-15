<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_upload_a_product_image(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'غسول', 'slug' => 'cleanser']);
        $file = UploadedFile::fake()->image('cleanser.webp');

        $this->actingAs($admin)->post(route('admin.products.images.store', $product), ['image' => $file, 'alt' => 'غسول لطيف'])
            ->assertRedirect(route('admin.products.index'));

        $path = $product->images()->sole()->path;
        Storage::disk('public')->assertExists($path);
    }

    public function test_an_administrator_can_remove_a_product_image_and_its_file(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'غسول', 'slug' => 'cleanser']);
        $image = $product->images()->create(['path' => 'products/1/old.webp']);
        Storage::disk('public')->put($image->path, 'image');

        $this->actingAs($admin)->delete(route('admin.images.destroy', $image))->assertRedirect(route('admin.products.index'));
        $this->assertModelMissing($image);
        Storage::disk('public')->assertMissing('products/1/old.webp');
    }

    public function test_primary_image_is_promoted_when_the_primary_image_is_deleted(): void
    {
        Storage::fake('public'); $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(); $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'غسول', 'slug' => 'cleanser']);
        $primary = $product->images()->create(['path' => 'products/1/a.webp', 'is_primary' => true]);
        $other = $product->images()->create(['path' => 'products/1/b.webp', 'sort_order' => 1]);
        $this->actingAs($admin)->delete(route('admin.images.destroy', $primary));
        $this->assertTrue($other->refresh()->is_primary);
    }
}
