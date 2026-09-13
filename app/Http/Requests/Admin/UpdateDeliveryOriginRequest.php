<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryOriginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.access') ?? false;
    }

    public function rules(): array
    {
        return ['store_latitude' => ['required', 'decimal:0,7', 'between:-90,90'], 'store_longitude' => ['required', 'decimal:0,7', 'between:-180,180'], 'store_formatted_address' => ['nullable', 'string', 'max:255'], 'store_place_id' => ['nullable', 'string', 'max:255']];
    }
}
