<x-layouts.storefront title="منتجات العناية | Glowy" description="تصفحي منتجات العناية بالبشرة والشعر والجسم المختارة من Glowy." :canonical="route('products.index')" :robots="request()->query() ? 'noindex,follow' : 'index,follow'">
    <section class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold text-glowy-rose">اكتشفي اختيارات Glowy</p>
                <h1 class="mt-2 font-display text-3xl font-bold text-glowy-plum sm:text-4xl">منتجات العناية</h1>
            </div>
            <p class="text-sm text-glowy-muted">{{ $products->total() }} منتج</p>
        </div>

        <form method="GET" action="{{ route('products.index') }}" class="mt-8 rounded-3xl border border-glowy-champagne bg-white p-5" aria-label="فلترة المنتجات">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <label class="lg:col-span-2"><span class="mb-2 block text-sm font-bold">البحث</span><input name="q" value="{{ request('q') }}" placeholder="ابحثي عن منتج، ماركة أو احتياج لبشرتك..." class="w-full rounded-2xl border-glowy-champagne bg-glowy-ivory"></label>
                <label><span class="mb-2 block text-sm font-bold">الماركة</span><select name="brand" class="w-full rounded-2xl border-glowy-champagne bg-glowy-ivory"><option value="">كل الماركات</option>@foreach($brands as $brand)<option value="{{ $brand->slug }}" @selected(request('brand') === $brand->slug)>{{ $brand->name_ar }}</option>@endforeach</select></label>
                <label><span class="mb-2 block text-sm font-bold">التصنيف</span><select name="category" class="w-full rounded-2xl border-glowy-champagne bg-glowy-ivory"><option value="">كل التصنيفات</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name_ar }}</option>@endforeach</select></label>
                <label><span class="mb-2 block text-sm font-bold">احتياج البشرة</span><select name="concern" class="w-full rounded-2xl border-glowy-champagne bg-glowy-ivory"><option value="">كل الاحتياجات</option>@foreach($concerns as $concern)<option value="{{ $concern->slug }}" @selected(request('concern') === $concern->slug)>{{ $concern->name_ar }}</option>@endforeach</select></label>
                <label><span class="mb-2 block text-sm font-bold">نوع البشرة</span><select name="skin_type" class="w-full rounded-2xl border-glowy-champagne bg-glowy-ivory"><option value="">كل الأنواع</option>@foreach($skinTypes as $skinType)<option value="{{ $skinType->slug }}" @selected(request('skin_type') === $skinType->slug)>{{ $skinType->name_ar }}</option>@endforeach</select></label>
                <div class="grid grid-cols-2 gap-2"><label><span class="mb-2 block text-sm font-bold">السعر من</span><input type="number" min="0" step="0.001" name="min_price" value="{{ request('min_price') }}" class="w-full rounded-2xl border-glowy-champagne bg-glowy-ivory"></label><label><span class="mb-2 block text-sm font-bold">إلى</span><input type="number" min="0" step="0.001" name="max_price" value="{{ request('max_price') }}" class="w-full rounded-2xl border-glowy-champagne bg-glowy-ivory"></label></div>
                <label><span class="mb-2 block text-sm font-bold">الترتيب</span><select name="sort" class="w-full rounded-2xl border-glowy-champagne bg-glowy-ivory"><option value="">الأحدث</option><option value="price_asc" @selected(request('sort') === 'price_asc')>السعر: الأقل أولًا</option><option value="price_desc" @selected(request('sort') === 'price_desc')>السعر: الأعلى أولًا</option><option value="name" @selected(request('sort') === 'name')>الاسم</option></select></label>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-4 text-sm"><label class="flex items-center gap-2"><input type="checkbox" name="availability" value="in_stock" @checked(request('availability') === 'in_stock')> متوفر الآن</label><label class="flex items-center gap-2"><input type="checkbox" name="sale" value="1" @checked(request()->boolean('sale'))> عليه تخفيض</label><label class="flex items-center gap-2"><input type="checkbox" name="new" value="1" @checked(request()->boolean('new'))> وصل حديثًا</label><button class="rounded-full bg-glowy-plum px-6 py-2.5 font-bold text-white">عرض النتائج</button><a href="{{ route('products.index') }}" class="font-bold text-glowy-rose">مسح الفلاتر</a></div>
        </form>

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($products as $product)
                <x-storefront.product-card :product="$product" />
            @empty
                <div class="sm:col-span-2 lg:col-span-3 xl:col-span-4"><x-ui.empty-state title="لم نجد منتجات بهذه المواصفات" description="جرّبي إزالة بعض الفلاتر أو البحث بكلمة أخرى." /></div>
            @endforelse
        </div>
        <div class="mt-10">{{ $products->links() }}</div>
    </section>
</x-layouts.storefront>
