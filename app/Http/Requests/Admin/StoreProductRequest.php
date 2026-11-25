<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'catalog.manage', 'products.create']) ?? false;
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('products', 'slug')],
            'sku' => ['required', 'string', 'max:255', Rule::unique('product_variants', 'sku')],
            'barcode' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9._\-]+$/', Rule::unique('product_variants', 'barcode')],
            'price' => ['required', 'decimal:0,3', 'min:0'],
            'compare_at_price' => ['nullable', 'decimal:0,3', 'gte:price'],
            'default_variant_name' => ['nullable', 'string', 'max:255'],
            'default_variant_size' => ['nullable', 'string', 'max:255'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'usage' => ['nullable', 'string'],
            'warnings' => ['nullable', 'string'],
            'skin_type_ids' => ['nullable', 'array'],
            'skin_type_ids.*' => ['integer', 'exists:skin_types,id'],
            'concern_ids' => ['nullable', 'array'],
            'concern_ids.*' => ['integer', 'exists:concerns,id'],
            'ingredient_ids' => ['nullable', 'array'],
            'ingredient_ids.*' => ['integer', 'exists:ingredients,id'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_new' => ['sometimes', 'boolean'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['file', 'extensions:jpg,jpeg,png,webp,gif,avif', 'mimes:jpg,jpeg,png,webp,gif,avif', 'max:10240'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->input('slug') ?: $this->input('name_en') ?: $this->input('name_ar'))]);
    }

    public function messages(): array
    {
        return ['slug.unique' => 'الرابط المختصر مستخدم بالفعل. غيّري اسم المنتج أو الرابط.', 'slug.alpha_dash' => 'الرابط المختصر يجب أن يحتوي حروفًا وأرقامًا وشرطات فقط.'];
    }
}
