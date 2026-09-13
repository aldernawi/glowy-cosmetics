<x-layouts.admin title="لوحة التحكم | Glowy">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-bold text-glowy-rose">نظرة عامة</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-glowy-plum">لوحة التحكم</h1>
            <p class="mt-2 text-sm text-glowy-muted">ستظهر ملخصات المبيعات والطلبات بعد إضافة بيانات المتجر.</p>
        </div>
        <span class="rounded-full bg-glowy-blush px-4 py-2 text-sm font-semibold text-glowy-rose">قريبًا: التقارير الحية</span>
    </div>

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="ملخص الأعمال">
        @foreach ([['مبيعات اليوم', '—'], ['طلبات اليوم', '—'], ['قيد التجهيز', '—'], ['منخفض المخزون', '—']] as [$label, $value])
            <article class="rounded-3xl border border-glowy-champagne/70 bg-white p-5 shadow-sm">
                <p class="text-sm text-glowy-muted">{{ $label }}</p>
                <p class="mt-3 text-3xl font-bold text-glowy-plum">{{ $value }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-8 rounded-3xl border border-dashed border-glowy-champagne bg-white p-8 text-center">
        <p class="text-xl font-bold text-glowy-plum">نبدأ بكتالوجك الجميل</p>
        <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-glowy-muted">بعد المرحلة التالية ستتمكنين من إضافة المنتجات، الماركات، التصنيفات والمخزون من هذه اللوحة.</p>
    </section>
</x-layouts.admin>
