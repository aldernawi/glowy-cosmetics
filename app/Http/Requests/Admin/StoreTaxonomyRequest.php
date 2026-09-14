<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxonomyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.access') ?? false;
    }

    public function rules(): array
    {
        return ['name_ar' => ['required', 'string', 'max:255'], 'name_en' => ['nullable', 'string', 'max:255'], 'slug' => ['required', 'alpha_dash', 'max:255'], 'is_active' => ['nullable', 'boolean']];
    }
}
