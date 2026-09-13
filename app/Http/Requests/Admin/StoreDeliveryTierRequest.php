<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.access') ?? false;
    }

    public function rules(): array
    {
        return ['min_distance_km' => ['required', 'decimal:0,3', 'min:0'], 'max_distance_km' => ['required', 'decimal:0,3', 'gt:min_distance_km'], 'fee' => ['required', 'decimal:0,3', 'min:0']];
    }
}
