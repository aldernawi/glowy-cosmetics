<nav class="mt-6 flex flex-wrap gap-2 text-sm font-bold" aria-label="أقسام نقطة البيع">
    @foreach (['admin.pos.sales.index' => ['المبيعات', 'admin.pos.sales.*'], 'admin.pos.shortages.index' => ['نقص المخزون', 'admin.pos.shortages.*'], 'admin.pos.device.index' => ['جهاز الكاشير', 'admin.pos.device.*']] as $route => [$label, $match])
        <a href="{{ route($route) }}" class="rounded-full px-4 py-2 {{ request()->routeIs($match) ? 'bg-glowy-plum text-white' : 'border border-glowy-champagne bg-white text-glowy-plum' }}">{{ $label }}</a>
    @endforeach
    <a href="{{ route('pos.app') }}" class="rounded-full border border-glowy-rose px-4 py-2 text-glowy-rose">فتح شاشة الكاشير ↗</a>
</nav>
