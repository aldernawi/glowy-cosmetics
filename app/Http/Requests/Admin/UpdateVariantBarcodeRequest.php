<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateVariantBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'catalog.manage', 'products.update']) ?? false;
    }

    public function rules(): array
    {
        return ['barcode' => ['present', 'nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9._\-]+$/']];
    }

    /** Naming the product that already owns the code is what makes a duplicate easy to fix. */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('barcode')) {
                return;
            }

            $owner = ProductVariant::query()->with('product')->whereKeyNot($this->route('variant')->getKey())->where('barcode', $this->input('barcode'))->first();

            if ($owner !== null) {
                $validator->errors()->add('barcode', 'هذا الباركود مسجل للمنتج «'.$owner->product->name_ar.'» ('.$owner->sku.').');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'barcode.regex' => 'الباركود يجب أن يحتوي أرقامًا وحروفًا إنجليزية فقط. غيّري لغة لوحة المفاتيح إلى الإنجليزية ثم امسحي مجددًا.',
            'barcode.*' => 'الباركود غير صالح.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('barcode'))) {
            $this->merge(['barcode' => str_replace(['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'], range(0, 9), trim($this->input('barcode')))]);
        }
    }
}
