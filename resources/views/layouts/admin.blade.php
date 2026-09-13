<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'إدارة Glowy' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-glowy-ivory font-sans text-glowy-ink antialiased">
        <a href="#main-content" class="sr-only z-50 rounded-full bg-glowy-plum px-4 py-3 text-white focus:not-sr-only focus:fixed focus:right-4 focus:top-4">انتقلي إلى المحتوى</a>
        <div class="min-h-screen lg:grid lg:grid-cols-[17rem_1fr]">
            <aside class="bg-glowy-plum px-5 py-6 text-white lg:min-h-screen">
                <a href="{{ route('admin.dashboard') }}" class="text-2xl font-bold">Glowy<span class="text-glowy-champagne">✦</span></a>
                <p class="mt-1 text-sm text-white/65">لوحة الإدارة</p>
                <nav class="mt-8 grid gap-1 text-sm" aria-label="التنقل الإداري">
                    <a href="{{ route('admin.dashboard') }}" class="rounded-xl bg-white/10 px-4 py-3 font-bold">لوحة التحكم</a>
                    <span class="rounded-xl px-4 py-3 text-white/55">الطلبات — قريبًا</span>
                    <a href="{{ route('admin.brands.index') }}" class="rounded-xl px-4 py-3 transition hover:bg-white/10">الماركات</a>
                    <a href="{{ route('admin.products.index') }}" class="rounded-xl px-4 py-3 transition hover:bg-white/10">المنتجات</a>
                    <span class="rounded-xl px-4 py-3 text-white/55">المخزون — قريبًا</span>
                    <span class="rounded-xl px-4 py-3 text-white/55">التوصيل — قريبًا</span>
                    <span class="rounded-xl px-4 py-3 text-white/55">الإعدادات — قريبًا</span>
                </nav>
            </aside>
            <div class="min-w-0">
                <header class="flex items-center justify-between border-b border-glowy-champagne/70 bg-white px-5 py-4 sm:px-8">
                    <p class="text-sm text-glowy-muted">مرحبًا، {{ auth()->user()->name }}</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="min-h-11 rounded-full px-4 text-sm font-bold text-glowy-rose transition hover:bg-glowy-blush">تسجيل الخروج</button>
                    </form>
                </header>
                <main id="main-content" class="p-5 sm:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
