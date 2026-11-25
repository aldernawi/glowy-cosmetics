<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'catalog.manage', 'products.update']) ?? false;
    }

    public function rules(): array
    {
        /** @var ProductVariant $variant */ $variant = $this->route('variant');

        return ['sku' => ['required', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($variant)], 'barcode' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9._\-]+$/', Rule::unique('product_variants', 'barcode')->ignore($variant)], 'name' => ['nullable', 'string', 'max:255'], 'size' => ['nullable', 'string', 'max:255'], 'price' => ['required', 'decimal:0,3', 'min:0'], 'compare_at_price' => ['nullable', 'decimal:0,3', 'gte:price'], 'stock_quantity' => ['sometimes', 'required', 'integer', 'min:0'], 'stock_quantity_original' => ['nullable', 'integer', 'min:0'], 'low_stock_threshold' => ['required', 'integer', 'min:0'], 'is_active' => ['nullable', 'boolean']];
    }
}
