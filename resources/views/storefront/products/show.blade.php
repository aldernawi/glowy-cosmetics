<x-layouts.storefront :title="$product->name_ar . ' | Glowy'">
    @php($variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first())
    <section class="mx-auto grid max-w-6xl gap-10 px-5 py-12 md:grid-cols-2 sm:px-8">
        <div class="aspect-square rounded-3xl bg-glowy-blush p-8">
            @if ($product->images->first())
                <img src="{{ $product->images->first()->path }}" alt="{{ $product->name_ar }}" class="h-full w-full object-cover" loading="eager">
            @else
                <span class="flex h-full items-center justify-center text-7xl text-glowy-rose" aria-hidden="true">✦</span>
            @endif
        </div>
        <div class="self-center">
            <p class="text-sm font-bold text-glowy-rose">{{ $product->brand?->name_ar }}</p>
            <h1 class="mt-2 text-3xl font-bold text-glowy-plum sm:text-4xl">{{ $product->name_ar }}</h1>
            @if ($product->description)<p class="mt-5 leading-8 text-glowy-muted">{{ $product->description }}</p>@endif
            @if ($variant)
                <p class="mt-6 text-2xl font-bold text-glowy-rose">{{ $variant->price }} د.ل</p>
                @if ($variant->stock_quantity > 0)
                    <form action="{{ route('cart.items.store') }}" method="POST" class="mt-6 flex gap-3">
                        @csrf
                        <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                        <input type="number" name="quantity" value="1" min="1" max="{{ $variant->stock_quantity }}" class="min-h-11 w-20 rounded-full border-glowy-champagne text-center" aria-label="الكمية">
                        <x-ui.button type="submit">أضيفي إلى السلة</x-ui.button>
                    </form>
                @else
                    <p class="mt-6 font-bold text-glowy-muted">نفد من المخزون حاليًا</p>
                @endif
            @endif
        </div>
    </section>
</x-layouts.storefront>
