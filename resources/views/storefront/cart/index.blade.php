<x-layouts.storefront title="سلة التسوق | Glowy">
    <section class="mx-auto max-w-6xl px-5 py-12 sm:px-8">
        <h1 class="text-3xl font-bold text-glowy-plum">سلة التسوق</h1>
        @if (session('status'))<p class="mt-4 text-sm font-bold text-emerald-700">{{ session('status') }}</p>@endif
        @error('cart')<p class="mt-4 text-sm font-bold text-red-700">{{ $message }}</p>@enderror
        @if ($cart->items->isEmpty())
            <x-ui.empty-state title="سلتك فارغة الآن" description="ابدئي باكتشاف منتجات العناية التي تناسب روتينك." />
        @else
            <p class="mt-5 text-glowy-muted">لديك {{ $cart->items->sum('quantity') }} منتجات في السلة.</p>
            <a href="{{ route('checkout.index') }}" class="mt-6 inline-flex min-h-11 items-center rounded-full bg-glowy-rose px-5 py-2.5 font-bold text-white">إتمام الطلب</a>
        @endif
    </section>
</x-layouts.storefront>
