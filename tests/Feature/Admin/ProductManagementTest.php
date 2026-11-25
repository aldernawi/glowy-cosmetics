<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\SkinType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_a_product_with_its_default_variant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');

        $this->actingAs($administrator)
            ->post(route('admin.products.store'), [
                'brand_id' => Brand::query()->create(['name_ar' => 'سيرافي', 'slug' => 'cerave'])->id,
                'category_id' => Category::query()->create(['name_ar' => 'غسولات', 'slug' => 'cleansers'])->id,
                'name_ar' => 'غسول لطيف',
                'slug' => 'gentle-cleanser',
                'sku' => 'GLW-CLN-100',
                'price' => '95.000',
            ])
            ->assertRedirect(route('admin.products.edit', Product::query()->where('slug', 'gentle-cleanser')->firstOrFail()));

        $this->assertDatabaseHas('products', ['slug' => 'gentle-cleanser']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'GLW-CLN-100', 'price' => '95.000', 'is_default' => true]);
    }

    public function test_an_administrator_can_create_a_product_with_images_from_the_same_form(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');

        $this->actingAs($administrator)->post(route('admin.products.store'), [
            'name_ar' => 'غسول يومي',
            'sku' => 'GLW-CLN-IMAGE',
            'price' => '90.000',
            'images' => [new UploadedFile(public_path('images/glowy-logo.png'), 'cleanser.png', 'image/png', null, true)],
        ])->assertRedirect();

        $product = Product::query()->where('name_ar', 'غسول يومي')->firstOrFail();
        $image = $product->images()->firstOrFail();
        $this->assertTrue($image->is_primary);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_an_administrator_can_update_full_product_content_and_taxonomy_associations(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'قديم', 'slug' => 'old-full-product']);
        $product->createDefaultVariant('GLW-OLD-001', '110.000');
        $skinType = SkinType::query()->create(['name_ar' => 'حساسة', 'slug' => 'sensitive']);
        $concern = Concern::query()->create(['name_ar' => 'ترطيب', 'slug' => 'hydration']);
        $ingredient = Ingredient::query()->create(['name_ar' => 'هيالورونيك', 'slug' => 'hyaluronic']);

        $this->actingAs($admin)->patch(route('admin.products.update', $product), [
            'name_ar' => 'سيروم مرطب', 'slug' => 'hydrating-serum', 'description' => 'وصف', 'benefits' => 'فائدة', 'usage' => 'استخدميه مساءً', 'warnings' => 'اختبار حساسية', 'default_variant_name' => '30 مل', 'default_variant_size' => '30ml', 'compare_at_price' => '120.000',
            'skin_type_ids' => [$skinType->id], 'concern_ids' => [$concern->id], 'ingredient_ids' => [$ingredient->id], 'is_active' => true,
        ])->assertRedirect(route('admin.products.edit', 'hydrating-serum'));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name_ar' => 'سيروم مرطب', 'benefits' => 'فائدة']);
        $this->assertSame([$skinType->id], $product->fresh()->skinTypes()->pluck('id')->all());
        $this->assertSame([$concern->id], $product->fresh()->concerns()->pluck('id')->all());
        $this->assertSame([$ingredient->id], $product->fresh()->ingredients()->pluck('id')->all());
        $this->assertDatabaseHas('product_variants', ['product_id' => $product->id, 'is_default' => true, 'name' => '30 مل', 'size' => '30ml', 'compare_at_price' => '120.000']);
    }

    public function test_the_edit_form_shows_the_default_variant_instead_of_the_cheapest_one(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $administrator = User::factory()->create();
        $administrator->givePermissionTo('admin.access');
        $product = Product::query()->create(['name_ar' => 'كريم', 'slug' => 'two-sizes']);
        $product->createDefaultVariant('TWO-100', '50.000')->update(['size' => '100ml', 'compare_at_price' => '60.000']);
        $product->variants()->create(['sku' => 'TWO-30', 'size' => '30ml', 'price' => '20.000', 'compare_at_price' => '25.000', 'is_active' => true]);

        $html = $this->actingAs($administrator)->get(route('admin.products.edit', $product))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<input name="default_variant_size"[^>]*value="100ml"/', $html);
        $this->assertMatchesRegularExpression('/<input name="compare_at_price"[^>]*value="60\.000"/', $html);
    }
}
