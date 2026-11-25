<?php

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'catalog.manage', 'brands.update']) ?? false;
    }

    public function rules(): array
    {
        /** @var Brand $brand */ $brand = $this->route('brand');

        return ['name_ar' => ['required', 'string', 'max:255'], 'name_en' => ['nullable', 'string', 'max:255'], 'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('brands', 'slug')->ignore($brand)], 'description' => ['nullable', 'string'], 'website' => ['nullable', 'url', 'max:255'], 'logo' => ['nullable', 'file', 'extensions:jpg,jpeg,png,webp,gif,avif', 'mimes:jpg,jpeg,png,webp,gif,avif', 'max:10240'], 'is_active' => ['required', 'boolean'], 'is_featured' => ['nullable', 'boolean'], 'sort_order' => ['nullable', 'integer', 'min:0']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->input('slug') ?: $this->input('name_en') ?: $this->input('name_ar'))]);
    }

    public function messages(): array
    {
        return ['slug.unique' => 'الرابط المختصر مستخدم بالفعل. غيّري اسم الماركة أو الرابط.', 'slug.alpha_dash' => 'الرابط المختصر يجب أن يحتوي حروفًا وأرقامًا وشرطات فقط.'];
    }
}
