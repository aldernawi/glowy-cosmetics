<x-layouts.storefront :title="$title.' | Glowy'">
    <section class="mx-auto max-w-7xl px-5 py-12 sm:px-8">
        <p class="text-sm font-bold text-glowy-rose">{{ $eyebrow }}</p>
        <h1 class="mt-2 font-display text-3xl font-bold text-glowy-plum sm:text-4xl">{{ $title }}</h1>
        @if ($description)<p class="mt-4 max-w-2xl leading-8 text-glowy-muted">{{ $description }}</p>@endif
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($products as $product)
                <x-storefront.product-card :product="$product" />
            @empty
                <div class="sm:col-span-2 lg:col-span-3 xl:col-span-4"><x-ui.empty-state title="لا توجد منتجات هنا الآن" description="ستصل اختيارات جديدة قريبًا." /></div>
            @endforelse
        </div>
        <div class="mt-10">{{ $products->links() }}</div>
    </section>
</x-layouts.storefront>
