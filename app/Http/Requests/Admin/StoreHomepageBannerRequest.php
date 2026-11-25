<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreHomepageBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'content.manage']) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'file', 'extensions:jpg,jpeg,png,webp,gif,avif', 'mimes:jpg,jpeg,png,webp,gif,avif', 'max:10240'],
            'cta_label' => ['nullable', 'string', 'max:40'],
            'cta_url' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value && ! str_starts_with($value, '/') && filter_var($value, FILTER_VALIDATE_URL) === false) {
                    $fail('رابط الزر يجب أن يكون رابطًا كاملاً أو مسارًا يبدأ بـ /.');
                }
            }],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
