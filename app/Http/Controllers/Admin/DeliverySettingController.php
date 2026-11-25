<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDeliveryTierRequest;
use App\Http\Requests\Admin\UpdateDeliveryOriginRequest;
use App\Http\Requests\Admin\UpdateDeliveryTierRequest;
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
        return view('admin.delivery.index', ['setting' => DeliverySetting::current(), 'tiers' => DeliveryTier::query()->orderBy('min_distance_km')->get(), 'maximumDistance' => DeliveryTier::query()->where('is_active', true)->max('max_distance_km')]);
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
                DeliveryTier::query()->create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);
                $tiers->validateActiveTiers();
            });
        } catch (DomainException) {
            return back()->withErrors(['delivery_tier' => 'الشرائح النشطة يجب أن تبدأ من 0 وأن تكون متصلة بلا فجوات أو تداخل.']);
        }

        return to_route('admin.delivery.index')->with('status', 'تمت إضافة شريحة التوصيل.');
    }

    public function updateTier(UpdateDeliveryTierRequest $request, DeliveryTier $tier, DeliveryTierService $tiers): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $tier, $tiers): void {
                $tier->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
                $tiers->validateActiveTiers();
            });
        } catch (DomainException) {
            return back()->withErrors(['delivery_tier' => 'لا يمكن حفظ الشرائح النشطة إلا إذا بدأت من 0 وكانت متصلة بلا فجوات أو تداخل.']);
        }

        return to_route('admin.delivery.index')->with('status', 'تم تحديث شريحة التوصيل.');
    }

    public function destroyTier(DeliveryTier $tier, DeliveryTierService $tiers): RedirectResponse
    {
        try {
            DB::transaction(function () use ($tier, $tiers): void {
                $tier->delete();
                $tiers->validateActiveTiers();
            });
        } catch (DomainException) {
            return back()->withErrors(['delivery_tier' => 'لا يمكن حذف هذه الشريحة لأن نطاق التوصيل النشط سيصبح غير صالح.']);
        }

        return to_route('admin.delivery.index')->with('status', 'تم حذف شريحة التوصيل.');
    }
}
