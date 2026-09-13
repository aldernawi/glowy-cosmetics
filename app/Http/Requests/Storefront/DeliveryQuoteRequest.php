<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryQuoteRequest extends FormRequest
{
    public function rules(): array
    {
        return ['latitude' => ['required', 'decimal:0,7', 'between:-90,90'], 'longitude' => ['required', 'decimal:0,7', 'between:-180,180']];
    }
}
