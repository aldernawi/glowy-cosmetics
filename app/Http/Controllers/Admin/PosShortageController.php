<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosStockShortage;
use App\Services\PosSaleService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PosShortageController extends Controller
{
    public function index(): View
    {
        return view('admin.pos.shortages.index', [
            'shortages' => PosStockShortage::query()->with(['item.sale', 'item.productVariant', 'resolver'])->orderByRaw('resolved_at IS NOT NULL')->latest('id')->paginate(30),
        ]);
    }

    public function update(Request $request, PosStockShortage $shortage, PosSaleService $sales): RedirectResponse
    {
        $validated = $request->validate(['resolution' => ['required', 'in:'.PosSaleService::RESOLUTION_DEDUCTED.','.PosSaleService::RESOLUTION_DISMISSED]]);

        if ($validated['resolution'] === PosSaleService::RESOLUTION_DISMISSED) {
            $sales->dismissShortage($shortage, $request->user());

            return to_route('admin.pos.shortages.index')->with('status', 'أُغلق النقص دون تغيير المخزون.');
        }

        try {
            $sales->deductShortage($shortage, $request->user());
        } catch (DomainException) {
            return back()->withErrors(['resolution' => 'لا توجد كمية كافية للخصم. ألغي طلب الموقع الذي حجزها أو عدّلي المخزون أولًا.']);
        }

        return to_route('admin.pos.shortages.index')->with('status', 'خُصم النقص من المخزون.');
    }
}
