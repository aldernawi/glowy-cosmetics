<x-layouts.admin title="التصنيفات | Glowy">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm text-glowy-muted">تنظيم واجهة المتجر</p><h1 class="text-3xl font-bold text-glowy-plum">التصنيفات</h1></div><span class="rounded-full bg-glowy-blush px-4 py-2 text-sm font-bold text-glowy-rose">{{ $categories->count() }} تصنيف</span></div>
    <form class="mt-8 grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-2" method="POST" action="{{ route('admin.categories.store') }}">@csrf
        <x-ui.input name="name_ar" label="اسم التصنيف" required />
        <x-ui.input name="slug" label="الرابط المختصر" dir="ltr" required />
        <label class="grid gap-2 text-sm font-bold">التصنيف الأب<select name="parent_id" class="min-h-11 rounded-xl border-glowy-champagne"><option value="">تصنيف رئيسي</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name_ar }}</option>@endforeach</select></label>
        <label class="flex items-center gap-2 self-end pb-3 font-bold"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> نشط</label>
        <x-ui.button type="submit" class="md:col-span-2">إضافة التصنيف</x-ui.button>
    </form>
    <div class="mt-8 overflow-hidden rounded-3xl border border-glowy-champagne bg-white"><table class="w-full text-right text-sm"><thead class="bg-glowy-blush text-glowy-muted"><tr><th class="p-4">التصنيف</th><th class="p-4">الأب</th><th class="p-4">الحالة</th></tr></thead><tbody>@forelse($categories as $category)<tr class="border-t border-glowy-champagne/50"><td class="p-4 font-bold">{{ $category->name_ar }}</td><td class="p-4">{{ $category->parent?->name_ar ?? 'رئيسي' }}</td><td class="p-4">{{ $category->is_active ? 'نشط' : 'مخفي' }}</td></tr>@empty<tr><td colspan="3" class="p-8 text-center text-glowy-muted">أضيفي أول تصنيف لبدء التنظيم.</td></tr>@endforelse</tbody></table></div>
</x-layouts.admin>
