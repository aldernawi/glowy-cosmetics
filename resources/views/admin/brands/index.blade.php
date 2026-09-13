<x-layouts.admin title="الماركات | Glowy">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-bold text-glowy-rose">الكتالوج</p>
            <h1 class="mt-1 text-3xl font-bold text-glowy-plum">الماركات</h1>
        </div>
    </div>

    <form class="mt-8 grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-4" method="POST" action="{{ route('admin.brands.store') }}">
        @csrf
        <x-ui.input name="name_ar" label="الاسم بالعربية" required />
        <x-ui.input name="slug" label="الرابط المختصر" dir="ltr" required />
        <label class="flex min-h-11 items-center gap-2 self-end text-sm font-bold"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> مفعّلة</label>
        <x-ui.button type="submit" class="self-end">إضافة ماركة</x-ui.button>
    </form>

    <div class="mt-6 overflow-hidden rounded-3xl border border-glowy-champagne bg-white">
        @forelse ($brands as $brand)
            <div class="flex items-center justify-between border-b border-glowy-blush px-6 py-4 last:border-0">
                <div><p class="font-bold text-glowy-plum">{{ $brand->name_ar }}</p><p class="text-sm text-glowy-muted">{{ $brand->slug }}</p></div>
                <span class="rounded-full px-3 py-1 text-xs {{ $brand->is_active ? 'bg-glowy-blush text-glowy-rose' : 'bg-slate-100 text-slate-500' }}">{{ $brand->is_active ? 'مفعّلة' : 'مخفية' }}</span>
            </div>
        @empty
            <x-ui.empty-state title="لا توجد ماركات بعد" description="أضيفي أول ماركة لتظهر ضمن كتالوج Glowy." />
        @endforelse
    </div>
</x-layouts.admin>
