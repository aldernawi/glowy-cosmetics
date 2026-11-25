<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'إدارة Glowy' }}</title>@stack('head')@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f4f3] font-sans text-glowy-ink antialiased" x-data="{ sidebarOpen: false }">
    @php
        $adminUser = auth()->user();
        $can = fn (array $permissions) => $adminUser->canAny($permissions);
        $navigation = [
            ['label' => 'الرئيسية', 'items' => [['label' => 'نظرة عامة', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'permissions' => ['admin.access', 'admin.dashboard.view'], 'icon' => '⌂']]],
            ['label' => 'التشغيل', 'items' => [['label' => 'الطلبات', 'route' => 'admin.orders.index', 'match' => 'admin.orders.*', 'permissions' => ['admin.access', 'orders.view'], 'icon' => '◫'], ['label' => 'نقطة البيع', 'route' => 'admin.pos.sales.index', 'match' => 'admin.pos.*', 'permissions' => ['admin.access', 'pos.manage'], 'icon' => '▤'], ['label' => 'العملاء', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*', 'permissions' => ['admin.access', 'customers.view'], 'icon' => '♙'], ['label' => 'التوصيل', 'route' => 'admin.delivery.index', 'match' => 'admin.delivery.*', 'permissions' => ['admin.access', 'delivery.manage'], 'icon' => '⌖']]],
            ['label' => 'الكتالوج', 'items' => [['label' => 'المنتجات', 'route' => 'admin.products.index', 'match' => 'admin.products.*|admin.variants.*|admin.images.*', 'permissions' => ['admin.access', 'catalog.manage', 'products.view'], 'icon' => '◇'], ['label' => 'إدخال الباركود', 'route' => 'admin.barcodes.index', 'match' => 'admin.barcodes.*', 'permissions' => ['admin.access', 'catalog.manage', 'products.update'], 'icon' => '▥'], ['label' => 'الماركات', 'route' => 'admin.brands.index', 'match' => 'admin.brands.*', 'permissions' => ['admin.access', 'catalog.manage', 'brands.view'], 'icon' => '✦'], ['label' => 'التصنيفات', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*|admin.concerns.*|admin.skin-types.*|admin.ingredients.*', 'permissions' => ['admin.access', 'catalog.manage', 'categories.view'], 'icon' => '☷']]],
            ['label' => 'المخزون', 'items' => [['label' => 'المخزون الحالي', 'route' => 'admin.stock.index', 'match' => 'admin.stock.index|admin.stock.low.*|admin.stock.out.*|admin.stock.movements.*', 'permissions' => ['admin.access', 'inventory.view'], 'icon' => '▦'], ['label' => 'تعديل المخزون', 'route' => 'admin.stock.adjustments.index', 'match' => 'admin.stock.adjustments.*', 'permissions' => ['admin.access', 'inventory.adjust'], 'icon' => '±']]],
            ['label' => 'الإدارة', 'items' => [['label' => 'المحتوى والإعدادات', 'route' => 'admin.content.index', 'match' => 'admin.content.*', 'permissions' => ['admin.access', 'content.manage'], 'icon' => '◌'], ['label' => 'التقارير', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'permissions' => ['admin.access', 'reports.view'], 'icon' => '⌁'], ['label' => 'المستخدمون', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'permissions' => ['admin.access', 'users.manage'], 'icon' => '♧']]],
        ];
    @endphp
    <a href="#main-content" class="sr-only z-50 rounded-full bg-glowy-plum px-4 py-3 text-white focus:not-sr-only focus:fixed focus:right-4 focus:top-4">انتقلي إلى المحتوى</a>
    <div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-40 bg-glowy-plum/45 lg:hidden" @click="sidebarOpen = false"></div>
    <div class="min-h-screen lg:grid lg:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="fixed inset-y-0 right-0 z-50 flex w-[18rem] flex-col bg-glowy-plum p-4 text-white shadow-2xl transition-transform duration-300 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shadow-none" :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full'">
            <div class="flex items-center justify-between gap-3"><a href="{{ route(app(App\Services\AdminLandingService::class)->routeFor($adminUser) ?? 'home') }}" class="rounded-2xl bg-white p-3"><img src="{{ asset('images/glowy-logo.png') }}" alt="Glowy" class="w-28"></a><button type="button" class="rounded-xl p-2 text-xl lg:hidden" @click="sidebarOpen = false" aria-label="إغلاق القائمة">×</button></div>
            <div class="mt-5 rounded-2xl bg-white/10 p-3"><p class="text-xs text-white/60">لوحة التشغيل</p><p class="mt-1 text-sm font-bold">{{ $adminUser->name }}</p></div>
            <nav class="mt-5 min-h-0 flex-1 space-y-5 overflow-y-auto pb-4" aria-label="التنقل الإداري">
                @foreach ($navigation as $group)
                    @php($visibleItems = collect($group['items'])->filter(fn ($item) => $can($item['permissions'])))
                    @if ($visibleItems->isNotEmpty())
                        <section><p class="px-3 text-[0.7rem] font-bold tracking-wider text-white/45">{{ $group['label'] }}</p><div class="mt-2 grid gap-1">@foreach ($visibleItems as $item)@php($active = collect(explode('|', $item['match']))->contains(fn ($match) => request()->routeIs($match)))<a href="{{ route($item['route']) }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition {{ $active ? 'bg-white text-glowy-plum shadow-sm' : 'text-white/75 hover:bg-white/10 hover:text-white' }}"><span class="w-5 text-center text-base">{{ $item['icon'] }}</span>{{ $item['label'] }}</a>@endforeach</div></section>
                    @endif
                @endforeach
            </nav>
            <a href="{{ route('home') }}" class="rounded-xl px-3 py-2 text-xs font-bold text-white/65 transition hover:bg-white/10 hover:text-white">↗ عرض المتجر</a>
        </aside>
        <div class="min-w-0">
            <header class="sticky top-0 z-30 flex min-h-[4.5rem] items-center justify-between gap-3 border-b border-glowy-champagne/70 bg-[#fdfcfc]/95 px-4 py-3 backdrop-blur sm:px-7">
                <div class="flex min-w-0 items-center gap-3"><button type="button" class="rounded-xl border border-glowy-champagne bg-white p-2 text-xl lg:hidden" @click="sidebarOpen = true" aria-label="فتح القائمة">☰</button><div class="min-w-0"><p class="truncate text-sm font-bold text-glowy-plum">{{ $title ?? 'إدارة Glowy' }}</p><p class="mt-0.5 text-xs text-glowy-muted">إدارة سريعة وواضحة لمتجر Glowy</p></div></div>
                <div class="flex items-center gap-2">@if ($can(['admin.access', 'catalog.manage', 'products.create']))<a href="{{ route('admin.products.create') }}" class="hidden rounded-full bg-glowy-rose px-4 py-2 text-sm font-bold text-white transition hover:bg-glowy-plum sm:block">+ منتج جديد</a>@endif<form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-full border border-glowy-champagne bg-white px-3 py-2 text-xs font-bold text-glowy-rose transition hover:bg-glowy-blush sm:px-4 sm:text-sm">خروج</button></form></div>
            </header>
            <main id="main-content" class="mx-auto w-full max-w-[100rem] p-4 sm:p-7 lg:p-9">{{ $slot }}</main>
        </div>
    </div>
</body>
</html>
