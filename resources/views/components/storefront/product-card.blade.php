@props(['product'])
@php($variant = $product->catalogVariant())
@php($activeVariants = $product->variants->where('is_active', true))
<article class="group relative overflow-hidden rounded-[2rem] border border-glowy-champagne bg-white p-4 transition duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-glowy-champagne/40" data-reveal>
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-square overflow-hidden rounded-2xl bg-glowy-blush p-5">
        <span class="pointer-events-none absolute -start-1/2 top-0 z-10 h-full w-1/3 -skew-x-12 bg-gradient-to-r from-transparent via-white/45 to-transparent opacity-0 transition-all duration-700 group-hover:start-[120%] group-hover:opacity-100" aria-hidden="true"></span>
        @if ($product->is_new)<span class="absolute start-3 top-3 z-10 rounded-full bg-white px-3 py-1 text-xs font-bold text-glowy-rose-deep">وصل حديثًا</span>@endif
        @if ($product->images->first())
            <img src="{{ $product->images->first()->url }}" alt="{{ $product->images->first()->alt_text ?: $product->name_ar }}" class="h-full w-full object-contain transition duration-700 group-hover:-rotate-1 group-hover:scale-110" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}'">
        @else
            <span class="flex h-full items-center justify-center text-5xl text-glowy-rose" aria-hidden="true">✦</span>
        @endif
    </a>
    <div class="pt-4">
        @if ($product->brand)<a href="{{ route('brands.show', $product->brand) }}" class="text-xs text-glowy-muted hover:text-glowy-rose-deep">{{ $product->brand->name_ar }}</a>@endif
        <h2 class="mt-1 font-bold"><a href="{{ route('products.show', $product) }}">{{ $product->name_ar }}</a></h2>
        @if ($variant)
            <div class="mt-3 flex items-center gap-2">
                <p class="font-bold text-glowy-rose-deep">{{ $product->variants->where('is_active', true)->count() > 1 ? 'يبدأ من ' : '' }}{{ $variant->price }} د.ل</p>
                @if ($variant->isOnSale())<p class="text-xs text-glowy-muted line-through">{{ $variant->compare_at_price }} د.ل</p>@endif
            </div>
            <p class="mt-2 text-xs font-bold {{ $product->hasAvailableStock() ? 'text-emerald-700' : 'text-glowy-muted' }}">{{ $product->hasAvailableStock() ? 'متوفر' : 'نفد من المخزون' }}</p>
            @if ($product->hasAvailableStock())
                @if ($activeVariants->count() === 1)
                    <form action="{{ route('cart.items.store') }}" method="POST" class="mt-4" data-ajax-cart-form>
                        @csrf
                        <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="flex min-h-11 w-full items-center justify-center rounded-full bg-glowy-rose px-4 text-sm font-bold text-white transition hover:bg-glowy-plum">أضيفي إلى السلة</button>
                    </form>
                @else
                    <a href="{{ route('products.show', $product) }}" class="mt-4 flex min-h-11 items-center justify-center rounded-full border border-glowy-rose px-4 text-sm font-bold text-glowy-rose transition hover:bg-glowy-blush">اختاري الحجم</a>
                @endif
            @endif
        @endif
    </div>
</article>
