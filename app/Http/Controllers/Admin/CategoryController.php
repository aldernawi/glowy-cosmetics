<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.taxonomy.categories', [
            'categories' => Category::query()->with('parent')->orderBy('name_ar')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::query()->create($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return to_route('admin.categories.index')->with('status', 'تمت إضافة التصنيف.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return back()->with('status', 'تم تحديث التصنيف.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_if($category->children()->exists() || $category->products()->exists(), 422, 'لا يمكن حذف تصنيف مرتبط.');
        $category->delete();

        return to_route('admin.categories.index')->with('status', 'تم حذف التصنيف.');
    }
}
