<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return ['customer_name' => ['required', 'string', 'max:255'], 'phone' => ['required', 'string', 'max:30'], 'latitude' => ['required', 'decimal:0,7', 'between:-90,90'], 'longitude' => ['required', 'decimal:0,7', 'between:-180,180']];
    }
}
