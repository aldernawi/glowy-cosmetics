<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Contracts\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('storefront.brands.index', ['brands' => Brand::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name_ar')->paginate(24)]);
    }

    public function show(Brand $brand): View
    {
        abort_unless($brand->is_active, 404);

        return view('storefront.products.collection', [
            'eyebrow' => 'تسوّقي حسب الماركة',
            'title' => $brand->name_ar,
            'description' => $brand->description,
            'products' => $brand->products()->sellable()
                ->with(['brand', 'images', 'variants' => fn ($query) => $query->where('is_active', true)])
                ->latest()->paginate(24),
        ]);
    }
}
