<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'catalog.manage', 'products.update']) ?? false;
    }

    public function rules(): array
    {
        return ['image' => ['nullable', 'file', 'extensions:jpg,jpeg,png,webp,gif,avif', 'mimes:jpg,jpeg,png,webp,gif,avif', 'max:10240', 'required_without:images'], 'images' => ['nullable', 'array', 'max:8', 'required_without:image'], 'images.*' => ['file', 'extensions:jpg,jpeg,png,webp,gif,avif', 'mimes:jpg,jpeg,png,webp,gif,avif', 'max:10240'], 'alt' => ['nullable', 'string', 'max:255']];
    }
}
