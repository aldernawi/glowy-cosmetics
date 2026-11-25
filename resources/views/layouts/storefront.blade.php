<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $pageTitle = $title ?? $storeContent['meta_title'] ?? 'Glowy | عناية تستحقها بشرتك';
            $pageDescription = $description ?? $storeContent['meta_description'] ?? 'متجر Glowy لمنتجات العناية بالبشرة والجمال المختارة بعناية.';
            $pageCanonical = $canonical ?? url()->current();
        @endphp
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <meta name="robots" content="{{ $robots ?? 'index,follow' }}">
        <link rel="canonical" href="{{ $pageCanonical }}">
        <meta property="og:locale" content="ar_LY">
        <meta property="og:site_name" content="Glowy">
        <meta property="og:type" content="{{ $ogType ?? 'website' }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ $pageCanonical }}">
        @if(!empty($ogImage))<meta property="og:image" content="{{ $ogImage }}">@endif
        <meta name="twitter:card" content="summary_large_image">
        @if(!empty($structuredData))<script type="application/ld+json">{!! json_encode(array_filter($structuredData, fn ($value) => $value !== null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>@endif
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
        @stack('head')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="storefront-shell min-h-screen overflow-x-hidden bg-glowy-ivory font-sans text-glowy-ink antialiased" x-data="{ menuOpen: false, cartOpen: false }">
        <a href="#main-content" class="sr-only z-50 rounded-full bg-glowy-plum px-4 py-3 text-white focus:not-sr-only focus:fixed focus:start-4 focus:top-4">انتقلي إلى المحتوى</a>

        @if(!empty($storeContent['announcement']))<div class="bg-glowy-plum px-4 py-2 text-center text-xs font-medium text-white sm:text-sm">{{ $storeContent['announcement'] }}</div>@endif

        <header class="sticky top-0 z-40 border-b border-glowy-champagne/70 bg-glowy-ivory/95 backdrop-blur" data-site-header>
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                <button type="button" class="rounded-full p-2 lg:hidden" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-label="فتح القائمة"><span class="text-2xl">☰</span></button>
                <a href="{{ route('home') }}" class="block" aria-label="Glowy الرئيسية"><img src="{{ asset('images/glowy-logo.png') }}" alt="Glowy" class="h-auto w-32 sm:w-40" data-site-logo></a>
                <nav class="hidden items-center gap-5 text-sm font-bold lg:flex" aria-label="التنقل الرئيسي">
                    <a class="transition hover:text-glowy-rose" href="{{ route('home') }}">الرئيسية</a>
                    @foreach($headerCategories as $category)<a class="transition hover:text-glowy-rose" href="{{ route('products.index', ['category' => $category->slug]) }}">{{ $category->name_ar }}</a>@endforeach
                    <details class="group relative"><summary class="cursor-pointer list-none transition hover:text-glowy-rose">احتياجات البشرة</summary><div class="absolute start-0 top-7 z-20 grid w-52 gap-2 rounded-2xl border border-glowy-champagne bg-white p-3 shadow-lg">@foreach($headerConcerns as $concern)<a class="text-sm font-medium hover:text-glowy-rose" href="{{route('concerns.show',$concern)}}">{{$concern->name_ar}}</a>@endforeach</div></details>
                    <a class="transition hover:text-glowy-rose" href="{{ route('brands.index') }}">الماركات</a>
                    <a class="transition hover:text-glowy-rose" href="{{ route('products.index', ['new' => 1]) }}">وصل حديثًا</a>
                    <a class="transition hover:text-glowy-rose" href="{{ route('products.index', ['sale' => 1]) }}">العروض</a>
                </nav>
                <div class="flex items-center gap-1 sm:gap-2">
                    <a href="{{ route('products.index') }}" class="rounded-full p-2 hover:bg-glowy-blush" aria-label="البحث">⌕</a>
                    @auth<a href="{{ route('dashboard') }}" class="hidden rounded-full px-3 py-2 text-sm font-bold hover:bg-glowy-blush sm:block">حسابي</a>@else<a href="{{ route('login') }}" class="hidden rounded-full px-3 py-2 text-sm font-bold hover:bg-glowy-blush sm:block">دخول</a>@endauth
                    <button type="button" @click="cartOpen = true" class="relative rounded-full bg-glowy-rose px-4 py-2 text-sm font-bold text-white transition hover:bg-glowy-plum" aria-label="فتح سلة التسوق">السلة <span data-cart-count class="ms-1 rounded-full bg-white/20 px-1.5">{{ $headerCartCount }}</span></button>
                </div>
            </div>
            <div x-cloak x-show="menuOpen" x-transition class="border-t border-glowy-champagne bg-white px-5 py-5 lg:hidden"><nav class="grid gap-4 font-bold"><a href="{{ route('home') }}">الرئيسية</a>@foreach($headerCategories as $category)<a href="{{ route('products.index',['category'=>$category->slug]) }}">{{$category->name_ar}}</a>@endforeach<a href="{{route('brands.index')}}">الماركات</a><a href="{{ route('products.index', ['new' => 1]) }}">وصل حديثًا</a><a href="{{ route('products.index', ['sale' => 1]) }}">العروض</a>@auth<a href="{{ route('dashboard') }}">حسابي</a>@else<a href="{{ route('login') }}">دخول</a>@endauth</nav></div>
            <form method="GET" action="{{ route('products.index') }}" class="mx-auto hidden max-w-7xl px-4 pb-4 md:block sm:px-6 lg:px-8"><label class="sr-only" for="header-search">ابحثي في المتجر</label><input id="header-search" name="q" value="{{ request('q') }}" placeholder="ابحثي عن منتج، ماركة أو احتياج لبشرتك..." class="w-full rounded-full border-glowy-champagne bg-white px-5 text-sm"></form>
        </header>

        <div x-cloak x-show="cartOpen" class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="سلة التسوق">
            <button class="absolute inset-0 bg-glowy-plum/45" @click="cartOpen = false" aria-label="إغلاق السلة"></button>
            <aside x-show="cartOpen" x-transition:enter="transition duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" class="absolute inset-y-0 end-0 flex w-full max-w-md flex-col bg-glowy-ivory p-6 shadow-2xl">
                <div class="flex items-center justify-between"><h2 class="font-display text-2xl font-bold">سلتك</h2><button type="button" @click="cartOpen = false" class="rounded-full p-2 text-xl" aria-label="إغلاق">×</button></div>
                <div class="mt-6 flex-1 space-y-4 overflow-y-auto">
                    @forelse($headerCart?->items ?? [] as $item)
                        <div class="flex gap-3 rounded-2xl bg-white p-3"><div class="h-16 w-16 shrink-0 rounded-xl bg-glowy-blush p-1">@if($item->productVariant->product->images->first())<img src="{{ $item->productVariant->product->images->first()->url }}" alt="" class="h-full w-full object-contain" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}'">@else<span class="flex h-full items-center justify-center text-glowy-rose">✦</span>@endif</div><div class="min-w-0"><p class="truncate font-bold">{{ $item->productVariant->product->name_ar }}</p><p class="text-sm text-glowy-muted">الكمية: {{ $item->quantity }}</p><p class="text-sm font-bold text-glowy-rose">{{ $item->productVariant->price }} د.ل</p></div></div>
                    @empty<div class="rounded-3xl bg-glowy-blush p-8 text-center"><span class="text-4xl text-glowy-rose">✦</span><p class="mt-4 font-bold">سلتك تنتظر اختياراتك</p></div>@endforelse
                </div>
                <div class="border-t border-glowy-champagne pt-5"><div class="flex justify-between"><span>المجموع الفرعي</span><strong>{{ $headerCartSubtotal }} د.ل</strong></div><a href="{{ route('cart.index') }}" class="mt-4 flex min-h-12 items-center justify-center rounded-full bg-glowy-rose font-bold text-white">عرض السلة</a></div>
            </aside>
        </div>
        <div id="cart-feedback" class="pointer-events-none fixed inset-x-4 bottom-5 z-[60] mx-auto hidden max-w-sm rounded-2xl px-5 py-4 text-center text-sm font-bold shadow-lg sm:inset-x-auto sm:start-5" role="status" aria-live="polite"></div>

        <main id="main-content">{{ $slot }}</main>

        <footer class="mt-16 border-t border-glowy-champagne bg-white">
            <div class="mx-auto grid max-w-7xl gap-10 px-5 py-12 sm:grid-cols-2 sm:px-8 lg:grid-cols-4"><div><img src="{{ asset('images/glowy-logo.png') }}" alt="Glowy" class="w-36"><p class="mt-4 text-sm leading-7 text-glowy-muted">عناية مختارة بحب لبشرة أكثر صحة ووهجًا.</p></div><div><h2 class="font-display font-bold">تسوّقي</h2><div class="mt-4 grid gap-3 text-sm text-glowy-muted"><a href="{{ route('products.index') }}">كل المنتجات</a><a href="{{ route('products.index', ['new' => 1]) }}">وصل حديثًا</a><a href="{{ route('products.index', ['sale' => 1]) }}">العروض</a></div></div><div><h2 class="font-display font-bold">خدمة العميلات</h2><div class="mt-4 grid gap-3 text-sm text-glowy-muted"><a href="{{ route('cart.index') }}">سلة التسوق</a><a href="{{ route('checkout.index') }}">إتمام الطلب</a>@auth<a href="{{ route('customer.orders.index') }}">طلباتي</a>@endauth</div></div><div><h2 class="font-display font-bold">تواصلي معنا</h2><div class="mt-4 grid gap-2 text-sm text-glowy-muted">@if($storeContent['phone'])<a dir="ltr" class="w-fit" href="tel:{{ $storeContent['phone'] }}">{{ $storeContent['phone'] }}</a>@endif @if($storeContent['whatsapp'])<a href="https://wa.me/{{ $storeContent['whatsapp'] }}">واتساب</a>@endif @if($storeContent['email'])<a href="mailto:{{ $storeContent['email'] }}">{{ $storeContent['email'] }}</a>@endif @if($storeContent['address'])<p>{{ $storeContent['address'] }}</p>@endif @if($storeContent['working_hours'])<p>{{ $storeContent['working_hours'] }}</p>@endif</div></div></div>
            <div class="border-t border-glowy-champagne"><div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-5 text-xs text-glowy-muted sm:flex-row sm:justify-between sm:px-8"><p>© {{ now()->year }} Glowy. جميع الحقوق محفوظة.</p><p>جمالك الطبيعي، بطريقتك.</p></div></div>
        </footer>
    </body>
</html>
