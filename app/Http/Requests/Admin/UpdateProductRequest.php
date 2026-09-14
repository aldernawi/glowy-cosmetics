<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.access') ?? false;
    }

    public function rules(): array
    {
        /** @var Product $product */ $product = $this->route('product');

        return ['name_ar' => ['required', 'string', 'max:255'], 'name_en' => ['nullable', 'string', 'max:255'], 'slug' => ['required', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product)], 'brand_id' => ['nullable', 'exists:brands,id'], 'category_id' => ['nullable', 'exists:categories,id'], 'description' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean'], 'is_featured' => ['nullable', 'boolean'], 'is_new' => ['nullable', 'boolean']];
    }
}
