<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHomepageBannerRequest;
use App\Http\Requests\Admin\UpdateHomepageBannerRequest;
use App\Http\Requests\Admin\UpdateHomepageContentRequest;
use App\Http\Requests\Admin\UpdateHomepageSectionsRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Concern;
use App\Models\HomepageBanner;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Services\HomepageService;
use App\Services\ProductImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class HomepageContentController extends Controller
{
    public function index(HomepageService $content): View
    {
        return view('admin.content.index', [
            'content' => $content->all(),
            'categories' => Category::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'concerns' => Concern::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'products' => Product::query()->sellable()->orderBy('name_ar')->get(),
            'banners' => HomepageBanner::query()->orderBy('sort_order')->orderBy('id')->get(),
            'sections' => $content->sections(),
        ]);
    }

    public function update(UpdateHomepageContentRequest $request, HomepageService $content, ProductImageService $images): RedirectResponse
    {
        $previousImage = $content->all()['hero_image_path'];
        $values = $request->safe()->except('hero_image');
        foreach (['featured_category_ids', 'featured_brand_ids', 'featured_concern_ids', 'new_product_ids', 'best_seller_ids'] as $key) {
            $values[$key] = $values[$key] ?? [];
        }
        if ($request->hasFile('hero_image')) {
            $values['hero_image_path'] = $images->storeOriginal($request->file('hero_image'), 'homepage');
        }
        $content->update($values);
        if (isset($values['hero_image_path']) && str_starts_with($previousImage, 'homepage/')) {
            Storage::disk('public')->delete($previousImage);
        }

        return to_route('admin.content.index')->with('status', 'تم تحديث محتوى المتجر.');
    }

    public function storeBanner(StoreHomepageBannerRequest $request, ProductImageService $images): RedirectResponse
    {
        $attributes = $request->safe()->except('image') + ['is_active' => $request->boolean('is_active')];
        if ($request->hasFile('image')) {
            $attributes['image_path'] = $images->storeOriginal($request->file('image'), 'homepage/banners');
        }
        HomepageBanner::query()->create($attributes);

        return to_route('admin.content.index')->with('status', 'تمت إضافة البنر.');
    }

    public function updateBanner(UpdateHomepageBannerRequest $request, HomepageBanner $banner, ProductImageService $images): RedirectResponse
    {
        $attributes = $request->safe()->except('image') + ['is_active' => $request->boolean('is_active')];
        if ($request->hasFile('image')) {
            $attributes['image_path'] = $images->storeOriginal($request->file('image'), 'homepage/banners');
            if ($banner->image_path && ! HomepageBanner::query()->whereKeyNot($banner->id)->where('image_path', $banner->image_path)->exists()) {
                Storage::disk('public')->delete($banner->image_path);
            }
        }
        $banner->update($attributes);

        return to_route('admin.content.index')->with('status', 'تم تحديث البنر.');
    }

    public function destroyBanner(HomepageBanner $banner): RedirectResponse
    {
        $path = $banner->image_path;
        $banner->delete();
        if ($path && ! HomepageBanner::query()->where('image_path', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }

        return to_route('admin.content.index')->with('status', 'تم حذف البنر.');
    }

    public function updateSections(UpdateHomepageSectionsRequest $request): RedirectResponse
    {
        foreach ($request->validated('sections') as $section) {
            HomepageSection::query()->updateOrCreate(['key' => $section['key']], [
                'is_visible' => (bool) ($section['is_visible'] ?? false),
                'sort_order' => $section['sort_order'],
            ]);
        }

        return to_route('admin.content.index')->with('status', 'تم تحديث ظهور وترتيب أقسام الرئيسية.');
    }
}
