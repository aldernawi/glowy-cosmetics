<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_an_administrator_can_create_an_arabic_brand_without_manually_entering_a_slug(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');

        $this->actingAs($administrator)
            ->post(route('admin.brands.store'), ['name_ar' => 'لاروش بوزيه', 'is_active' => true])
            ->assertRedirect(route('admin.brands.index'));

        $this->assertDatabaseHas('brands', ['name_ar' => 'لاروش بوزيه']);
    }

    public function test_an_administrator_can_create_a_brand_with_a_logo(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');

        $this->actingAs($administrator)->post(route('admin.brands.store'), [
            'name_ar' => 'ماركة بالشعار',
            'is_active' => true,
            'logo' => new UploadedFile(public_path('images/glowy-logo.png'), 'brand.png', 'image/png', null, true),
        ])->assertRedirect(route('admin.brands.index'));

        $path = Brand::query()->where('name_ar', 'ماركة بالشعار')->value('logo_path');
        $this->assertNotEmpty($path);
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_an_extremely_wide_brand_logo_is_stored_without_gd_processing(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');
        $temporaryPath = tempnam(sys_get_temp_dir(), 'glowy-brand-logo-');
        $image = imagecreatetruecolor(10000, 1);
        imagepng($image, $temporaryPath);
        imagedestroy($image);

        try {
            $this->actingAs($administrator)->post(route('admin.brands.store'), [
                'name_ar' => 'شعار عريض جدًا',
                'is_active' => true,
                'logo' => new UploadedFile($temporaryPath, 'wide-brand.png', 'image/png', null, true),
            ])->assertRedirect(route('admin.brands.index'));

            $path = Brand::query()->where('name_ar', 'شعار عريض جدًا')->value('logo_path');
            $this->assertNotEmpty($path);
            Storage::disk('public')->assertExists($path);
        } finally {
            @unlink($temporaryPath);
        }
    }

    public function test_replacing_a_brand_logo_keeps_the_new_file_and_removes_the_old_one(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brands/old.png', 'old');
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');
        $brand = Brand::query()->create(['name_ar' => 'ماركة', 'slug' => 'logo-brand', 'logo_path' => 'brands/old.png']);

        $this->actingAs($administrator)->patch(route('admin.brands.update', $brand), [
            'name_ar' => 'ماركة', 'slug' => 'logo-brand', 'is_active' => 1, 'logo' => UploadedFile::fake()->image('new.png'),
        ])->assertRedirect(route('admin.brands.index'));

        Storage::disk('public')->assertExists($brand->refresh()->logo_path);
        Storage::disk('public')->assertMissing('brands/old.png');
    }

    public function test_replacing_a_brand_logo_keeps_a_file_shared_with_another_brand(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brands/shared.png', 'shared');
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');
        $brand = Brand::query()->create(['name_ar' => 'أولى', 'slug' => 'first-shared', 'logo_path' => 'brands/shared.png']);
        Brand::query()->create(['name_ar' => 'ثانية', 'slug' => 'second-shared', 'logo_path' => 'brands/shared.png']);

        $this->actingAs($administrator)->patch(route('admin.brands.update', $brand), [
            'name_ar' => 'أولى', 'slug' => 'first-shared', 'is_active' => 1, 'logo' => UploadedFile::fake()->image('new.png'),
        ])->assertRedirect();

        Storage::disk('public')->assertExists('brands/shared.png');
    }
}
