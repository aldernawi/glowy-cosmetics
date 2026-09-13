<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDeliveryTierRequest;
use App\Http\Requests\Admin\UpdateDeliveryOriginRequest;
use App\Models\DeliverySetting;
use App\Models\DeliveryTier;
use App\Services\DeliveryTierService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class DeliverySettingController extends Controller
{
    public function index(): View
    {
        return view('admin.delivery.index', ['setting' => DeliverySetting::current(), 'tiers' => DeliveryTier::query()->orderBy('min_distance_km')->get()]);
    }

    public function updateOrigin(UpdateDeliveryOriginRequest $request): RedirectResponse
    {
        DeliverySetting::query()->updateOrCreate(['id' => DeliverySetting::current()?->id], $request->validated());

        return to_route('admin.delivery.index')->with('status', 'تم حفظ نقطة انطلاق Glowy.');
    }

    public function storeTier(StoreDeliveryTierRequest $request, DeliveryTierService $tiers): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $tiers): void {
                DeliveryTier::query()->create($request->validated());
                $tiers->validateActiveTiers();
            });
        } catch (DomainException) {
            return back()->withErrors(['delivery_tier' => 'الشرائح النشطة يجب أن تبدأ من 0 وأن تكون متصلة بلا فجوات أو تداخل.']);
        }

        return to_route('admin.delivery.index')->with('status', 'تمت إضافة شريحة التوصيل.');
    }
}
