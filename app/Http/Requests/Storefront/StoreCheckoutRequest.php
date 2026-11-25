<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return ['customer_name' => ['required', 'string', 'max:255'], 'phone' => ['required', 'string', 'max:30'], 'secondary_phone' => ['nullable', 'string', 'max:30'], 'city_region' => ['nullable', 'string', 'max:120'], 'address' => ['nullable', 'string', 'max:255'], 'landmark' => ['nullable', 'string', 'max:255'], 'place_id' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000'], 'latitude' => ['required', 'decimal:0,7', 'between:-90,90'], 'longitude' => ['required', 'decimal:0,7', 'between:-180,180'], 'location_confirmed' => ['accepted']];
    }
}
