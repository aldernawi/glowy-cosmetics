<x-layouts.admin title="التوصيل | Glowy">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-bold text-glowy-rose">الإعدادات التشغيلية</p>
            <h1 class="mt-1 text-3xl font-bold text-glowy-plum">التوصيل</h1>
            <p class="mt-2 text-sm text-glowy-muted">تعتمد الرسوم على مسافة طريق القيادة الفعلية، وليس اسم المدينة.</p>
        </div>
        <span class="rounded-full bg-glowy-blush px-4 py-2 text-sm font-bold text-glowy-rose">الحد الأقصى: {{ $maximumDistance ?? '—' }} كم</span>
    </div>

    @if (session('status'))
        <p class="mt-5 rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div class="mt-5 rounded-2xl bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_23rem]">
        <form method="POST" action="{{ route('admin.delivery.origin.update') }}" class="rounded-3xl border border-glowy-champagne bg-white p-5 sm:p-6">
            @csrf
            @method('PUT')

            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-glowy-rose">١. نقطة الانطلاق</p>
                    <h2 class="mt-1 font-display text-xl font-bold text-glowy-plum">أين يبدأ توصيل Glowy؟</h2>
                    <p class="mt-1 max-w-xl text-sm leading-6 text-glowy-muted">استخدمي موقعك الحالي، ابحثي بالعنوان، أو انقري الخريطة. لا يُطلب موقع المتصفح إلا عند الضغط على الزر.</p>
                </div>
                <span class="rounded-full bg-glowy-ivory px-3 py-1.5 text-xs font-bold text-glowy-muted">خريطة مجانية محسّنة</span>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <button id="origin-use-location" type="button" class="glowy-map-action glowy-map-action--primary"><span aria-hidden="true">⌖</span> استخدمي موقعي الحالي</button>
                <button id="origin-refresh-address" type="button" class="glowy-map-action"><span aria-hidden="true">↻</span> جلب اسم العنوان للنقطة</button>
            </div>

            <div class="mt-4 grid gap-2 sm:grid-cols-[1fr_auto]">
                <label class="grid gap-2 text-sm font-bold text-glowy-ink">
                    ابحثي عن موقع المتجر
                    <input id="origin-places-search" class="min-h-11 rounded-xl border border-glowy-champagne bg-white px-4 outline-none transition focus:border-glowy-rose" placeholder="مثال: الظهرة، طرابلس">
                </label>
                <button id="origin-places-search-button" type="button" class="self-end rounded-xl border border-glowy-champagne px-5 py-3 text-sm font-bold text-glowy-rose transition hover:bg-glowy-blush">بحث</button>
            </div>
            <div id="origin-places-results" class="mt-3 grid gap-1 rounded-2xl bg-glowy-ivory empty:hidden"></div>

            <div class="glowy-map-frame mt-5">
                <div id="store-origin-map" class="glowy-location-map h-80" data-map-provider="maplibre" data-map-mode="origin" data-latitude="{{ old('store_latitude', $setting?->store_latitude) }}" data-longitude="{{ old('store_longitude', $setting?->store_longitude) }}"></div>
                <div class="glowy-map-hint"><span aria-hidden="true">✦</span> انقري الخريطة أو اسحبي العلامة بدقة</div>
            </div>

            <div class="mt-4 rounded-2xl bg-glowy-ivory p-4">
                <p class="text-xs font-bold text-glowy-muted">النقطة المختارة</p>
                <p id="origin-location-summary" class="mt-1 text-sm font-bold leading-6 text-glowy-plum">{{ old('store_formatted_address', $setting?->store_formatted_address) ?: 'لم يُحدد موقع بعد.' }}</p>
                <p id="origin-map-status" data-map-status aria-live="polite" class="mt-2 text-sm leading-6 text-glowy-muted">اختاري الموقع ثم احفظي الإعدادات.</p>
            </div>

            <details class="mt-4 rounded-2xl border border-glowy-champagne px-4 py-3">
                <summary class="cursor-pointer text-sm font-bold text-glowy-plum">تعديل الإحداثيات والعنوان يدويًا</summary>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <x-ui.input id="store-latitude" name="store_latitude" label="خط العرض" :value="old('store_latitude', $setting?->store_latitude)" dir="ltr" required/>
                    <x-ui.input id="store-longitude" name="store_longitude" label="خط الطول" :value="old('store_longitude', $setting?->store_longitude)" dir="ltr" required/>
                    <x-ui.input id="store-address" name="store_formatted_address" label="العنوان المحفوظ" :value="old('store_formatted_address', $setting?->store_formatted_address)" class="md:col-span-2"/>
                    <input id="store-place-id" type="hidden" name="store_place_id" value="{{ old('store_place_id', $setting?->store_place_id) }}">
                </div>
            </details>

            <x-ui.button type="submit" class="mt-5">حفظ نقطة الانطلاق</x-ui.button>
        </form>

        <aside class="rounded-3xl bg-glowy-plum p-6 text-white">
            <p class="text-sm font-bold text-glowy-champagne">قاعدة الحدود</p>
            <h2 class="mt-2 font-display text-2xl font-bold">نطاق واضح بلا تخمين</h2>
            <ol class="mt-5 grid gap-4 text-sm leading-7 text-white/80">
                <li><strong class="text-white">١.</strong> تبدأ أول شريحة نشطة من 0 كم.</li>
                <li><strong class="text-white">٢.</strong> كل شريحة نشطة تليها تبدأ من نهاية التي قبلها بالضبط.</li>
                <li><strong class="text-white">٣.</strong> الحد الأعلى مشمول؛ لذلك 10 كم تدخل في شريحة 0–10.</li>
                <li><strong class="text-white">٤.</strong> إذا تجاوزت المسافة آخر حد نشط، يُرفض الطلب لأنه خارج نطاق التوصيل.</li>
            </ol>
        </aside>
    </section>

    <section class="mt-8 rounded-3xl border border-glowy-champagne bg-white p-5 sm:p-6">
        <h2 class="font-display text-xl font-bold text-glowy-plum">شرائح المسافة</h2>
        <p class="mt-1 text-sm text-glowy-muted">إضافة أو تعديل أو تعطيل أي شريحة لا يُحفظ إذا أحدث فجوة أو تداخلًا في النطاق النشط.</p>

        <form method="POST" action="{{ route('admin.delivery.tiers.store') }}" class="mt-6 grid gap-4 rounded-2xl bg-glowy-ivory p-5 md:grid-cols-5">
            @csrf
            <x-ui.input name="min_distance_km" label="من (كم)" inputmode="decimal" dir="ltr" required/>
            <x-ui.input name="max_distance_km" label="إلى (كم)" inputmode="decimal" dir="ltr" required/>
            <x-ui.input name="fee" label="الرسوم (د.ل)" inputmode="decimal" dir="ltr" required/>
            <x-ui.input name="sort_order" label="ترتيب العرض" :value="$tiers->count()" type="number" min="0"/>
            <label class="flex items-end gap-2 pb-3 text-sm font-bold"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> نشطة</label>
            <x-ui.button type="submit" class="md:col-span-5">إضافة شريحة</x-ui.button>
        </form>

        <div class="mt-6 space-y-3">
            @forelse ($tiers as $tier)
                <div class="rounded-2xl border border-glowy-champagne p-4">
                    <form method="POST" action="{{ route('admin.delivery.tiers.update', $tier) }}" class="grid gap-3 lg:grid-cols-[1fr_1fr_1fr_1fr_auto_auto]">
                        @csrf
                        @method('PATCH')
                        <x-ui.input name="min_distance_km" label="من" :value="$tier->min_distance_km" inputmode="decimal" dir="ltr" required/>
                        <x-ui.input name="max_distance_km" label="إلى" :value="$tier->max_distance_km" inputmode="decimal" dir="ltr" required/>
                        <x-ui.input name="fee" label="الرسوم" :value="$tier->fee" inputmode="decimal" dir="ltr" required/>
                        <x-ui.input name="sort_order" label="ترتيب العرض" :value="$tier->sort_order" type="number" min="0"/>
                        <label class="flex items-end gap-2 pb-3 text-sm font-bold"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($tier->is_active)> نشطة</label>
                        <div class="flex items-end"><x-ui.button type="submit">حفظ</x-ui.button></div>
                    </form>
                    <form method="POST" action="{{ route('admin.delivery.tiers.destroy', $tier) }}" class="mt-3">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-bold text-red-700">حذف الشريحة</button>
                    </form>
                </div>
            @empty
                <p class="rounded-2xl bg-glowy-blush p-6 text-center text-sm text-glowy-muted">لم تُضف أي شريحة بعد. أضيفي نطاق 0–… أولًا.</p>
            @endforelse
        </div>
    </section>
</x-layouts.admin>
