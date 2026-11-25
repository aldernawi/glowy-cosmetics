<x-layouts.storefront title="طلباتي | Glowy">
    <section class="mx-auto max-w-5xl px-5 py-12 sm:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="section-eyebrow">حسابي</p><h1 class="section-title">طلباتي</h1><p class="mt-3 text-glowy-muted">تابعي تفاصيل كل طلب وحالته من مكان واحد.</p></div><a href="{{ route('products.index') }}" class="glowy-button glowy-button--secondary">مواصلة التسوّق</a></div>
        <div class="mt-8 grid gap-4">
            @forelse($orders as $order)
                <a class="group rounded-3xl border border-glowy-champagne bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-[0_16px_40px_rgba(58,47,51,.08)] sm:flex sm:items-center sm:justify-between" href="{{ route('customer.orders.show',$order) }}">
                    <div><div class="flex flex-wrap items-center gap-3"><strong class="font-display text-lg text-glowy-plum" dir="ltr">{{ $order->number }}</strong><span class="rounded-full bg-glowy-blush px-3 py-1 text-xs font-bold text-glowy-rose-deep">{{ $order->status->label() }}</span></div><p class="mt-2 text-sm text-glowy-muted">{{ $order->created_at?->translatedFormat('j F Y') }} · الدفع عند الاستلام</p></div><div class="mt-4 flex items-center justify-between gap-5 sm:mt-0"><strong class="text-glowy-plum">{{ $order->total }} د.ل</strong><span class="font-bold text-glowy-rose-deep transition group-hover:-translate-x-1">التفاصيل ←</span></div>
                </a>
            @empty
                <x-ui.empty-state title="لا توجد طلبات بعد" description="ابدئي باكتشاف اختيارات Glowy المناسبة لكِ." />
            @endforelse
        </div>
        <div class="mt-7">{{ $orders->links() }}</div>
    </section>
</x-layouts.storefront>
