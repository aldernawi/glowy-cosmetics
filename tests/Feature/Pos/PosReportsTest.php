<?php

namespace Tests\Feature\Pos;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\PosDevice;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\PosDeviceService;
use App\Services\PosSaleService;
use App\Services\ReportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create(['name' => 'كاشير التقارير']);
        $this->admin->assignRole('administrator');
        $this->variant = Product::query()->create(['name_ar' => 'كريم', 'slug' => 'report-cream', 'brand_id' => Brand::query()->create(['name_ar' => 'ماركة', 'slug' => 'brand'])->id, 'category_id' => Category::query()->create(['name_ar' => 'تصنيف', 'slug' => 'category'])->id])->createDefaultVariant('REPORT-1', '10.000');
        $this->variant->update(['stock_quantity' => 100]);

        // Web: one delivered order of 2 (20.000 + 5.000 delivery) and a cancelled one.
        $this->order('delivered', 2, '25.000', '2026-10-10 12:00:00');
        $this->order('cancelled', 9, '95.000', '2026-10-10 12:00:00');
        // Shop: 3 in cash, 1 by card, one voided, one from another month.
        $this->sell(3, 'cash', '2026-10-11T10:00:00+02:00');
        $this->sell(1, 'card', '2026-10-12T10:00:00+02:00');
        app(PosSaleService::class)->void($this->sell(7, 'cash', '2026-10-12T11:00:00+02:00'), $this->admin);
        $this->sell(4, 'transfer', '2026-09-01T10:00:00+02:00');
    }

    public function test_the_unified_total_adds_both_channels_and_matches_its_parts(): void
    {
        $metrics = $this->metrics(['from' => '2026-10-01', 'to' => '2026-10-31']);

        $this->assertSame(['65.000', 3, '21.667'], [$metrics['sales_total'], $metrics['sales_count'], $metrics['average_order']]);
        $this->assertSame(['25.000', 1], [$metrics['web_total'], $metrics['web_count']]);
        $this->assertSame(['40.000', 2], [$metrics['pos_total'], $metrics['pos_count']]);
        $this->assertSame(['30.000', '10.000', '0.000'], array_column(array_values($metrics['pos_methods']), 'total'));
        $this->assertSame(['كريم', 6, '60.000'], array_values((array) $metrics['top_products']->sole()));
    }

    public function test_each_channel_can_be_viewed_alone(): void
    {
        $web = $this->metrics(['channel' => 'web']);
        $this->assertSame(['25.000', '25.000', '0.000', 2], [$web['sales_total'], $web['web_total'], $web['pos_total'], $web['top_products']->sole()->quantity]);

        $pos = $this->metrics(['channel' => 'pos']);
        $this->assertSame(['80.000', '0.000', '80.000', 8], [$pos['sales_total'], $pos['web_total'], $pos['pos_total'], $pos['top_products']->sole()->quantity]);

        $this->assertSame('105.000', $this->metrics([])['sales_total']);
    }

    public function test_dates_use_the_moment_of_sale_in_the_shop(): void
    {
        $this->sell(5, 'cash', '2026-10-31T22:30:00Z');

        $this->assertSame('40.000', $this->metrics(['channel' => 'pos', 'from' => '2026-10-01', 'to' => '2026-10-31'])['pos_total']);
        $this->assertSame('50.000', $this->metrics(['channel' => 'pos', 'from' => '2026-11-01', 'to' => '2026-11-01'])['pos_total']);
    }

    public function test_order_filters_only_keep_shop_sales_they_truly_describe(): void
    {
        $this->assertSame('80.000', $this->metrics(['status' => 'delivered', 'payment_status' => 'paid'])['pos_total']);
        $this->assertSame('0.000', $this->metrics(['status' => 'pending'])['pos_total']);
        $this->assertSame('0.000', $this->metrics(['payment_status' => 'unpaid'])['pos_total']);
    }

    public function test_the_sales_file_lists_both_channels_newest_first(): void
    {
        $rows = app(ReportService::class)->rows('sales', ['from' => '2026-10-01', 'to' => '2026-10-31']);

        $this->assertSame(['المحل', 'المحل', 'الموقع'], $rows->pluck(1)->all());
        $this->assertSame(['بطاقة مصرفية', 'نقدًا', 'عند الاستلام'], $rows->pluck(4)->all());
        $this->assertSame(['10.000', '30.000', '25.000'], $rows->pluck(7)->all());
        $this->assertSame('كاشير التقارير', $rows[0][3]);
        $this->assertCount(count(app(ReportService::class)->headings('sales')), $rows[0]);

        $csv = $this->actingAs($this->admin)->get(route('admin.reports.export', ['report' => 'sales', 'channel' => 'pos']))->assertOk()->streamedContent();
        $this->assertStringContainsString('المحل', $csv);
        $this->assertStringNotContainsString('GLW-', $csv);
    }

    public function test_brand_and_category_files_follow_the_channel_and_dates(): void
    {
        $october = ['from' => '2026-10-01', 'to' => '2026-10-31'];

        foreach (['brands' => 'ماركة', 'categories' => 'تصنيف'] as $report => $name) {
            $this->assertSame([[$name, 1, 6, '60.000']], app(ReportService::class)->rows($report, $october)->all());
            $this->assertSame([[$name, 1, 4, '40.000']], app(ReportService::class)->rows($report, $october + ['channel' => 'pos'])->all());
            $this->assertSame([[$name, 1, 2, '20.000']], app(ReportService::class)->rows($report, $october + ['channel' => 'web'])->all());
        }
    }

    public function test_the_page_shows_the_totals_and_rejects_an_unknown_channel(): void
    {
        $this->actingAs($this->admin)->get(route('admin.reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))->assertOk()
            ->assertSee('65.000 د.ل')->assertSee('25.000 د.ل')->assertSee('40.000 د.ل')->assertSee('إجمالي المبيعات (الموقع + المحل)');
        $this->actingAs($this->admin)->get(route('admin.reports.index', ['channel' => 'pos']))->assertOk()->assertSee('إجمالي مبيعات المحل')->assertSee('channel=pos', false);
        $this->actingAs($this->admin)->get(route('admin.reports.index', ['channel' => 'x']))->assertSessionHasErrors('channel');
    }

    /** @param array<string, string> $filters */
    private function metrics(array $filters): array
    {
        return app(ReportService::class)->dashboard($filters);
    }

    private function order(string $status, int $quantity, string $total, string $date): void
    {
        $order = Order::query()->forceCreate(['number' => 'GLW-'.Str::random(6), 'status' => $status, 'customer_name' => 'عميلة', 'phone' => '09', 'delivery_latitude' => '1.0000000', 'delivery_longitude' => '1.0000000', 'delivery_distance_km' => '1.000', 'delivery_fee' => '5.000', 'store_latitude_snapshot' => '1.0000000', 'store_longitude_snapshot' => '1.0000000', 'subtotal' => $quantity * 10, 'total' => $total, 'created_at' => $date]);
        $order->items()->create(['product_variant_id' => $this->variant->id, 'product_name' => 'كريم', 'sku' => 'REPORT-1', 'unit_price' => '10.000', 'quantity' => $quantity, 'subtotal' => $quantity * 10]);
    }

    private function sell(int $quantity, string $method, string $soldAt): PosSale
    {
        $this->travelTo('2026-12-01');
        $device = PosDevice::query()->first() ?? app(PosDeviceService::class)->link($this->admin, 'جهاز')[0];
        $sequence = PosSale::query()->count() + 1;

        return app(PosSaleService::class)->record($device, ['uuid' => (string) Str::uuid(), 'sequence' => $sequence, 'receipt_number' => "P1-{$sequence}", 'sold_at' => $soldAt, 'payment_method' => $method, 'items' => [['variant_id' => $this->variant->id, 'name' => 'كريم', 'variant' => null, 'sku' => 'REPORT-1', 'unit_price' => '10.000', 'quantity' => $quantity]]]);
    }
}
