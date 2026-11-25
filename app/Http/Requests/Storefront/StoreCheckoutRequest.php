<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    private const PHONE_PATTERN = 'regex:/^(?:\+|00)?[0-9]{9,15}$/';

    private const LOCATION_MESSAGE = 'حددي موقع التسليم على الخريطة ثم أكديه.';

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', self::PHONE_PATTERN],
            'secondary_phone' => ['nullable', 'string', 'max:30', self::PHONE_PATTERN],
            'city_region' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'place_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['required', 'decimal:0,7', 'between:-90,90'],
            'longitude' => ['required', 'decimal:0,7', 'between:-180,180'],
            'location_confirmed' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.*' => 'أدخلي الاسم الكامل.',
            'phone.*' => 'أدخلي رقم هاتف صحيحًا، مثل 0912345678.',
            'secondary_phone.*' => 'رقم الهاتف الإضافي غير صحيح. صححيه أو اتركيه فارغًا.',
            'city_region.*' => 'اسم المدينة أو المنطقة أطول من المسموح.',
            'address.*' => 'العنوان التفصيلي أطول من المسموح.',
            'landmark.*' => 'وصف العلامة المميزة أطول من المسموح.',
            'notes.*' => 'ملاحظات الطلب أطول من المسموح.',
            'place_id.*' => self::LOCATION_MESSAGE,
            'latitude.*' => self::LOCATION_MESSAGE,
            'longitude.*' => self::LOCATION_MESSAGE,
            'location_confirmed.*' => 'أكدي موقع التسليم قبل إتمام الطلب.',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['phone', 'secondary_phone'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => $this->normalizePhone($this->input($field))]);
            }
        }
    }

    private function normalizePhone(string $phone): string
    {
        $latinDigits = str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $phone,
        );

        return preg_replace('/[\s\-().]+/u', '', $latinDigits) ?? $latinDigits;
    }
}
