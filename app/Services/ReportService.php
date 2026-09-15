<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public const REPORTS = ['sales', 'orders', 'products', 'brands', 'categories', 'customers', 'stock', 'movements'];

    public function dashboard(): array
    {
        $sales = Order::query()->where('status', OrderStatus::Delivered->value);
        $salesTotal = BigDecimal::of((string) ($sales->sum('total') ?: '0'))->toScale(3);
        $salesCount = (clone $sales)->count();

        return [
            'sales_total' => (string) $salesTotal,
            'sales_count' => $salesCount,
            'average_order' => $salesCount > 0 ? (string) $salesTotal->dividedBy($salesCount, 3) : '0.000',
            'orders_count' => Order::query()->count(),
            'customers_count' => User::query()->whereHas('orders')->count(),
            'products_count' => Product::query()->count(),
            'low_stock_count' => ProductVariant::query()->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'top_products' => DB::table('order_items')->select('product_name', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(subtotal) as revenue'))->groupBy('product_name')->orderByDesc('quantity')->limit(8)->get(),
        ];
    }

    public function rows(string $report): Collection
    {
        return match ($report) {
            'sales' => Order::query()->where('status', OrderStatus::Delivered->value)->latest()->get()->map(fn (Order $order) => [$order->number, $order->created_at?->format('Y-m-d'), $order->customer_name, $order->subtotal, $order->delivery_fee, $order->total]),
            'orders' => Order::query()->latest()->get()->map(fn (Order $order) => [$order->number, $order->created_at?->format('Y-m-d'), $order->customer_name, $order->status->value, $order->payment_status->value, $order->total]),
            'products' => Product::query()->with(['brand', 'category', 'variants'])->orderBy('name_ar')->get()->map(fn (Product $product) => [$product->name_ar, $product->brand?->name_ar, $product->category?->name_ar, $product->variants->count(), $product->variants->sum('stock_quantity'), $product->is_active ? 'نعم' : 'لا']),
            'brands' => DB::table('brands')->leftJoin('products', 'brands.id', '=', 'products.brand_id')->leftJoin('product_variants', 'products.id', '=', 'product_variants.product_id')->leftJoin('order_items', 'product_variants.id', '=', 'order_items.product_variant_id')->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')->select('brands.name_ar', DB::raw('COUNT(DISTINCT products.id) as products_count'), DB::raw("COALESCE(SUM(CASE WHEN orders.status = 'delivered' THEN order_items.quantity ELSE 0 END), 0) as sold_quantity"), DB::raw("COALESCE(SUM(CASE WHEN orders.status = 'delivered' THEN order_items.subtotal ELSE 0 END), 0) as revenue"))->groupBy('brands.id', 'brands.name_ar')->orderBy('brands.name_ar')->get()->map(fn ($row) => (array) $row),
            'categories' => DB::table('categories')->leftJoin('products', 'categories.id', '=', 'products.category_id')->leftJoin('product_variants', 'products.id', '=', 'product_variants.product_id')->leftJoin('order_items', 'product_variants.id', '=', 'order_items.product_variant_id')->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')->select('categories.name_ar', DB::raw('COUNT(DISTINCT products.id) as products_count'), DB::raw("COALESCE(SUM(CASE WHEN orders.status = 'delivered' THEN order_items.quantity ELSE 0 END), 0) as sold_quantity"), DB::raw("COALESCE(SUM(CASE WHEN orders.status = 'delivered' THEN order_items.subtotal ELSE 0 END), 0) as revenue"))->groupBy('categories.id', 'categories.name_ar')->orderBy('categories.name_ar')->get()->map(fn ($row) => (array) $row),
            'customers' => User::query()->withCount('orders')->withSum('orders', 'total')->having('orders_count', '>', 0)->orderByDesc('orders_count')->get()->map(fn (User $user) => [$user->name, $user->email, $user->orders_count, $user->orders_sum_total ?? '0.000']),
            'stock' => ProductVariant::query()->with('product')->orderBy('stock_quantity')->get()->map(fn (ProductVariant $variant) => [$variant->sku, $variant->product->name_ar, $variant->name ?: $variant->size, $variant->stock_quantity, $variant->low_stock_threshold]),
            'movements' => StockMovement::query()->with(['variant.product', 'user'])->latest()->get()->map(fn (StockMovement $movement) => [$movement->created_at?->format('Y-m-d H:i'), $movement->variant->sku, $movement->variant->product->name_ar, $movement->type->value, $movement->delta, $movement->quantity_before, $movement->quantity_after, $movement->reference, $movement->user?->name]),
        };
    }

    public function headings(string $report): array
    {
        return match ($report) {
            'sales' => ['رقم الطلب', 'التاريخ', 'العميل', 'المجموع الفرعي', 'التوصيل', 'الإجمالي'],
            'orders' => ['رقم الطلب', 'التاريخ', 'العميل', 'الحالة', 'حالة الدفع', 'الإجمالي'],
            'products' => ['المنتج', 'الماركة', 'التصنيف', 'عدد الخيارات', 'المخزون', 'نشط'],
            'brands', 'categories' => ['الاسم', 'عدد المنتجات', 'الكمية المباعة', 'الإيراد'],
            'customers' => ['العميل', 'البريد', 'عدد الطلبات', 'إجمالي الطلبات'],
            'stock' => ['SKU', 'المنتج', 'الخيار', 'المخزون', 'حد التنبيه'],
            'movements' => ['التاريخ', 'SKU', 'المنتج', 'النوع', 'التغيير', 'قبل', 'بعد', 'المرجع', 'المستخدم'],
        };
    }
}
