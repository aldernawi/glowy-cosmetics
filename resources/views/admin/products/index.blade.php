<x-layouts.admin title="المنتجات | Glowy">
    <h1 class="text-3xl font-bold text-glowy-plum">المنتجات</h1>
    <form class="mt-8 grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-2" method="POST" action="{{ route('admin.products.store') }}">
        @csrf
        <x-ui.input name="name_ar" label="اسم المنتج" required />
        <x-ui.input name="slug" label="الرابط المختصر" dir="ltr" required />
        <x-ui.input name="sku" label="SKU للمتغير الافتراضي" dir="ltr" required />
        <x-ui.input name="price" label="السعر" inputmode="decimal" dir="ltr" required />
        <label class="grid gap-2 text-sm font-bold">الماركة<select name="brand_id" class="min-h-11 rounded-xl border-glowy-champagne"><option value="">—</option>@foreach ($brands as $brand)<option value="{{ $brand->id }}">{{ $brand->name_ar }}</option>@endforeach</select></label>
        <label class="grid gap-2 text-sm font-bold">التصنيف<select name="category_id" class="min-h-11 rounded-xl border-glowy-champagne"><option value="">—</option>@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name_ar }}</option>@endforeach</select></label>
        <x-ui.button type="submit" class="md:col-span-2">إضافة المنتج</x-ui.button>
    </form>
</x-layouts.admin>
