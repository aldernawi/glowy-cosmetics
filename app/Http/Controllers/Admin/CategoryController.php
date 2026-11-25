<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\ProductImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.taxonomy.categories', [
            'categories' => Category::query()->with('parent')->orderBy('name_ar')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request, ProductImageService $images): RedirectResponse
    {
        $attributes = Arr::except($request->validated(), ['image']) + ['is_active' => $request->boolean('is_active'), 'is_featured' => $request->boolean('is_featured')];
        if ($request->hasFile('image')) {
            $attributes['image_path'] = $images->storeOriginal($request->file('image'), 'categories');
        }
        Category::query()->create($attributes);

        return to_route('admin.categories.index')->with('status', 'تمت إضافة التصنيف.');
    }

    public function update(UpdateCategoryRequest $request, Category $category, ProductImageService $images): RedirectResponse
    {
        $attributes = Arr::except($request->validated(), ['image']) + ['is_active' => $request->boolean('is_active'), 'is_featured' => $request->boolean('is_featured')];
        if ($request->hasFile('image')) {
            $attributes['image_path'] = $images->storeOriginal($request->file('image'), 'categories');
        }
        $previousImage = $category->image_path;
        $category->update($attributes);
        if (isset($attributes['image_path']) && $previousImage && ! Category::query()->where('image_path', $previousImage)->exists()) {
            Storage::disk('public')->delete($previousImage);
        }

        return back()->with('status', 'تم تحديث التصنيف.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_if($category->children()->exists() || $category->products()->exists(), 422, 'لا يمكن حذف تصنيف مرتبط.');
        $image = $category->image_path;
        $category->delete();
        if ($image && ! Category::query()->where('image_path', $image)->exists()) {
            Storage::disk('public')->delete($image);
        }

        return to_route('admin.categories.index')->with('status', 'تم حذف التصنيف.');
    }
}
