<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomepageContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.access') ?? false;
    }

    public function rules(): array
    {
        return [
            'hero_title' => ['required', 'string', 'max:120'],
            'hero_subtitle' => ['required', 'string', 'max:500'],
            'hero_cta_label' => ['required', 'string', 'max:40'],
            'hero_image' => ['nullable', 'image', 'max:5120'],
            'announcement' => ['nullable', 'string', 'max:180'],
            'promo_title' => ['sometimes', 'required', 'string', 'max:120'],
            'promo_body' => ['sometimes', 'required', 'string', 'max:500'],
            'promo_cta_label' => ['sometimes', 'required', 'string', 'max:40'],
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
