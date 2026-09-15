<x-layouts.storefront title="سلة التسوق | Glowy">
    <section class="mx-auto max-w-7xl px-5 py-12 sm:px-8">
        <h1 class="font-display text-3xl font-bold text-glowy-plum sm:text-4xl">سلة التسوق</h1>
        @if (session('status'))<p class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700" role="status">{{ session('status') }}</p>@endif
        @error('cart')<p class="mt-4 rounded-2xl bg-red-50 px-4 py-3 text-sm font-bold text-red-700" role="alert">{{ $message }}</p>@enderror

        @if ($cart->items->isEmpty())
            <div class="mt-8"><x-ui.empty-state title="سلتك فارغة الآن" description="ابدئي باكتشاف منتجات العناية التي تناسب روتينك." /><div class="text-center"><a href="{{ route('products.index') }}" class="inline-flex rounded-full bg-glowy-rose px-6 py-3 font-bold text-white">اكتشفي المنتجات</a></div></div>
        @else
            <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_22rem]">
                <div class="space-y-4">
                    @foreach($cart->items as $item)
                        @php($product = $item->productVariant->product)
                        @php($image = $product->images->first())
                        <article class="grid grid-cols-[5rem_1fr] gap-4 rounded-3xl border border-glowy-champagne bg-white p-4 sm:grid-cols-[7rem_1fr_auto] sm:items-center">
                            <a href="{{ route('products.show', $product) }}" class="aspect-square rounded-2xl bg-glowy-blush p-2">@if($image)<img src="{{ $image->url }}" alt="{{ $image->alt_text ?: $product->name_ar }}" class="h-full w-full object-contain" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}'">@else<span class="flex h-full items-center justify-center text-2xl text-glowy-rose">✦</span>@endif</a>
                            <div><h2 class="font-display font-bold"><a href="{{ route('products.show', $product) }}">{{ $product->name_ar }}</a></h2><p class="mt-1 text-sm text-glowy-muted">{{ $item->productVariant->name ?: $item->productVariant->size }}</p><p class="mt-2 font-bold text-glowy-rose">{{ $item->productVariant->price }} د.ل</p></div>
                            <div class="col-span-2 flex items-center justify-between gap-3 sm:col-span-1">
                                <form method="POST" action="{{ route('cart.items.update', $item) }}" class="flex items-center gap-2">@csrf @method('PATCH')<label class="sr-only" for="quantity-{{ $item->id }}">الكمية</label><input id="quantity-{{ $item->id }}" type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->productVariant->stock_quantity }}" class="w-20 rounded-full border-glowy-champagne text-center"><button class="text-sm font-bold text-glowy-rose">تحديث</button></form>
                                <form method="POST" action="{{ route('cart.items.destroy', $item) }}">@csrf @method('DELETE')<button class="text-sm font-bold text-red-700">حذف</button></form>
                            </div>
                        </article>
                    @endforeach
                </div>
                <aside class="h-fit rounded-3xl bg-glowy-blush p-6 lg:sticky lg:top-36"><h2 class="font-display text-xl font-bold">ملخص السلة</h2><div class="mt-6 flex justify-between border-b border-glowy-champagne pb-4"><span class="text-glowy-muted">المجموع الفرعي</span><strong>{{ $subtotal }} د.ل</strong></div><p class="mt-4 text-sm leading-6 text-glowy-muted">تحسب رسوم التوصيل بدقة بعد تحديد موقعك في صفحة إتمام الطلب.</p><a href="{{ route('checkout.index') }}" class="mt-6 flex min-h-12 items-center justify-center rounded-full bg-glowy-rose px-6 font-bold text-white transition hover:bg-glowy-plum">إتمام الطلب</a><a href="{{ route('products.index') }}" class="mt-3 flex justify-center text-sm font-bold text-glowy-rose">متابعة التسوق</a></aside>
            </div>
        @endif
    </section>
</x-layouts.storefront>
