<x-layouts.storefront title="سلة التسوق | Glowy">
    <section class="mx-auto max-w-6xl px-5 py-12 sm:px-8">
        <h1 class="text-3xl font-bold text-glowy-plum">سلة التسوق</h1>
        @if ($cart->items->isEmpty())
            <x-ui.empty-state title="سلتك فارغة الآن" description="ابدئي باكتشاف منتجات العناية التي تناسب روتينك." />
        @else
            <p class="mt-5 text-glowy-muted">لديك {{ $cart->items->sum('quantity') }} منتجات في السلة.</p>
        @endif
    </section>
</x-layouts.storefront>
