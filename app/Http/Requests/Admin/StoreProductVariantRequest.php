<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'catalog.manage', 'products.update']) ?? false;
    }

    public function rules(): array
    {
        return ['sku' => ['required', 'string', 'max:255', Rule::unique('product_variants', 'sku')], 'name' => ['nullable', 'string', 'max:255'], 'size' => ['nullable', 'string', 'max:255'], 'price' => ['required', 'decimal:0,3', 'min:0'], 'compare_at_price' => ['nullable', 'decimal:0,3', 'gte:price'], 'stock_quantity' => ['required', 'integer', 'min:0'], 'low_stock_threshold' => ['required', 'integer', 'min:0'], 'is_active' => ['nullable', 'boolean']];
    }
}
