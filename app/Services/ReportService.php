<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PosPaymentMethod;
use App\Enums\PosSaleStatus;
use App\Models\Order;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public const REPORTS = ['sales', 'orders', 'products', 'brands', 'categories', 'customers', 'stock', 'movements'];

    public const CHANNELS = ['web' => 'الموقع', 'pos' => 'المحل'];

    /** @param array<string, mixed> $filters */
    public function dashboard(array $filters = []): array
    {
        $webTotal = $this->includes('web', $filters) ? $this->money($this->webSales($filters)->sum('total')) : $this->money(0);
        $webCount = $this->includes('web', $filters) ? $this->webSales($filters)->count() : 0;
        $posMethods = $this->posTotalsByMethod($filters);
        $posTotal = array_reduce($posMethods, fn (BigDecimal $sum, array $row) => $sum->plus($row['total']), BigDecimal::zero())->toScale(3);
        $posCount = array_sum(array_column($posMethods, 'count'));
        $total = $webTotal->plus($posTotal);
        $count = $webCount + $posCount;

        return [
            'sales_total' => (string) $total,
            'sales_count' => $count,
            'average_order' => $count > 0 ? (string) $total->dividedBy($count, 3, RoundingMode::HalfUp) : '0.000',
            'web_total' => (string) $webTotal,
            'web_count' => $webCount,
            'pos_total' => (string) $posTotal,
            'pos_count' => $posCount,
            'pos_methods' => array_map(fn (array $row): array => ['label' => $row['label'], 'total' => (string) $row['total'], 'count' => $row['count']], $posMethods),
            'orders_count' => $this->filteredOrders(Order::query(), $filters)->count(),
            'customers_count' => User::query()->whereHas('orders')->count(),
            'products_count' => Product::query()->count(),
            'low_stock_count' => ProductVariant::query()->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'top_products' => DB::query()->fromSub($this->soldLines($filters), 'sold')
                ->select('product_name', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(subtotal) as revenue'))
                ->groupBy('product_name')->orderByDesc('quantity')->orderBy('product_name')->limit(8)->get()
                ->map(function (object $row): object {
                    $row->quantity = (int) $row->quantity;
                    $row->revenue = (string) $this->money($row->revenue);

                    return $row;
                }),
        ];
    }

    public function rows(string $report, array $filters = []): Collection
    {
        return match ($report) {
            'sales' => $this->salesRows($filters),
            'orders' => $this->filteredOrders(Order::query(), $filters)->latest()->get()->map(fn (Order $order) => [$order->number, $order->created_at?->format('Y-m-d'), $order->customer_name, $order->status->value, $order->payment_status->value, $order->total]),
            'products' => Product::query()->with(['brand', 'category', 'variants'])->orderBy('name_ar')->get()->map(fn (Product $product) => [$product->name_ar, $product->brand?->name_ar, $product->category?->name_ar, $product->variants->count(), $product->variants->sum('stock_quantity'), $product->is_active ? 'نعم' : 'لا']),
            'brands' => $this->soldBy('brand_id', DB::table('brands'), $filters),
            'categories' => $this->soldBy('category_id', DB::table('categories'), $filters),
            'customers' => User::query()->withCount('orders')->withSum('orders', 'total')->having('orders_count', '>', 0)->orderByDesc('orders_count')->get()->map(fn (User $user) => [$user->name, $user->email, $user->orders_count, $user->orders_sum_total ?? '0.000']),
            'stock' => ProductVariant::query()->with('product')->orderBy('stock_quantity')->get()->map(fn (ProductVariant $variant) => [$variant->sku, $variant->product->name_ar, $variant->name ?: $variant->size, $variant->stock_quantity, $variant->low_stock_threshold]),
            'movements' => StockMovement::query()->with(['variant.product', 'user'])->latest()->get()->map(fn (StockMovement $movement) => [$movement->created_at?->format('Y-m-d H:i'), $movement->variant->sku, $movement->variant->product->name_ar, $movement->type->value, $movement->delta, $movement->quantity_before, $movement->quantity_after, $movement->reference, $movement->user?->name]),
        };
    }

    public function headings(string $report): array
    {
        return match ($report) {
            'sales' => ['رقم العملية', 'المصدر', 'التاريخ', 'العميل / الكاشير', 'طريقة الدفع', 'المجموع الفرعي', 'التوصيل', 'الإجمالي'],
            'orders' => ['رقم الطلب', 'التاريخ', 'العميل', 'الحالة', 'حالة الدفع', 'الإجمالي'],
            'products' => ['المنتج', 'الماركة', 'التصنيف', 'عدد الخيارات', 'المخزون', 'نشط'],
            'brands', 'categories' => ['الاسم', 'عدد المنتجات', 'الكمية المباعة', 'الإيراد'],
            'customers' => ['العميل', 'البريد', 'عدد الطلبات', 'إجمالي الطلبات'],
            'stock' => ['SKU', 'المنتج', 'الخيار', 'المخزون', 'حد التنبيه'],
            'movements' => ['التاريخ', 'SKU', 'المنتج', 'النوع', 'التغيير', 'قبل', 'بعد', 'المرجع', 'المستخدم'],
        };
    }

    /** @param array<string, mixed> $filters */
    private function salesRows(array $filters): Collection
    {
        $web = $this->includes('web', $filters)
            ? $this->webSales($filters)->get()->map(fn (Order $order): array => [$order->created_at, [$order->number, self::CHANNELS['web'], $order->created_at?->format('Y-m-d H:i'), $order->customer_name, 'عند الاستلام', $order->subtotal, $order->delivery_fee, $order->total]])
            : collect();
        $pos = $this->includes('pos', $filters)
            ? $this->posSales($filters)->get()->map(fn (PosSale $sale): array => [$sale->sold_at, [$sale->receipt_number, self::CHANNELS['pos'], $sale->sold_at->format('Y-m-d H:i'), $sale->cashier_name, $sale->payment_method->label(), $sale->total, '0.000', $sale->total]])
            : collect();

        return $web->toBase()->concat($pos)->sortByDesc(fn (array $row) => $row[0]?->getTimestamp())->pluck(1)->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array{label: string, total: BigDecimal, count: int}>
     */
    private function posTotalsByMethod(array $filters): array
    {
        $rows = $this->includes('pos', $filters)
            ? $this->posSales($filters)->toBase()->select('payment_method', DB::raw('COUNT(*) as sales_count'), DB::raw('SUM(total) as sales_total'))->groupBy('payment_method')->get()->keyBy('payment_method')
            : collect();

        return collect(PosPaymentMethod::cases())->mapWithKeys(fn (PosPaymentMethod $method): array => [$method->value => [
            'label' => $method->label(),
            'total' => $this->money($rows->get($method->value)?->sales_total),
            'count' => (int) ($rows->get($method->value)?->sales_count ?? 0),
        ]])->all();
    }

    /** @param array<string, mixed> $filters */
    private function soldBy(string $column, Builder $groups, array $filters): Collection
    {
        $sold = DB::query()->fromSub($this->soldLines($filters), 'sold')
            ->join('product_variants', 'product_variants.id', '=', 'sold.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->whereNotNull("products.{$column}")
            ->select("products.{$column} as group_id", DB::raw('SUM(sold.quantity) as quantity'), DB::raw('SUM(sold.subtotal) as revenue'))
            ->groupBy("products.{$column}")->get()->keyBy('group_id');
        $products = DB::table('products')->whereNotNull($column)->select($column, DB::raw('COUNT(*) as total'))->groupBy($column)->pluck('total', $column);

        return $groups->orderBy('name_ar')->get(['id', 'name_ar'])->map(fn (object $group): array => [
            $group->name_ar,
            (int) ($products[$group->id] ?? 0),
            (int) ($sold->get($group->id)?->quantity ?? 0),
            (string) $this->money($sold->get($group->id)?->revenue),
        ]);
    }

    /**
     * Every sold line from the selected channels: delivered web orders and completed shop sales.
     *
     * @param  array<string, mixed>  $filters
     */
    private function soldLines(array $filters): Builder
    {
        $columns = ['product_variant_id', 'product_name', 'quantity', 'subtotal'];
        $web = DB::table('order_items')->select($columns)->whereIn('order_id', $this->webSales($filters)->toBase()->select('id'))->when(! $this->includes('web', $filters), fn (Builder $query) => $query->whereRaw('1 = 0'));
        $pos = DB::table('pos_sale_items')->select($columns)->whereIn('pos_sale_id', $this->posSales($filters)->toBase()->select('id'))->when(! $this->includes('pos', $filters), fn (Builder $query) => $query->whereRaw('1 = 0'));

        return $web->unionAll($pos);
    }

    /** @param array<string, mixed> $filters */
    private function webSales(array $filters): EloquentBuilder
    {
        return $this->filteredOrders(Order::query()->where('status', OrderStatus::Delivered->value), $filters);
    }

    /** @param array<string, mixed> $filters */
    private function posSales(array $filters): EloquentBuilder
    {
        return PosSale::query()->where('status', PosSaleStatus::Completed->value)
            ->when($filters['from'] ?? null, fn ($sales, $from) => $sales->whereDate('sold_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($sales, $to) => $sales->whereDate('sold_at', '<=', $to));
    }

    /**
     * A shop sale is handed over and paid on the spot, so it only matches the order
     * filters that mean exactly that.
     *
     * @param  array<string, mixed>  $filters
     */
    private function includes(string $channel, array $filters): bool
    {
        if (! in_array($filters['channel'] ?? null, [null, '', 'all', $channel], true)) {
            return false;
        }

        return $channel === 'web'
            || (in_array($filters['status'] ?? null, [null, '', OrderStatus::Delivered->value], true)
                && in_array($filters['payment_status'] ?? null, [null, '', PaymentStatus::Paid->value], true));
    }

    private function money(mixed $amount): BigDecimal
    {
        return BigDecimal::of((string) ($amount ?: '0'))->toScale(3, RoundingMode::HalfUp);
    }

    /** @param array<string, mixed> $filters */
    private function filteredOrders($query, array $filters)
    {
        return $query->when($filters['from'] ?? null, fn ($orders, $from) => $orders->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($orders, $to) => $orders->whereDate('created_at', '<=', $to))
            ->when($filters['status'] ?? null, fn ($orders, $status) => $orders->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn ($orders, $status) => $orders->where('payment_status', $status));
    }
}
