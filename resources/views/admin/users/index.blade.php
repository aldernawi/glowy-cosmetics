<x-layouts.admin title="المستخدمون والصلاحيات | Glowy">
    <div><p class="text-sm text-glowy-muted">وصول فريق العمل</p><h1 class="text-3xl font-bold text-glowy-plum">المستخدمون والصلاحيات</h1></div>
    <form class="mt-8 grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-2" method="POST" action="{{ route('admin.users.store') }}">@csrf
        <x-ui.input name="name" label="الاسم" required /><x-ui.input name="email" label="البريد الإلكتروني" type="email" dir="ltr" required /><x-ui.input name="password" label="كلمة المرور" type="password" required />
        <label class="grid gap-2 text-sm font-bold">الدور<select name="role" class="min-h-11 rounded-xl border-glowy-champagne"><option value="catalog-manager">إدارة الكتالوج</option><option value="administrator">مسؤول كامل</option></select></label><x-ui.button type="submit" class="md:col-span-2">إضافة مستخدم</x-ui.button>
    </form>
    <div class="mt-8 overflow-hidden rounded-3xl border border-glowy-champagne bg-white"><table class="w-full text-right text-sm"><thead class="bg-glowy-blush"><tr><th class="p-4">الاسم</th><th class="p-4">البريد</th><th class="p-4">الدور</th></tr></thead><tbody>@foreach($users as $user)<tr class="border-t border-glowy-champagne/60"><td class="p-4 font-bold">{{ $user->name }}</td><td class="p-4" dir="ltr">{{ $user->email }}</td><td class="p-4">{{ $user->roles->pluck('name')->join('، ') ?: 'عميل' }}</td></tr>@endforeach</tbody></table>{{ $users->links() }}</div>
</x-layouts.admin>
