<?php

namespace App\Services;

use App\Enums\PosSaleStatus;
use App\Models\Order;
use App\Models\PosSale;
use App\Models\ProductVariant;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /** @return array<string, mixed> */
    public function metrics(): array
    {
        $today = CarbonImmutable::today();
        $salesToday = Order::query()->where('status', 'delivered')->whereDate('created_at', $today)->sum('total');
        $salesMonth = Order::query()->where('status', 'delivered')->whereBetween('created_at', [$today->startOfMonth(), $today->endOfMonth()])->sum('total');

        $delivered = Order::query()->where('status', 'delivered');
        $deliveredCount = (clone $delivered)->count();
        $lastSevenDays = collect(range(6, 0))->map(function (int $daysAgo) use ($delivered): array {
            $date = CarbonImmutable::today()->subDays($daysAgo);

            return ['label' => $date->format('d/m'), 'sales' => (string) BigDecimal::of((string) ((clone $delivered)->whereDate('created_at', $date)->sum('total') ?: 0))->toScale(3, RoundingMode::HalfUp)];
        });

        return [
            'sales_today' => BigDecimal::of((string) $salesToday)->toScale(3, RoundingMode::HalfUp)->__toString(),
            'sales_month' => BigDecimal::of((string) $salesMonth)->toScale(3, RoundingMode::HalfUp)->__toString(),
            'average_order_value' => $deliveredCount > 0 ? BigDecimal::of((string) ((clone $delivered)->sum('total') ?: 0))->dividedBy($deliveredCount, 3, RoundingMode::HalfUp)->__toString() : '0.000',
            'pos_sales_today' => BigDecimal::of((string) PosSale::query()->where('status', PosSaleStatus::Completed)->whereDate('sold_at', $today)->sum('total'))->toScale(3, RoundingMode::HalfUp)->__toString(),
            'orders_today' => Order::query()->whereDate('created_at', $today)->count(),
            'active_orders' => Order::query()->whereNotIn('status', ['delivered', 'cancelled', 'returned'])->count(),
            'customers_count' => Order::query()->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'low_stock_count' => ProductVariant::query()->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'out_of_stock_count' => ProductVariant::query()->where('stock_quantity', 0)->count(),
            'recent_orders' => Order::query()->latest()->limit(6)->get(),
            'sales_by_day' => $lastSevenDays,
            'orders_by_status' => Order::query()->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status'),
            'top_products' => DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->whereNotIn('orders.status', ['cancelled', 'returned'])->select('product_name', DB::raw('SUM(quantity) as quantity'))->groupBy('product_name')->orderByDesc('quantity')->limit(5)->get(),
            'top_brands' => DB::table('brands')->leftJoin('products', 'brands.id', '=', 'products.brand_id')->leftJoin('product_variants', 'products.id', '=', 'product_variants.product_id')->leftJoin('order_items', 'product_variants.id', '=', 'order_items.product_variant_id')->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')->select('brands.name_ar', DB::raw("COALESCE(SUM(CASE WHEN orders.status NOT IN ('cancelled', 'returned') THEN order_items.quantity ELSE 0 END), 0) as quantity"))->groupBy('brands.id', 'brands.name_ar')->orderByDesc('quantity')->limit(5)->get(),
        ];
    }
}
