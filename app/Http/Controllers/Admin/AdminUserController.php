<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRolesRequest;
use App\Models\User;
use App\Services\AdminRoleService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->with(['roles.permissions', 'permissions'])->latest()->paginate(20),
            'roles' => AdminRoleService::LABELS,
        ]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(fn () => User::query()->create(Arr::except($data, ['role']))->assignRole($data['role']));

        return to_route('admin.users.index')->with('status', 'تم إنشاء حساب الموظف.');
    }

    public function update(UpdateAdminUserRolesRequest $request, User $user, AdminRoleService $roles): RedirectResponse
    {
        $role = $request->validated('role');

        if ($request->user()->is($user) && $role !== AdminRoleService::ADMINISTRATOR) {
            return back()->withErrors(['role' => 'لا يمكنك إزالة صلاحيتك الإدارية بنفسك.']);
        }

        try {
            $roles->assign($user, $role);
        } catch (DomainException) {
            return back()->withErrors(['role' => 'لا يمكن إزالة صلاحية آخر مسؤول في المتجر.']);
        }

        return to_route('admin.users.index')->with('status', $role === null ? 'تم سحب الصلاحيات الإدارية من المستخدم.' : 'تم تحديث دور المستخدم.');
    }
}
