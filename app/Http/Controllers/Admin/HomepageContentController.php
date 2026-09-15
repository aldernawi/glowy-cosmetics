<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateHomepageContentRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Concern;
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
            $values['hero_image_path'] = $images->storeWebp($request->file('hero_image'), 'homepage');
        }
        $content->update($values);
        if (isset($values['hero_image_path']) && str_starts_with($previousImage, 'homepage/')) {
            Storage::disk('public')->delete($previousImage);
        }

        return to_route('admin.content.index')->with('status', 'تم تحديث محتوى المتجر.');
    }
}
