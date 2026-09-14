<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;

class AdminDashboardService
{
    /** @return array<string, int|string> */
    public function metrics(): array
    {
        $today = CarbonImmutable::today();
        $salesToday = Order::query()->where('status', 'delivered')->whereDate('created_at', $today)->sum('total');
        $salesMonth = Order::query()->where('status', 'delivered')->whereBetween('created_at', [$today->startOfMonth(), $today->endOfMonth()])->sum('total');

        return [
            'sales_today' => BigDecimal::of((string) $salesToday)->toScale(3)->__toString(),
            'sales_month' => BigDecimal::of((string) $salesMonth)->toScale(3)->__toString(),
            'orders_today' => Order::query()->whereDate('created_at', $today)->count(),
            'active_orders' => Order::query()->whereNotIn('status', ['delivered', 'cancelled', 'returned'])->count(),
            'customers_count' => Order::query()->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'low_stock_count' => ProductVariant::query()->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'out_of_stock_count' => ProductVariant::query()->where('stock_quantity', 0)->count(),
        ];
    }
}
