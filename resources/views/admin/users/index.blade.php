<x-layouts.admin title="المستخدمون والصلاحيات | Glowy">
    @php($grantableRoles = auth()->user()->can('grantAdministrator', App\Models\User::class) ? $roles : Arr::except($roles, App\Services\AdminRoleService::ADMINISTRATOR))
    <div><p class="text-sm text-glowy-muted">وصول فريق العمل</p><h1 class="text-3xl font-bold text-glowy-plum">المستخدمون والصلاحيات</h1></div>
    @if (session('status'))<p class="mt-5 rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-800" role="status">{{ session('status') }}</p>@endif
    @if ($errors->any())<div class="mt-5 rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-800" role="alert"><ul class="grid gap-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form class="mt-8 grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-2" method="POST" action="{{ route('admin.users.store') }}">@csrf
        <x-ui.input name="name" label="الاسم" :value="old('name')" required /><x-ui.input name="email" label="البريد الإلكتروني" type="email" dir="ltr" :value="old('email')" required /><x-ui.input name="password" label="كلمة المرور" type="password" required />
        <label class="grid gap-2 text-sm font-bold">الدور<select name="role" class="min-h-11 rounded-xl border-glowy-champagne">@foreach ($grantableRoles as $role => $label)<option value="{{ $role }}">{{ $label }}</option>@endforeach</select></label><x-ui.button type="submit" class="md:col-span-2">إضافة مستخدم</x-ui.button>
    </form>
    <div class="mt-8 overflow-x-auto rounded-3xl border border-glowy-champagne bg-white"><table class="w-full text-right text-sm"><thead class="bg-glowy-blush"><tr><th class="p-4">الاسم</th><th class="p-4">البريد</th><th class="p-4">الدور</th></tr></thead><tbody>@foreach ($users as $user)
        @php($currentRole = $user->roles->first()?->name)
        <tr class="border-t border-glowy-champagne/60"><td class="p-4 font-bold">{{ $user->name }}</td><td class="p-4" dir="ltr">{{ $user->email }}</td><td class="p-4">
            @if (auth()->user()->is($user) || ! auth()->user()->can('changeRole', $user))
                {{ $roles[$currentRole] ?? 'بدون دور إداري' }}@if (auth()->user()->is($user)) <span class="text-xs text-glowy-muted">(حسابك)</span>@endif
            @else
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex flex-wrap items-center gap-3">@csrf @method('PATCH')
                    <select name="role" aria-label="دور {{ $user->name }}" class="min-h-11 rounded-xl border-glowy-champagne"><option value="">عميل (بدون صلاحيات إدارية)</option>@foreach ($grantableRoles as $role => $label)<option value="{{ $role }}" @selected($currentRole === $role)>{{ $label }}</option>@endforeach</select>
                    <button class="text-sm font-bold text-glowy-rose">حفظ الدور</button>
                </form>
            @endif
        </td></tr>@endforeach</tbody></table></div>
    <div class="mt-6">{{ $users->links() }}</div>
</x-layouts.admin>
