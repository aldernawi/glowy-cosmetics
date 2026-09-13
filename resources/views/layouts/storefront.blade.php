<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Glowy | عناية تستحقها بشرتك' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-glowy-ivory font-sans text-glowy-ink antialiased">
        <a href="#main-content" class="sr-only z-50 rounded-full bg-glowy-plum px-4 py-3 text-white focus:not-sr-only focus:fixed focus:right-4 focus:top-4">انتقلي إلى المحتوى</a>

        <div class="bg-glowy-plum px-4 py-2 text-center text-xs font-medium text-white sm:text-sm">
            شحن داخل طرابلس والمناطق القريبة ✨
        </div>

        <header class="border-b border-glowy-champagne/70 bg-glowy-ivory/95">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="block" aria-label="Glowy الرئيسية"><img src="{{ Vite::asset('resources/images/glowy-logo.png') }}" alt="Glowy" class="h-auto w-36 sm:w-44"></a>
                <nav class="hidden items-center gap-6 text-sm font-medium lg:flex" aria-label="التنقل الرئيسي">
                    <a class="transition hover:text-glowy-rose" href="{{ route('products.index') }}">العناية بالبشرة</a>
                    <a class="transition hover:text-glowy-rose" href="#">واقي الشمس</a>
                    <a class="transition hover:text-glowy-rose" href="#">العناية بالشعر</a>
                    <a class="transition hover:text-glowy-rose" href="#">الماركات</a>
                    <a class="transition hover:text-glowy-rose" href="#">العروض</a>
                </nav>
                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="rounded-full px-3 py-2 text-sm font-medium transition hover:bg-glowy-blush">دخول</a>
                    <a href="{{ route('cart.index') }}" class="rounded-full bg-glowy-rose px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-glowy-plum">السلة</a>
                </div>
            </div>
        </header>

        <main id="main-content">
            {{ $slot }}
        </main>

        <footer class="mt-16 border-t border-glowy-champagne bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-10 text-sm text-glowy-muted sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p>© {{ now()->year }} Glowy. عناية مختارة بحب.</p>
                <p>جمالك الطبيعي، بطريقتك.</p>
            </div>
        </footer>
    </body>
</html>
