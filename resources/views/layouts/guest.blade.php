<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'تسجيل الدخول | Glowy' }}</title><meta name="description" content="سجّلي دخولكِ إلى حساب Glowy لإدارة طلباتكِ ومتابعة رحلتكِ مع العناية.">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-glowy-ivory font-sans text-glowy-ink antialiased">
    <main class="relative isolate grid min-h-screen overflow-hidden lg:grid-cols-[1.05fr_.95fr]"><div class="absolute -start-24 top-16 h-80 w-80 rounded-full bg-glowy-blush blur-3xl" aria-hidden="true"></div><div class="absolute -end-24 bottom-0 h-96 w-96 rounded-full bg-glowy-champagne/70 blur-3xl" aria-hidden="true"></div>
        <section class="relative hidden overflow-hidden bg-glowy-plum p-10 text-white lg:flex lg:flex-col lg:justify-between" aria-label="عن Glowy"><span class="absolute -start-20 -top-16 h-72 w-72 rounded-full border border-white/10" aria-hidden="true"></span><span class="absolute -bottom-16 end-12 h-64 w-64 rounded-full border border-white/10" aria-hidden="true"></span><a href="{{ route('home') }}" class="relative z-10 w-fit rounded-2xl bg-white p-3"><img src="{{ asset('images/glowy-logo.png') }}" alt="Glowy" class="w-36"></a><div class="relative z-10 max-w-md"><p class="text-sm font-bold text-glowy-champagne">لحظة خاصة لكِ</p><h1 class="mt-4 font-display text-5xl font-bold leading-tight">العناية الجميلة تبدأ بخطوة صغيرة.</h1><p class="mt-6 text-lg leading-9 text-white/75">اختيارات أصلية، روتين مفهوم، وتفاصيل ناعمة تشبهكِ.</p><div class="mt-10 grid gap-3 text-sm font-bold text-white/90"><p>✦ منتجات مختارة بعناية</p><p>✦ دفع عند الاستلام</p><p>✦ توصيل محسوب بدقة</p></div></div><p class="relative z-10 text-sm text-white/55">Glowy — جمالك الطبيعي، بطريقتكِ.</p></section>
        <section class="relative flex items-center justify-center px-5 py-10 sm:px-8"><a href="{{ route('home') }}" class="absolute start-5 top-6 lg:hidden"><img src="{{ asset('images/glowy-logo.png') }}" alt="Glowy" class="w-28"></a><div class="w-full max-w-md rounded-[2rem] border border-white/85 bg-white/90 p-6 shadow-[0_28px_80px_rgba(58,47,51,.12)] backdrop-blur sm:p-9">{{ $slot }}</div></section>
    </main>
</body>
</html>
