<x-layouts.admin title="جهاز الكاشير | Glowy">
    <div><p class="text-sm font-bold text-glowy-rose">نقطة البيع</p><h1 class="mt-1 text-3xl font-bold text-glowy-plum">جهاز الكاشير</h1><p class="mt-2 max-w-3xl text-sm leading-7 text-glowy-muted">افتحي هذه الصفحة من كمبيوتر المحل نفسه ثم اضغطي ربط. بعدها تعمل شاشة الكاشير دون إنترنت، وتُرفع المبيعات تلقائيًا عند رجوعه.</p></div>
    @include('admin.pos.tabs')
    @if (session('status'))<p class="mt-5 rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-800" role="status">{{ session('status') }}</p>@endif
    @if ($errors->any())<div class="mt-5 rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-800" role="alert"><ul class="grid gap-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if ($active)<section class="mt-6 rounded-3xl border border-glowy-champagne bg-white p-6"><h2 class="text-xl font-bold">الجهاز المربوط حاليًا</h2><p class="mt-3 text-sm">{{ $active->name }} — {{ $active->user?->name ?? 'بدون كاشير' }}</p><p class="mt-1 text-sm text-glowy-muted">آخر اتصال: {{ $active->last_seen_at?->format('Y-m-d H:i') ?? 'لم يتصل بعد' }}</p><form class="mt-4" method="POST" action="{{ route('admin.pos.device.destroy', $active) }}" onsubmit="return confirm('لن يستطيع الجهاز رفع مبيعاته حتى يُربط من جديد. متابعة؟')">@csrf @method('DELETE')<button class="text-sm font-bold text-red-700">إلغاء الربط</button></form></section>@endif
    <form class="mt-6 grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-2" method="POST" action="{{ route('admin.pos.device.store') }}">@csrf
        <x-ui.input name="name" label="اسم الجهاز" :value="old('name', 'كاشير المحل')" required/>
        <label class="grid gap-2 text-sm font-bold">الكاشير<select name="user_id" class="min-h-11 rounded-xl border-glowy-champagne" required>@foreach ($cashiers as $cashier)<option value="{{ $cashier->id }}" @selected(old('user_id', auth()->id()) == $cashier->id)>{{ $cashier->name }}</option>@endforeach</select></label>
        @if ($active)<label class="flex items-start gap-2 text-sm font-bold text-amber-900 md:col-span-2"><input type="checkbox" name="replace" value="1" class="mt-1"> استبدال الجهاز المربوط. تأكدي أن مبيعاته مرفوعة كلها أولًا، وإلا تبقى فيه حتى يُربط من جديد.</label>@endif
        <x-ui.button type="submit" class="md:col-span-2">ربط هذا الكمبيوتر وفتح الكاشير</x-ui.button>
    </form>
</x-layouts.admin>
