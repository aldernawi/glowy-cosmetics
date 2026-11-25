<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PhaseFourReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_homepage_and_contact_content(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');

        $this->actingAs($admin)->put(route('admin.content.update'), [
            'hero_title' => 'وهجك يبدأ هنا',
            'hero_subtitle' => 'عناية مختارة لبشرتك',
            'hero_cta_label' => 'تسوّقي الآن',
            'announcement' => 'توصيل محسوب بدقة',
            'phone' => '0910000000',
            'whatsapp' => '218910000000',
        ])->assertRedirect(route('admin.content.index'));

        $this->get(route('home'))->assertOk()->assertSee('وهجك يبدأ هنا')->assertSee('توصيل محسوب بدقة');
    }

    public function test_admin_can_manage_extra_homepage_banners_and_section_visibility(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');

        $this->actingAs($admin)->post(route('admin.content.banners.store'), [
            'title' => 'عرض العناية المسائية',
            'body' => 'اختيارات هادئة قبل النوم',
            'cta_label' => 'اكتشفي العرض',
            'cta_url' => '/products',
            'sort_order' => 3,
            'is_active' => true,
        ])->assertRedirect(route('admin.content.index'));

        $this->get(route('home'))->assertOk()->assertSee('عرض العناية المسائية');

        $this->actingAs($admin)->put(route('admin.content.sections.update'), ['sections' => [
            ['key' => 'categories', 'is_visible' => false, 'sort_order' => 1],
            ['key' => 'concerns', 'is_visible' => true, 'sort_order' => 2],
        ]])->assertRedirect(route('admin.content.index'));

        $this->get(route('home'))->assertDontSee('تسوّقي حسب التصنيف');
    }

    public function test_admin_reports_are_visible_and_exportable_as_csv(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee('التقارير');
        $this->actingAs($admin)->get(route('admin.reports.export', ['report' => 'products']))
            ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_product_pages_expose_canonical_open_graph_and_structured_data(): void
    {
        $product = Product::query()->create(['name_ar' => 'سيروم الوهج', 'slug' => 'glow-serum', 'description' => 'سيروم لطيف', 'is_active' => true]);
        $product->createDefaultVariant('GLOW-1', '29.500');

        $this->get(route('products.show', $product))->assertOk()
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:type" content="product"', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_sitemap_and_robots_are_cacheable_controller_routes(): void
    {
        $this->get(route('seo.sitemap'))->assertOk()->assertHeader('content-type', 'application/xml');
        $this->get(route('seo.robots'))->assertOk()->assertSee('Sitemap:');
    }

    public function test_catalog_changes_invalidate_the_cached_sitemap(): void
    {
        Cache::put('seo.sitemap', 'stale-map', 3600);
        Product::query()->create(['name_ar' => 'واقي شمس', 'slug' => 'fresh-sunscreen']);

        $this->assertNull(Cache::get('seo.sitemap'));
    }

    public function test_filtered_catalog_is_not_indexed_and_uses_the_clean_canonical_url(): void
    {
        $this->get(route('products.index', ['q' => 'سيروم']))->assertOk()
            ->assertSee('content="noindex,follow"', false)
            ->assertSee('href="'.route('products.index').'"', false);
    }

    public function test_non_administrators_cannot_access_content_or_reports(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.content.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.reports.index'))->assertForbidden();
    }
}
