<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomepageSectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'content.manage']) ?? false;
    }

    public function rules(): array
    {
        return [
            'sections' => ['required', 'array'],
            'sections.*.key' => ['required', 'string', 'in:concerns,categories,new_arrivals,best_sellers,promotion,brands,routine,why_glowy,banners'],
            'sections.*.is_visible' => ['nullable', 'boolean'],
            'sections.*.sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
