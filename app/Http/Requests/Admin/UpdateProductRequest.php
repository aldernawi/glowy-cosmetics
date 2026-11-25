<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'catalog.manage', 'products.update']) ?? false;
    }

    public function rules(): array
    {
        /** @var Product $product */ $product = $this->route('product');

        return ['name_ar' => ['required', 'string', 'max:255'], 'name_en' => ['nullable', 'string', 'max:255'], 'slug' => ['required', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product)], 'brand_id' => ['nullable', 'exists:brands,id'], 'category_id' => ['nullable', 'exists:categories,id'], 'description' => ['nullable', 'string'], 'benefits' => ['nullable', 'string'], 'usage' => ['nullable', 'string'], 'warnings' => ['nullable', 'string'], 'compare_at_price' => ['nullable', 'decimal:0,3', 'min:0'], 'default_variant_name' => ['nullable', 'string', 'max:255'], 'default_variant_size' => ['nullable', 'string', 'max:255'], 'skin_type_ids' => ['nullable', 'array'], 'skin_type_ids.*' => ['integer', 'exists:skin_types,id'], 'concern_ids' => ['nullable', 'array'], 'concern_ids.*' => ['integer', 'exists:concerns,id'], 'ingredient_ids' => ['nullable', 'array'], 'ingredient_ids.*' => ['integer', 'exists:ingredients,id'], 'is_active' => ['nullable', 'boolean'], 'is_featured' => ['nullable', 'boolean'], 'is_new' => ['nullable', 'boolean'], 'images' => ['nullable', 'array', 'max:8'], 'images.*' => ['file', 'extensions:jpg,jpeg,png,webp,gif,avif', 'mimes:jpg,jpeg,png,webp,gif,avif', 'max:10240']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled('compare_at_price') || $validator->errors()->has('compare_at_price')) {
                return;
            }

            $price = $this->route('product')->variants()->where('is_default', true)->value('price');

            if ($price !== null && BigDecimal::of($this->string('compare_at_price')->toString())->isLessThan(BigDecimal::of((string) $price))) {
                $validator->errors()->add('compare_at_price', 'السعر قبل التخفيض يجب ألا يقل عن سعر الخيار الافتراضي.');
            }
        }];
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
