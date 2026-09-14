<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_only_delivered_orders_as_sales_and_flags_low_stock_variants(): void
    {
        ProductVariant::query()->create(['product_id' => Product::query()->create(['name_ar' => 'سيروم', 'slug' => 'serum'])->id, 'sku' => 'LOW', 'price' => '10.000', 'stock_quantity' => 2, 'low_stock_threshold' => 5]);
        Order::query()->create($this->orderAttributes('DELIVERED-1', 'delivered', '25.500'));
        Order::query()->create($this->orderAttributes('PENDING-1', 'pending', '999.000'));

        $metrics = app(AdminDashboardService::class)->metrics();

        $this->assertSame('25.500', $metrics['sales_today']);
        $this->assertSame(1, $metrics['low_stock_count']);
    }

    private function orderAttributes(string $number, string $status, string $total): array
    {
        return ['number' => $number, 'status' => $status, 'customer_name' => 'عميلة', 'phone' => '0910000000', 'delivery_latitude' => '32.8872000', 'delivery_longitude' => '13.1913000', 'store_latitude_snapshot' => '32.8872000', 'store_longitude_snapshot' => '13.1913000', 'delivery_distance_km' => '5.000', 'delivery_fee' => '5.000', 'subtotal' => $total, 'discount' => '0.000', 'total' => $total];
    }
}
