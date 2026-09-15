<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRolesRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::query()->with('roles')->latest()->paginate(20)]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = $data['role'];
        unset($data['role']);
        $user = User::query()->create($data);
        $user->assignRole($role);

        return to_route('admin.users.index')->with('status', 'تم إنشاء حساب الموظف.');
    }

    public function update(UpdateAdminUserRolesRequest $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user) && $request->input('role') !== 'administrator', 422, 'لا يمكن إزالة صلاحيتك الإدارية.');
        $user->syncRoles([$request->string('role')->toString()]);

        return to_route('admin.users.index')->with('status', 'تم تحديث دور المستخدم.');
    }
}
