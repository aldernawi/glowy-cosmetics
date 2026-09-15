<x-layouts.storefront title="الصفحة غير موجودة | Glowy" robots="noindex,follow">
    <section class="mx-auto flex min-h-[60vh] max-w-3xl flex-col items-center justify-center px-5 py-20 text-center">
        <span class="text-7xl text-glowy-rose" aria-hidden="true">✦</span><p class="mt-5 font-display text-sm font-bold text-glowy-rose">404</p>
        <h1 class="mt-2 font-display text-4xl font-bold text-glowy-plum">هذه الصفحة فقدت طريقها</h1><p class="mt-4 max-w-lg leading-8 text-glowy-muted">لا تقلقي، يمكنك العودة للرئيسية أو متابعة اكتشاف منتجات العناية المختارة.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3"><a class="glowy-button glowy-button--primary" href="{{ route('home') }}">العودة للرئيسية</a><a class="glowy-button glowy-button--secondary" href="{{ route('products.index') }}">تصفّح المنتجات</a></div>
    </section>
</x-layouts.storefront>
