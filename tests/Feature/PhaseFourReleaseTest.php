<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\AdminDashboardService;
use App\Services\ReportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
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

    public function test_robots_is_not_shadowed_by_a_static_file_and_protects_private_pages(): void
    {
        $this->assertFileDoesNotExist(public_path('robots.txt'));
        $this->get(route('seo.robots'))->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('seo.sitemap'));
    }

    public function test_malformed_admin_dates_are_ignored(): void
    {
        $admin = $this->reportAdministrator();

        foreach ([route('admin.orders.index'), route('admin.stock.movements.index')] as $url) {
            $this->actingAs($admin)->get($url.'?from=notadate&to=2026-99-99')->assertOk();
        }
    }

    public function test_best_sellers_ignore_cancelled_orders_and_follow_the_report_dates(): void
    {
        $this->soldItem('delivered', 'منتج مسلّم', '2026-10-10');
        $this->soldItem('delivered', 'منتج قديم', '2026-01-01');
        $this->soldItem('cancelled', 'منتج ملغي', '2026-10-10');

        $reported = app(ReportService::class)->dashboard(['from' => '2026-10-01', 'to' => '2026-10-31'])['top_products']->pluck('product_name');
        $this->assertSame(['منتج مسلّم'], $reported->all());

        $dashboard = app(AdminDashboardService::class)->metrics();
        $this->assertEqualsCanonicalizing(['منتج مسلّم', 'منتج قديم'], $dashboard['top_products']->pluck('product_name')->all());
    }

    public function test_exported_text_cannot_run_as_a_spreadsheet_formula(): void
    {
        $this->soldItem('delivered', 'منتج', '2026-10-10')->update(['customer_name' => '=HYPERLINK("http://evil.test")']);

        $csv = $this->actingAs($this->reportAdministrator())->get(route('admin.reports.export', ['report' => 'sales']))->streamedContent();
        $rows = array_map(fn (string $line) => str_getcsv($line, escape: ''), explode("\n", trim($csv)));

        $this->assertSame("'=HYPERLINK(\"http://evil.test\")", $rows[1][3]);
        $this->assertSame('0.000', $rows[1][6]);
    }

    public function test_the_store_uses_libyan_time_and_arabic_validation_messages(): void
    {
        $this->assertSame('Africa/Tripoli', config('app.timezone'));

        $this->actingAs($this->reportAdministrator())->post(route('admin.categories.store'), [])
            ->assertSessionHasErrors(['name_ar' => 'حقل الاسم بالعربية مطلوب.']);
        $this->post(route('logout'));
        $this->post('/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])->assertSessionHasErrors(['email' => 'بيانات الدخول غير صحيحة.']);
    }

    public function test_arabic_validation_covers_every_framework_rule(): void
    {
        $english = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
        $keys = fn (array $lines) => array_keys(Arr::dot(Arr::except($lines, ['custom', 'attributes'])));

        $this->assertSame([], array_values(array_diff($keys($english), $keys(require lang_path('ar/validation.php')))));
    }

    public function test_the_printed_order_contains_what_the_courier_needs(): void
    {
        $order = $this->soldItem('confirmed', 'منتج', '2026-10-10');
        $order->update(['delivery_address' => 'شارع الجمهورية', 'delivery_landmark' => 'قرب المسجد', 'secondary_phone' => '0920000000', 'delivery_fee' => '15.000', 'notes' => 'اتصلي أولًا']);

        $this->actingAs($this->reportAdministrator())->get(route('admin.orders.print', $order))->assertOk()
            ->assertSee('شارع الجمهورية')->assertSee('قرب المسجد')->assertSee('0920000000')->assertSee('15.000 د.ل')->assertSee('اتصلي أولًا');
    }

    private function reportAdministrator(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->givePermissionTo('admin.access');

        return $admin;
    }

    private function soldItem(string $status, string $name, string $date): Order
    {
        $order = Order::query()->forceCreate(['number' => 'GLW-'.uniqid(), 'status' => $status, 'customer_name' => 'عميلة', 'phone' => '09', 'delivery_latitude' => '1.0000000', 'delivery_longitude' => '1.0000000', 'delivery_distance_km' => '1.000', 'delivery_fee' => '0.000', 'store_latitude_snapshot' => '1.0000000', 'store_longitude_snapshot' => '1.0000000', 'subtotal' => '10.000', 'total' => '10.000', 'created_at' => $date.' 12:00:00']);
        $order->items()->create(['product_name' => $name, 'sku' => 'S', 'unit_price' => '10.000', 'quantity' => 1, 'subtotal' => '10.000']);

        return $order;
    }
}
