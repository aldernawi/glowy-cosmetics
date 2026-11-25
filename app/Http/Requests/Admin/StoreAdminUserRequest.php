<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Services\AdminRoleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['admin.access', 'users.manage']) ?? false;
    }

    public function rules(): array
    {
        $roles = array_keys(AdminRoleService::LABELS);

        if (! $this->user()->can('grantAdministrator', User::class)) {
            $roles = array_diff($roles, [AdminRoleService::ADMINISTRATOR]);
        }

        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')], 'password' => ['required', 'string', 'min:8'], 'role' => ['required', Rule::in($roles)]];
    }

    public function messages(): array
    {
        return [
            'name.*' => 'أدخلي اسم الموظف.',
            'email.unique' => 'هذا البريد مستخدم بالفعل.',
            'email.*' => 'أدخلي بريدًا إلكترونيًا صحيحًا.',
            'password.*' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'role.*' => 'اختاري دورًا مسموحًا لك بمنحه.',
        ];
    }
}
