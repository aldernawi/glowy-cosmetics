<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Services\AdminRoleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeRole', $this->route('user')) ?? false;
    }

    public function rules(): array
    {
        $roles = array_keys(AdminRoleService::LABELS);

        if (! $this->user()->can('grantAdministrator', User::class)) {
            $roles = array_diff($roles, [AdminRoleService::ADMINISTRATOR]);
        }

        return ['role' => ['present', 'nullable', Rule::in($roles)]];
    }

    public function messages(): array
    {
        return ['role.*' => 'اختاري دورًا مسموحًا لك بمنحه.'];
    }
}
