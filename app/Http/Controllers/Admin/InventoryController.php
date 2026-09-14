<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;

class InventoryController extends Controller
{
    public function lowStock(): View
    {
        return view('admin.stock.low.index', ['variants' => ProductVariant::query()->with('product')->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->orderBy('stock_quantity')->paginate(30)]);
    }

    public function movements(): View
    {
        return view('admin.stock.movements.index', ['movements' => StockMovement::query()->with(['variant.product', 'user'])->latest()->paginate(50)]);
    }
}
