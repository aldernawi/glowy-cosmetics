<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\ProductImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('admin.brands.index', [
            'brands' => Brand::query()->orderBy('sort_order')->orderBy('name_ar')->paginate(20),
        ]);
    }

    public function store(StoreBrandRequest $request, ProductImageService $images): RedirectResponse
    {
        $attributes = Arr::except($request->validated(), ['logo']) + ['is_active' => $request->boolean('is_active'), 'is_featured' => $request->boolean('is_featured')];
        if ($request->hasFile('logo')) {
            $attributes['logo_path'] = $images->storeOriginal($request->file('logo'), 'brands');
        }
        Brand::query()->create($attributes);

        return to_route('admin.brands.index')->with('status', 'تمت إضافة الماركة بنجاح.');
    }

    public function update(UpdateBrandRequest $request, Brand $brand, ProductImageService $images): RedirectResponse
    {
        $attributes = Arr::except($request->validated(), ['logo']) + ['is_active' => $request->boolean('is_active'), 'is_featured' => $request->boolean('is_featured')];
        if ($request->hasFile('logo')) {
            $attributes['logo_path'] = $images->storeOriginal($request->file('logo'), 'brands');
        }
        $brand->update($attributes);
        if (isset($attributes['logo_path']) && $brand->getOriginal('logo_path') && ! Brand::query()->whereKeyNot($brand->id)->where('logo_path', $brand->getOriginal('logo_path'))->exists()) {
            Storage::disk('public')->delete($brand->getOriginal('logo_path'));
        }

        return to_route('admin.brands.index')->with('status', 'تم تحديث الماركة.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        abort_if($brand->products()->exists(), 422, 'لا يمكن حذف ماركة مرتبطة بمنتجات. أخفيها بدلًا من ذلك.');
        $logo = $brand->logo_path;
        $brand->delete();
        if ($logo && ! Brand::query()->where('logo_path', $logo)->exists()) {
            Storage::disk('public')->delete($logo);
        }

        return to_route('admin.brands.index')->with('status', 'تم حذف الماركة.');
    }
}
