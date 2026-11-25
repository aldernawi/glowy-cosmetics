<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxonomyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'catalog.manage', 'categories.create', 'categories.update']) ?? false;
    }

    public function rules(): array
    {
        return ['name_ar' => ['required', 'string', 'max:255'], 'name_en' => ['nullable', 'string', 'max:255'], 'slug' => ['required', 'alpha_dash', 'max:255'], 'description' => ['nullable', 'string'], 'sort_order' => ['nullable', 'integer', 'min:0'], 'is_active' => ['nullable', 'boolean']];
    }
}
