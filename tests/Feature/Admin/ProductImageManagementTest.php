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
            ->assertRedirect(route('admin.products.edit', $product));

        $path = $product->images()->sole()->path;
        $this->assertStringEndsWith('.webp', $path);
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

        $this->actingAs($admin)->delete(route('admin.images.destroy', $image))->assertRedirect(route('admin.products.edit', $product));
        $this->assertModelMissing($image);
        Storage::disk('public')->assertMissing('products/1/old.webp');
    }

    public function test_primary_image_is_promoted_when_the_primary_image_is_deleted(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'غسول', 'slug' => 'cleanser']);
        $primary = $product->images()->create(['path' => 'products/1/a.webp', 'is_primary' => true]);
        $other = $product->images()->create(['path' => 'products/1/b.webp', 'sort_order' => 1]);
        $this->actingAs($admin)->delete(route('admin.images.destroy', $primary));
        $this->assertTrue($other->refresh()->is_primary);
    }

    public function test_deleting_a_product_removes_only_its_unreferenced_media_files(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'مرطب', 'slug' => 'moisturizer']);
        $image = $product->images()->create(['path' => 'products/1/moisturizer.webp']);
        Storage::disk('public')->put($image->path, 'image');

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        Storage::disk('public')->assertMissing('products/1/moisturizer.webp');
    }

    public function test_an_administrator_can_move_a_product_image_without_losing_primary_consistency(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'تونر', 'slug' => 'toner']);
        $first = $product->images()->create(['path' => 'products/1/one.webp', 'sort_order' => 0, 'is_primary' => true]);
        $second = $product->images()->create(['path' => 'products/1/two.webp', 'sort_order' => 1]);

        $this->actingAs($admin)->patch(route('admin.images.move', $second), ['direction' => 'up'])
            ->assertRedirect(route('admin.products.edit', $product));

        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
        $this->assertTrue($first->fresh()->is_primary);
    }

    public function test_a_file_that_only_pretends_to_be_an_image_is_rejected(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'كريم', 'slug' => 'fake-image']);

        // File::mimeType() is bypassed by UploadedFile::fake(), so a real temporary file is used.
        $path = tempnam(sys_get_temp_dir(), 'glowy');
        file_put_contents($path, '<html><script>alert(1)</script></html>');

        $this->actingAs($administrator)->post(route('admin.products.images.store', $product), ['images' => [new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true)]])
            ->assertSessionHasErrors('images.0');

        $this->assertDatabaseCount('product_images', 0);
        @unlink($path);
    }
}
