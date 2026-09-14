@props(['product'])
@php($variant = $product->catalogVariant())
<article class="group overflow-hidden rounded-3xl border border-glowy-champagne bg-white p-4 transition duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-glowy-champagne/40">
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-square overflow-hidden rounded-2xl bg-glowy-blush p-5">
        @if ($product->is_new)<span class="absolute start-3 top-3 z-10 rounded-full bg-white px-3 py-1 text-xs font-bold text-glowy-rose">وصل حديثًا</span>@endif
        @if ($product->images->first())
            <img src="{{ $product->images->first()->path }}" alt="{{ $product->images->first()->alt_text ?: $product->name_ar }}" class="h-full w-full object-contain transition duration-500 group-hover:scale-105" loading="lazy">
        @else
            <span class="flex h-full items-center justify-center text-5xl text-glowy-rose" aria-hidden="true">✦</span>
        @endif
    </a>
    <div class="pt-4">
        @if ($product->brand)<a href="{{ route('brands.show', $product->brand) }}" class="text-xs text-glowy-muted hover:text-glowy-rose">{{ $product->brand->name_ar }}</a>@endif
        <h2 class="mt-1 font-bold"><a href="{{ route('products.show', $product) }}">{{ $product->name_ar }}</a></h2>
        @if ($variant)
            <div class="mt-3 flex items-center gap-2">
                <p class="font-bold text-glowy-rose">{{ $product->variants->where('is_active', true)->count() > 1 ? 'يبدأ من ' : '' }}{{ $variant->price }} د.ل</p>
                @if ($variant->isOnSale())<p class="text-xs text-glowy-muted line-through">{{ $variant->compare_at_price }} د.ل</p>@endif
            </div>
            <p class="mt-2 text-xs font-bold {{ $product->hasAvailableStock() ? 'text-emerald-700' : 'text-glowy-muted' }}">{{ $product->hasAvailableStock() ? 'متوفر' : 'نفد من المخزون' }}</p>
        @endif
    </div>
</article>
