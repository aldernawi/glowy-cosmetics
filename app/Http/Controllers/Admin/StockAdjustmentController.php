<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustStockRequest;
use App\Models\ProductVariant;
use App\Services\StockService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class StockAdjustmentController extends Controller
{
    public function index(): View
    {
        return view('admin.stock.adjustments.index', [
            'variants' => ProductVariant::query()
                ->with(['product.brand'])
                ->where('is_active', true)
                ->orderBy('sku')
                ->paginate(30),
        ]);
    }

    public function store(AdjustStockRequest $request, StockService $stock): RedirectResponse
    {
        $validated = $request->validated();
        $variant = ProductVariant::query()->findOrFail($validated['product_variant_id']);

        try {
            $stock->adjust(
                $variant,
                (int) $validated['delta'],
                StockMovementType::from($validated['type']),
                (string) Str::uuid(),
                $request->user(),
                'admin-adjustment',
                $validated['notes'] ?? null,
            );
        } catch (DomainException) {
            return back()->withInput()->withErrors(['delta' => 'لا يمكن تنفيذ الحركة لأن الرصيد المتاح لا يكفي.']);
        }

        return to_route('admin.stock.adjustments.index')->with('status', 'تم تعديل رصيد المخزون وتسجيل الحركة.');
    }
}
