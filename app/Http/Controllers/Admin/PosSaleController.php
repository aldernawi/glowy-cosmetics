<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PosPaymentMethod;
use App\Enums\PosSaleStatus;
use App\Http\Controllers\Admin\Concerns\ReadsDateFilters;
use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Models\PosStockShortage;
use App\Services\PosSaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PosSaleController extends Controller
{
    use ReadsDateFilters;

    public function index(Request $request): View
    {
        $sales = PosSale::query()
            ->when($this->dateFilter($request, 'from'), fn (Builder $query, string $from) => $query->whereDate('sold_at', '>=', $from))
            ->when($this->dateFilter($request, 'to'), fn (Builder $query, string $to) => $query->whereDate('sold_at', '<=', $to))
            ->when(PosPaymentMethod::tryFrom($request->string('payment_method')->toString()), fn (Builder $query, PosPaymentMethod $method) => $query->where('payment_method', $method))
            ->when($request->filled('q'), fn (Builder $query) => $query->where('receipt_number', 'like', '%'.$request->string('q').'%'));

        return view('admin.pos.sales.index', [
            'totals' => (clone $sales)->where('status', PosSaleStatus::Completed)->selectRaw('payment_method, COUNT(*) as sales_count, SUM(total) as sales_total')->groupBy('payment_method')->get()->keyBy(fn (PosSale $row) => $row->payment_method->value),
            'sales' => $sales->withCount('items')->latest('sold_at')->latest('id')->paginate(30)->withQueryString(),
            'methods' => PosPaymentMethod::cases(),
            'openShortages' => PosStockShortage::query()->whereNull('resolved_at')->count(),
        ]);
    }

    public function show(PosSale $sale): View
    {
        return view('admin.pos.sales.show', ['sale' => $sale->load(['items.shortages', 'device', 'voidedBy'])]);
    }

    public function void(Request $request, PosSale $sale, PosSaleService $sales): RedirectResponse
    {
        $sales->void($sale, $request->user());

        return to_route('admin.pos.sales.show', $sale)->with('status', 'أُلغي البيع وأُعيدت الكمية إلى المخزون.');
    }
}
