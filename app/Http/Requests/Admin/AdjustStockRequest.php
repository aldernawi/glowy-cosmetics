<?php

namespace App\Http\Requests\Admin;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'inventory.adjust']) ?? false;
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

    public function after(): array
    {
        return [function (Validator $validator): void {
            $type = StockMovementType::tryFrom((string) $this->input('type'));
            $delta = $this->integer('delta');

            if (in_array($type, [StockMovementType::Opening, StockMovementType::ManualIn], true) && $delta < 0) {
                $validator->errors()->add('delta', 'هذا النوع من الحركات يزيد المخزون، لذلك يجب أن تكون الكمية موجبة.');
            }

            if (in_array($type, [StockMovementType::Damage, StockMovementType::ManualOut], true) && $delta > 0) {
                $validator->errors()->add('delta', 'هذا النوع من الحركات ينقص المخزون، لذلك يجب أن تكون الكمية سالبة.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'product_variant_id.*' => 'اختاري المتغير المطلوب تعديل مخزونه.',
            'delta.*' => 'أدخلي كمية صحيحة لا تساوي صفرًا.',
            'type.*' => 'اختاري نوع حركة صحيحًا.',
            'notes.max' => 'الملاحظات أطول من المسموح.',
        ];
    }
}
