<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomepageContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'content.manage']) ?? false;
    }

    public function rules(): array
    {
        return [
            'hero_title' => ['required', 'string', 'max:120'],
            'hero_subtitle' => ['required', 'string', 'max:500'],
            'hero_cta_label' => ['required', 'string', 'max:40'],
            'hero_cta_url' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value && ! str_starts_with($value, '/') && filter_var($value, FILTER_VALIDATE_URL) === false) {
                    $fail('رابط زر Hero يجب أن يكون رابطًا كاملاً أو مسارًا يبدأ بـ /.');
                }
            }],
            'hero_image' => ['nullable', 'file', 'extensions:jpg,jpeg,png,webp,gif,avif', 'max:10240'],
            'announcement' => ['nullable', 'string', 'max:180'],
            'promo_title' => ['sometimes', 'required', 'string', 'max:120'],
            'promo_body' => ['sometimes', 'required', 'string', 'max:500'],
            'promo_cta_label' => ['sometimes', 'required', 'string', 'max:40'],
            'promo_cta_url' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value && ! str_starts_with($value, '/') && filter_var($value, FILTER_VALIDATE_URL) === false) {
                    $fail('رابط زر البانر يجب أن يكون رابطًا كاملاً أو مسارًا يبدأ بـ /.');
                }
            }],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'regex:/^[0-9]{8,20}$/'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:255'],
            'working_hours' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:170'],
            'featured_category_ids' => ['sometimes', 'array', 'max:8'],
            'featured_category_ids.*' => ['integer', 'exists:categories,id'],
            'featured_brand_ids' => ['sometimes', 'array', 'max:8'],
            'featured_brand_ids.*' => ['integer', 'exists:brands,id'],
            'featured_concern_ids' => ['sometimes', 'array', 'max:8'],
            'featured_concern_ids.*' => ['integer', 'exists:concerns,id'],
            'new_product_ids' => ['sometimes', 'array', 'max:8'],
            'new_product_ids.*' => ['integer', 'exists:products,id'],
            'best_seller_ids' => ['sometimes', 'array', 'max:8'],
            'best_seller_ids.*' => ['integer', 'exists:products,id'],
        ];
    }
}
