<x-layouts.storefront title="منتجات العناية | Glowy">
    <section class="mx-auto max-w-7xl px-5 py-12 sm:px-8">
        <p class="text-sm font-bold text-glowy-rose">اكتشفي اختيارات Glowy</p>
        <h1 class="mt-2 text-3xl font-bold text-glowy-plum sm:text-4xl">منتجات العناية</h1>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($products as $product)
                @php($variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first())
                <article class="overflow-hidden rounded-3xl border border-glowy-champagne bg-white p-4">
                    <a href="{{ route('products.show', $product) }}" class="block aspect-square rounded-2xl bg-glowy-blush p-5">
                        @if ($product->images->first())
                            <img src="{{ $product->images->first()->path }}" alt="{{ $product->name_ar }}" class="h-full w-full object-cover" loading="lazy">
                        @else
                            <span class="flex h-full items-center justify-center text-5xl text-glowy-rose" aria-hidden="true">✦</span>
                        @endif
                    </a>
                    <div class="pt-4">
                        <p class="text-xs text-glowy-muted">{{ $product->brand?->name_ar }}</p>
                        <h2 class="mt-1 font-bold"><a href="{{ route('products.show', $product) }}">{{ $product->name_ar }}</a></h2>
                        @if ($variant)<p class="mt-3 font-bold text-glowy-rose">{{ $variant->price }} د.ل</p>@endif
                    </div>
                </article>
            @empty
                <div class="sm:col-span-2 lg:col-span-3 xl:col-span-4"><x-ui.empty-state title="لا توجد منتجات متاحة الآن" description="سنضيف اختيارات جديدة قريبًا." /></div>
            @endforelse
        </div>
    </section>
</x-layouts.storefront>
