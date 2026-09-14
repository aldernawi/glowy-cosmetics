<x-layouts.admin title="{{ $label }} | Glowy">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm text-glowy-muted">تنظيم الكتالوج</p><h1 class="text-3xl font-bold text-glowy-plum">{{ $label }}</h1></div><span class="rounded-full bg-glowy-blush px-4 py-2 text-sm font-bold text-glowy-rose">{{ $items->count() }} عنصر</span></div>
    <form class="mt-8 grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-2" method="POST" action="{{ route('admin.'.$route.'.store') }}">@csrf
        <x-ui.input name="name_ar" label="الاسم بالعربية" required /><x-ui.input name="slug" label="الرابط المختصر" dir="ltr" required />
        <label class="flex items-center gap-2 self-end pb-3 font-bold"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> نشط</label>
        <x-ui.button type="submit">إضافة</x-ui.button>
    </form>
    <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">@forelse($items as $item)<article class="rounded-2xl border border-glowy-champagne bg-white p-5"><p class="font-bold text-glowy-plum">{{ $item->name_ar }}</p><p class="mt-1 text-xs text-glowy-muted" dir="ltr">{{ $item->slug }}</p><p class="mt-3 text-sm">{{ $item->is_active ? 'نشط' : 'مخفي' }}</p></article>@empty<p class="rounded-2xl bg-white p-6 text-glowy-muted">أضيفي أول عنصر من هذه القائمة.</p>@endforelse</div>
</x-layouts.admin>
