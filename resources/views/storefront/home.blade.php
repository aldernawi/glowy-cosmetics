<x-layouts.storefront :title="$content['meta_title']" :description="$content['meta_description']" :canonical="route('home')">
    <section class="hero-editorial relative isolate overflow-hidden bg-glowy-blush" data-parallax-scene>
        <div class="hero-editorial__halo absolute -end-24 top-10 h-72 w-72 rounded-full bg-white/60 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -start-24 bottom-0 h-80 w-80 rounded-full bg-glowy-champagne/65 blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-7xl gap-12 px-5 pb-16 pt-10 sm:px-8 sm:pb-24 sm:pt-16 lg:grid-cols-[.9fr_1.1fr] lg:items-center lg:gap-16">
            <div class="relative z-10 max-w-2xl" data-reveal>
                <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/75 px-4 py-2 text-sm font-bold text-glowy-rose-deep shadow-sm backdrop-blur-sm">
                    <span class="sparkle-dot" aria-hidden="true">✦</span>
                    اختيارات موثوقة لبشرتك
                </p>
                <h1 class="font-display text-4xl font-bold leading-[1.25] text-glowy-plum sm:text-6xl lg:text-7xl">
                    <span class="relative block w-fit text-glowy-rose">
                        {{ $content['hero_title'] }}
                        <svg class="absolute -bottom-3 start-0 w-full text-glowy-rose/45" viewBox="0 0 310 18" fill="none" aria-hidden="true"><path d="M5 12C73 3 183 3 305 9" stroke="currentColor" stroke-width="6" stroke-linecap="round"/></svg>
                    </span>
                </h1>
                <p class="mt-8 max-w-xl text-lg leading-9 text-glowy-muted">{{ $content['hero_subtitle'] }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('products.index') }}" class="glowy-button glowy-button--primary">{{ $content['hero_cta_label'] }} <span aria-hidden="true">←</span></a>
                    <a href="#routine" class="glowy-button glowy-button--secondary">اكتشفي روتينك</a>
                </div>
                <div class="mt-9 flex flex-wrap items-center gap-x-7 gap-y-3 text-sm font-bold text-glowy-plum/75">
                    <span class="inline-flex items-center gap-2"><span class="check-bubble">✓</span> أصلية ومختارة</span>
                    <span class="inline-flex items-center gap-2"><span class="check-bubble">✓</span> دفع عند الاستلام</span>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-2xl" data-reveal style="--reveal-delay: 120ms">
                <div class="absolute -end-2 top-10 z-20 rounded-2xl bg-white/90 px-4 py-3 shadow-[0_18px_50px_rgba(58,47,51,.12)] backdrop-blur md:-end-8" data-parallax data-parallax-depth="1.1">
                    <p class="text-xs font-bold text-glowy-muted">روتينك، بطريقتك</p>
                    <p class="mt-1 font-display font-bold text-glowy-plum">وهجك يبدأ بالعناية ✦</p>
                </div>
                <div class="absolute -start-3 bottom-12 z-20 flex items-center gap-3 rounded-2xl bg-glowy-plum px-4 py-3 text-white shadow-[0_18px_50px_rgba(58,47,51,.18)] md:-start-8" data-parallax data-parallax-depth=".75">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-glowy-champagne">✦</span>
                    <span class="text-sm font-bold">لمسة ناعمة، نتيجة أجمل</span>
                </div>
                <div class="hero-image-shell relative overflow-hidden rounded-[2.5rem_2.5rem_6rem_2.5rem] border-[6px] border-white bg-glowy-champagne shadow-[0_28px_90px_rgba(58,47,51,.16)] sm:rounded-[4rem_4rem_9rem_4rem]">
                    <img src="{{ str_starts_with($content['hero_image_path'], 'homepage/') ? Storage::disk('public')->url($content['hero_image_path']) : asset($content['hero_image_path']) }}"
                         alt="مجموعة عناية بالبشرة بألوان Glowy الهادئة"
                         class="hero-image h-[30rem] w-full object-cover object-center sm:h-[38rem]"
                         width="1536" height="1024" fetchpriority="high" decoding="async">
                    <div class="absolute inset-0 bg-gradient-to-t from-glowy-plum/10 via-transparent to-white/10" aria-hidden="true"></div>
                </div>
                <span class="hero-sparkle absolute -start-7 top-10 z-30 text-4xl text-glowy-rose" aria-hidden="true">✦</span>
                <span class="hero-sparkle hero-sparkle--delay absolute -bottom-5 end-14 z-30 text-5xl text-glowy-rose" aria-hidden="true">✦</span>
            </div>
        </div>
    </section>

    <div class="marquee-strip border-y border-glowy-champagne bg-white py-3" data-marquee aria-label="مميزات متجر Glowy">
        <button type="button" class="marquee-strip__toggle absolute end-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-glowy-champagne bg-white/95 px-3 py-1.5 text-xs font-bold text-glowy-rose-deep shadow-sm backdrop-blur" data-marquee-toggle aria-pressed="false">إيقاف الحركة</button>
        <div class="marquee-strip__track" data-marquee-track>
            @foreach(range(1, 2) as $copy)
                <div class="marquee-strip__group" @if($copy === 2) aria-hidden="true" @endif>
                    <span>منتجات أصلية</span><b>✦</b><span>عناية حسب احتياجك</span><b>✦</b><span>توصيل محسوب بدقة</span><b>✦</b><span>دفع عند الاستلام</span><b>✦</b>
                </div>
            @endforeach
        </div>
    </div>

    <section class="relative mx-auto max-w-7xl overflow-hidden px-5 py-20 sm:px-8">
        <div class="mb-10 flex flex-wrap items-end justify-between gap-4" data-reveal>
            <div><p class="section-eyebrow">ابدئي من احتياجك</p><h2 class="section-title">تسوّقي حسب احتياج بشرتك</h2></div>
            <a href="{{ route('products.index') }}" class="section-link">شاهدي كل الاختيارات <span aria-hidden="true">←</span></a>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($concerns as $concern)
                <a href="{{ route('concerns.show', $concern) }}" class="editorial-tile group relative overflow-hidden rounded-[2rem] border border-glowy-champagne bg-white p-6" data-reveal style="--reveal-delay: {{ $loop->index * 70 }}ms">
                    <span class="absolute -end-8 -top-8 h-28 w-28 rounded-full bg-glowy-blush transition duration-500 group-hover:scale-125" aria-hidden="true"></span>
                    <span class="relative flex h-12 w-12 items-center justify-center rounded-2xl bg-glowy-blush text-2xl text-glowy-rose transition duration-500 group-hover:-rotate-6 group-hover:bg-glowy-rose group-hover:text-white">✦</span>
                    <h3 class="relative mt-6 font-display text-lg font-bold text-glowy-plum">{{ $concern->name_ar }}</h3>
                    <p class="relative mt-2 text-sm leading-6 text-glowy-muted">{{ $concern->description ?: 'اختيارات مدروسة تساعدك على العناية بهذا الاحتياج.' }}</p>
                    <span class="editorial-tile__cta relative mt-5 inline-flex text-sm font-bold text-glowy-rose-deep opacity-0 transition duration-300 group-hover:opacity-100 group-focus-visible:opacity-100">اكتشفي المنتجات ←</span>
                </a>
            @empty
                <p class="text-glowy-muted">ستظهر احتياجات البشرة هنا بعد إضافتها.</p>
            @endforelse
        </div>
    </section>

    <section class="relative overflow-hidden bg-white py-20">
        <span class="absolute -start-12 top-10 text-8xl text-glowy-blush" aria-hidden="true">✦</span>
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="mb-10 flex items-end justify-between gap-4" data-reveal>
                <div><p class="section-eyebrow">اختاري ما تحبين</p><h2 class="section-title">تسوّقي حسب التصنيف</h2></div>
                <a href="{{ route('products.index') }}" class="section-link">عرض الكل <span aria-hidden="true">←</span></a>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($categories as $category)
                    <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="category-card group relative overflow-hidden rounded-[2rem] bg-glowy-blush" data-reveal style="--reveal-delay: {{ $loop->index * 70 }}ms">
                        <div class="aspect-[4/3] overflow-hidden p-5">
                            @if($category->image_path)
                                <img src="{{ $category->image_path }}" alt="{{ $category->name_ar }}" class="h-full w-full object-contain transition duration-700 group-hover:scale-110" loading="lazy" decoding="async">
                            @else
                                <div class="flex h-full items-center justify-center text-6xl text-glowy-rose transition duration-700 group-hover:rotate-12 group-hover:scale-110">✦</div>
                            @endif
                        </div>
                        <div class="relative flex items-center justify-between bg-white/65 px-5 py-4 backdrop-blur-sm">
                            <h3 class="font-display text-lg font-bold text-glowy-plum">{{ $category->name_ar }}</h3>
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-glowy-rose transition group-hover:-translate-x-1 group-hover:bg-glowy-rose group-hover:text-white">←</span>
                        </div>
                    </a>
                @empty
                    <p class="text-glowy-muted">ستظهر التصنيفات المختارة هنا.</p>
                @endforelse
            </div>
        </div>
    </section>

    @foreach ([['title' => 'وصل حديثًا', 'caption' => 'اختيارات جديدة تستحق التجربة', 'items' => $newProducts], ['title' => 'الأكثر طلبًا', 'caption' => 'مفضلات عميلات Glowy', 'items' => $bestSellers]] as $section)
        <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8">
            <div class="mb-10 flex items-end justify-between gap-4" data-reveal>
                <div><p class="section-eyebrow">{{ $section['caption'] }}</p><h2 class="section-title">{{ $section['title'] }}</h2></div>
                <a href="{{ route('products.index') }}" class="section-link">كل المنتجات <span aria-hidden="true">←</span></a>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($section['items'] as $product)
                    <x-storefront.product-card :product="$product" />
                @empty
                    <p class="text-glowy-muted sm:col-span-2 lg:col-span-4">نحضّر لك اختيارات جميلة قريبًا.</p>
                @endforelse
            </div>
        </section>
    @endforeach

    <section class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <div class="promo-editorial relative isolate grid overflow-hidden rounded-[2.5rem] bg-glowy-plum text-white shadow-[0_30px_80px_rgba(58,47,51,.14)] lg:grid-cols-[1.1fr_.9fr]" data-reveal data-parallax-scene>
            <span class="absolute -end-16 -top-16 h-52 w-52 rounded-full border border-white/10" aria-hidden="true"></span>
            <span class="absolute end-16 top-10 text-4xl text-glowy-champagne/70" data-parallax data-parallax-depth=".8" aria-hidden="true">✦</span>
            <div class="relative z-10 p-8 sm:p-12 lg:p-16">
                <p class="text-sm font-bold text-glowy-champagne">اختيار الأسبوع</p>
                <h2 class="mt-3 max-w-lg font-display text-3xl font-bold leading-tight sm:text-4xl">{{ $content['promo_title'] }}</h2>
                <p class="mt-5 max-w-xl leading-8 text-white/75">{{ $content['promo_body'] }}</p>
                <a href="{{ route('products.index') }}" class="mt-8 inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 font-bold text-glowy-plum transition hover:-translate-y-1 hover:bg-glowy-blush">{{ $content['promo_cta_label'] }} <span aria-hidden="true">←</span></a>
            </div>
            <div class="relative min-h-72 overflow-hidden bg-glowy-champagne/10">
                <div class="absolute inset-8 rounded-[45%_55%_42%_58%/56%_44%_56%_44%] border border-white/15" aria-hidden="true"></div>
                <span class="absolute start-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-8xl text-glowy-champagne" aria-hidden="true"><span class="promo-star inline-block">✦</span></span>
                <span class="absolute bottom-8 end-10 rounded-full bg-white/10 px-4 py-2 text-sm font-bold backdrop-blur">4 خطوات. وهج واحد.</span>
            </div>
        </div>
    </section>

    <section class="bg-white py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="mb-10" data-reveal><p class="section-eyebrow">أسماء مختارة بعناية</p><h2 class="section-title">ماركات نثق بها</h2></div>
            <div class="flex flex-wrap gap-3">
                @forelse($brands as $brand)
                    <a href="{{ route('brands.show', $brand) }}" class="brand-pill min-w-36 flex-1 rounded-2xl border border-glowy-champagne px-6 py-5 text-center font-display font-bold text-glowy-plum" data-reveal style="--reveal-delay: {{ $loop->index * 60 }}ms">{{ $brand->name_ar }}</a>
                @empty
                    <p class="text-glowy-muted">ستظهر الماركات المميزة هنا.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section id="routine" class="relative overflow-hidden py-20">
        <div class="mx-auto grid max-w-7xl gap-10 px-5 sm:px-8 lg:grid-cols-[.9fr_1.1fr] lg:items-center lg:gap-16">
            <div class="relative" data-reveal>
                <div class="overflow-hidden rounded-[3rem_3rem_3rem_8rem] bg-glowy-blush shadow-[0_24px_70px_rgba(58,47,51,.12)]">
                    <img src="{{ asset('images/editorial/glowy-routine.webp') }}"
                         alt="روتين عناية يومي أنيق من أربع خطوات"
                         class="h-[34rem] w-full object-cover sm:h-[42rem]"
                         width="1122" height="1402" loading="lazy" decoding="async">
                </div>
                <span class="hero-sparkle absolute -end-4 top-12 text-5xl text-glowy-rose" aria-hidden="true">✦</span>
                <div class="absolute -bottom-5 start-5 rounded-2xl bg-white px-5 py-4 shadow-[0_18px_50px_rgba(58,47,51,.14)] sm:start-10">
                    <p class="text-xs font-bold text-glowy-muted">وقت بسيط لكِ</p>
                    <p class="mt-1 font-display font-bold text-glowy-plum">أربع خطوات للعناية ✦</p>
                </div>
            </div>
            <div>
                <div data-reveal>
                    <p class="section-eyebrow">خطوات واضحة كل يوم</p>
                    <h2 class="section-title text-4xl sm:text-5xl">روتين عناية أبسط</h2>
                    <p class="mt-5 max-w-xl leading-8 text-glowy-muted">لا تحتاج العناية أن تكون معقدة. رتّبناها لكِ في خطوات واضحة تساعدك على الاستمرار.</p>
                </div>
                <div class="mt-9 grid gap-4 sm:grid-cols-2">
                    @foreach([['01','نظّفي','ابدئي بغسول مناسب لنوع بشرتك.'],['02','عالجي','استخدمي السيروم المخصص لاحتياجك.'],['03','رطّبي','ادعمي حاجز البشرة بمرطب لطيف.'],['04','احمي','ضعي واقي الشمس كل صباح.']] as $step)
                        <div class="routine-step group rounded-3xl border border-transparent bg-glowy-blush p-6" data-reveal style="--reveal-delay: {{ $loop->index * 70 }}ms">
                            <div class="flex items-center justify-between">
                                <span class="font-display text-sm font-bold text-glowy-rose-deep">{{ $step[0] }}</span>
                                <span class="text-glowy-rose/50 transition duration-500 group-hover:rotate-90 group-hover:text-glowy-rose">✦</span>
                            </div>
                            <h3 class="mt-5 font-display text-xl font-bold text-glowy-plum">{{ $step[1] }}</h3>
                            <p class="mt-2 leading-7 text-glowy-muted">{{ $step[2] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="relative overflow-hidden bg-glowy-blush py-20">
        <span class="absolute end-[8%] top-8 text-5xl text-white" aria-hidden="true">✦</span>
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="text-center" data-reveal><p class="section-eyebrow">ثقة في كل اختيار</p><h2 class="section-title">لماذا Glowy؟</h2></div>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                @foreach([['منتجات أصلية','نهتم بمصدر كل منتج وجودته.'],['اختيارات مفهومة','نرتب العناية حسب احتياج بشرتك.'],['توصيل محسوب بدقة','رسوم واضحة حسب مسافة الطريق الفعلية.']] as $reason)
                    <div class="why-card rounded-[2rem] bg-white p-8 text-center" data-reveal style="--reveal-delay: {{ $loop->index * 80 }}ms">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-glowy-blush text-3xl text-glowy-rose">✦</span>
                        <h3 class="mt-5 font-display text-xl font-bold text-glowy-plum">{{ $reason[0] }}</h3>
                        <p class="mt-2 leading-7 text-glowy-muted">{{ $reason[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.storefront>
