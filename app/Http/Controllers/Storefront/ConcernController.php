<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Concern;
use Illuminate\Contracts\View\View;

class ConcernController extends Controller
{
    public function show(Concern $concern): View
    {
        abort_unless($concern->is_active, 404);

        return view('storefront.products.collection', [
            'eyebrow' => 'عناية تناسب احتياج بشرتك',
            'title' => $concern->name_ar,
            'description' => $concern->description,
            'products' => $concern->products()->where('is_active', true)
                ->with(['brand', 'images', 'variants' => fn ($query) => $query->where('is_active', true)])
                ->latest()->paginate(24),
        ]);
    }
}
