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
    <div class="mt-8 grid gap-4">
        @forelse($products as $product)
            <article class="rounded-3xl border border-glowy-champagne bg-white p-5"><div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-bold text-glowy-plum">{{ $product->name_ar }}</h2><p class="text-sm text-glowy-muted">{{ $product->brand?->name_ar ?? 'بدون ماركة' }}</p></div><span class="rounded-full bg-glowy-blush px-3 py-1 text-sm">{{ $product->variants->count() }} متغيرات</span></div>
                <div class="mt-4 grid gap-2 text-sm">@foreach($product->variants as $variant)<div class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-glowy-ivory px-3 py-2"><span dir="ltr">{{ $variant->sku }}</span><span>{{ $variant->price }} د.ل · {{ $variant->stock_quantity }} متاح</span>@if(! $variant->is_default)<form method="POST" action="{{ route('admin.variants.destroy', $variant) }}">@csrf @method('DELETE')<button class="text-xs font-bold text-rose-700">حذف</button></form>@endif</div>@endforeach</div>
                <form class="mt-4 grid gap-3 md:grid-cols-3" method="POST" action="{{ route('admin.products.variants.store', $product) }}">@csrf <x-ui.input name="sku" label="SKU" required /><x-ui.input name="price" label="السعر" inputmode="decimal" required /><x-ui.input name="stock_quantity" label="الكمية الافتتاحية" type="number" required /><input type="hidden" name="low_stock_threshold" value="5"><input type="hidden" name="is_active" value="1"><x-ui.button type="submit">إضافة متغير</x-ui.button></form>
                <form class="mt-3 flex flex-wrap items-end gap-3" method="POST" action="{{ route('admin.products.images.store', $product) }}" enctype="multipart/form-data">@csrf <label class="grid gap-1 text-sm font-bold">صورة المنتج<input class="text-sm" type="file" name="image" accept="image/*" required></label><x-ui.input name="alt" label="وصف الصورة" /><x-ui.button type="submit">رفع الصورة</x-ui.button></form>
                @if($product->images->isNotEmpty())<div class="mt-3 flex flex-wrap gap-2">@foreach($product->images as $image)<div class="flex items-center gap-2 rounded-xl border border-glowy-champagne px-3 py-2 text-xs"><span>{{ $image->is_primary ? 'أساسية' : ($image->alt_text ?: 'صورة منتج') }}</span>@if(! $image->is_primary)<form method="POST" action="{{ route('admin.images.primary', $image) }}">@csrf @method('PATCH')<button class="font-bold text-glowy-rose">تعيين أساسية</button></form>@endif<form method="POST" action="{{ route('admin.images.destroy', $image) }}">@csrf @method('DELETE')<button class="font-bold text-rose-700">حذف</button></form></div>@endforeach</div>@endif
            </article>
        @empty <p class="rounded-3xl bg-white p-8 text-center text-glowy-muted">لا توجد منتجات بعد.</p>@endforelse
        {{ $products->links() }}
    </div>
</x-layouts.admin>
