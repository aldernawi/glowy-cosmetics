<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ReadsDateFilters;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use ReadsDateFilters;

    public function index(Request $request): View
    {
        $variants = ProductVariant::query()->with('product.brand')->when($request->filled('q'), fn ($query) => $query->where(fn ($nested) => $nested->where('sku', 'like', '%'.$request->string('q').'%')->orWhereHas('product', fn ($product) => $product->where('name_ar', 'like', '%'.$request->string('q').'%'))))->orderBy('stock_quantity')->paginate(30)->withQueryString();

        return view('admin.stock.index', compact('variants'));
    }

    public function outOfStock(): View
    {
        return view('admin.stock.out.index', ['variants' => ProductVariant::query()->with('product')->where('stock_quantity', 0)->orderBy('sku')->paginate(30)]);
    }

    public function lowStock(): View
    {
        return view('admin.stock.low.index', ['variants' => ProductVariant::query()->with('product')->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->orderBy('stock_quantity')->paginate(30)]);
    }

    public function movements(Request $request): View
    {
        $movements = StockMovement::query()->with(['variant.product', 'user'])->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))->when($this->dateFilter($request, 'from'), fn ($query, string $from) => $query->whereDate('created_at', '>=', $from))->when($this->dateFilter($request, 'to'), fn ($query, string $to) => $query->whereDate('created_at', '<=', $to))->latest()->paginate(50)->withQueryString();

        return view('admin.stock.movements.index', compact('movements'));
    }
}
