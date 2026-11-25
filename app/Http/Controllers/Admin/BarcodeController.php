<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVariantBarcodeRequest;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;

class BarcodeController extends Controller
{
    public function index(): View
    {
        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($product) => $product->where('is_active', true))
            ->with('product.brand')
            ->get()
            ->sortBy(fn (ProductVariant $variant) => [$variant->product->name_ar, $variant->id])
            ->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'name' => $variant->product->name_ar,
                'name_en' => $variant->product->name_en,
                'variant' => $variant->name ?: $variant->size,
                'brand' => $variant->product->brand?->name_ar,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
            ])
            ->values();

        return view('admin.barcodes.index', ['variants' => $variants]);
    }

    public function update(UpdateVariantBarcodeRequest $request, ProductVariant $variant): JsonResponse
    {
        try {
            $variant->update(['barcode' => $request->validated('barcode')]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'هذا الباركود سُجل لمنتج آخر قبل لحظات.'], 422);
        }

        return response()->json(['barcode' => $variant->barcode]);
    }
}
