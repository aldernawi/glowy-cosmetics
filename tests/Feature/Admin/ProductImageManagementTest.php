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
}
