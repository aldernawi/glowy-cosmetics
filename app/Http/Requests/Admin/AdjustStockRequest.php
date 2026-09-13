<?php

namespace App\Http\Requests\Admin;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.access') ?? false;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')],
            'delta' => ['required', 'integer', 'not_in:0'],
            'type' => ['required', Rule::in([
                StockMovementType::Opening->value,
                StockMovementType::ManualIn->value,
                StockMovementType::Adjustment->value,
                StockMovementType::Damage->value,
                StockMovementType::ManualOut->value,
            ])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
