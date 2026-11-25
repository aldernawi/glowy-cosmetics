<x-layouts.admin title="المخزون | Glowy">
    <h1 class="text-3xl font-bold text-glowy-plum">المخزون</h1>
    <p class="mt-2 text-glowy-muted">كل تعديل يُسجل في دفتر الحركات ولا يمكن أن يجعل الرصيد سالبًا.</p>

    @if (session('status'))
        <p class="mt-5 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</p>
    @endif

    <form class="mt-8 grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-2" method="POST" action="{{ route('admin.stock.adjustments.store') }}">
        @csrf
        <label class="grid gap-2 text-sm font-bold md:col-span-2">المتغير القابل للبيع
            <select name="product_variant_id" class="min-h-11 rounded-xl border-glowy-champagne" required>
                <option value="">اختاري منتجًا</option>
                @foreach ($variants as $variant)
                    <option value="{{ $variant->id }}" @selected(old('product_variant_id') == $variant->id)>{{ $variant->product->name_ar }} — {{ $variant->sku }} (المتاح: {{ $variant->stock_quantity }})</option>
                @endforeach
            </select>
            @error('product_variant_id')<span class="text-xs text-red-700">{{ $message }}</span>@enderror
        </label>
        <div class="grid gap-1"><x-ui.input name="delta" label="التغيير في الكمية (+ أو -)" type="number" :value="old('delta')" required />@error('delta')<span class="text-xs font-bold text-red-700">{{ $message }}</span>@enderror</div>
        <label class="grid gap-2 text-sm font-bold">نوع الحركة
            <select name="type" class="min-h-11 rounded-xl border-glowy-champagne" required>
                @foreach (['manual_in' => 'إضافة يدوية (+)', 'manual_out' => 'إخراج يدوي (-)', 'adjustment' => 'تسوية (+ أو -)', 'opening' => 'رصيد افتتاحي (+)', 'damage' => 'تالف (-)'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<span class="text-xs font-bold text-red-700">{{ $message }}</span>@enderror
        </label>
        <label class="grid gap-2 text-sm font-bold md:col-span-2">ملاحظات
            <textarea name="notes" rows="3" class="rounded-xl border-glowy-champagne">{{ old('notes') }}</textarea>
        </label>
        <x-ui.button type="submit" class="md:col-span-2">حفظ التعديل</x-ui.button>
    </form>

    <div class="mt-8 overflow-hidden rounded-3xl border border-glowy-champagne bg-white">
        <table class="w-full text-right text-sm">
            <thead class="bg-glowy-blush text-glowy-plum"><tr><th class="px-5 py-4">المنتج</th><th class="px-5 py-4">SKU</th><th class="px-5 py-4">المتاح</th></tr></thead>
            <tbody class="divide-y divide-glowy-champagne/60">
                @forelse ($variants as $variant)
                    <tr><td class="px-5 py-4 font-bold">{{ $variant->product->name_ar }}</td><td class="px-5 py-4" dir="ltr">{{ $variant->sku }}</td><td class="px-5 py-4">{{ $variant->stock_quantity }}</td></tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-8 text-center text-glowy-muted">لا توجد متغيرات نشطة بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $variants->links() }}</div>
</x-layouts.admin>
