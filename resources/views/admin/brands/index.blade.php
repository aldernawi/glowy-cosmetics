<x-layouts.admin title="الماركات | Glowy">
    <div>
        <p class="text-sm font-bold text-glowy-rose">الكتالوج</p>
        <h1 class="mt-1 text-3xl font-bold text-glowy-plum">الماركات</h1>
    </div>

    @if (session('status'))
        <p class="mt-5 rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div class="mt-5 rounded-2xl bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <form class="mt-8 grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-3" method="POST" action="{{ route('admin.brands.store') }}" enctype="multipart/form-data">
        @csrf
        <x-ui.input name="name_ar" label="الاسم بالعربية" required/>
        <x-ui.input name="name_en" label="الاسم بالإنجليزية" dir="ltr"/>
        <x-ui.input name="slug" label="الرابط المختصر" dir="ltr" placeholder="يُنشأ تلقائيًا عند تركه فارغًا"/>
        <x-ui.input name="website" label="الموقع الإلكتروني" dir="ltr"/>
        <x-ui.input name="sort_order" label="ترتيب العرض" type="number" min="0" value="0"/>
        <label class="grid gap-2 text-sm font-bold">الشعار<input type="file" name="logo" accept="image/*" class="text-sm"></label>
        <label class="grid gap-2 text-sm font-bold md:col-span-3">الوصف<textarea name="description" rows="3" class="rounded-xl border-glowy-champagne px-4 py-3"></textarea></label>
        <div class="flex gap-4 text-sm font-bold">
            <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> مفعّلة</label>
            <label><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1"> مميزة</label>
        </div>
        <x-ui.button type="submit" class="self-end">إضافة ماركة</x-ui.button>
    </form>

    <div class="mt-8 space-y-4">
        @forelse ($brands as $brand)
            <details class="rounded-3xl border border-glowy-champagne bg-white p-5">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        @if ($brand->logo_path)
                            <img src="{{ Storage::disk('public')->url($brand->logo_path) }}" alt="" class="h-10 w-10 rounded-xl object-contain">
                        @endif
                        <div>
                            <p class="font-bold text-glowy-plum">{{ $brand->name_ar }}</p>
                            <p class="text-xs text-glowy-muted" dir="ltr">{{ $brand->slug }}</p>
                        </div>
                    </div>
                    <span class="text-xs {{ $brand->is_active ? 'text-emerald-700' : 'text-slate-500' }}">{{ $brand->is_active ? 'مفعّلة' : 'مخفية' }}</span>
                </summary>

                <form class="mt-5 grid gap-3 border-t border-glowy-champagne pt-5 md:grid-cols-3" method="POST" action="{{ route('admin.brands.update', $brand) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <x-ui.input name="name_ar" label="الاسم بالعربية" :value="$brand->name_ar" required/>
                    <x-ui.input name="name_en" label="الاسم بالإنجليزية" :value="$brand->name_en" dir="ltr"/>
                    <x-ui.input name="slug" label="الرابط" :value="$brand->slug" dir="ltr" placeholder="يُنشأ تلقائيًا عند تركه فارغًا"/>
                    <x-ui.input name="website" label="الموقع" :value="$brand->website" dir="ltr"/>
                    <x-ui.input name="sort_order" label="الترتيب" :value="$brand->sort_order" type="number" min="0"/>
                    <label class="grid gap-2 text-sm font-bold">استبدال الشعار<input type="file" name="logo" accept="image/*" class="text-sm"></label>
                    <label class="grid gap-2 text-sm font-bold md:col-span-3">الوصف<textarea name="description" rows="3" class="rounded-xl border-glowy-champagne px-4 py-3">{{ $brand->description }}</textarea></label>
                    <div class="flex gap-4 text-sm font-bold">
                        <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($brand->is_active)> مفعّلة</label>
                        <label><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" @checked($brand->is_featured)> مميزة</label>
                    </div>
                    <x-ui.button type="submit">حفظ</x-ui.button>
                </form>

                <form class="mt-3" method="POST" action="{{ route('admin.brands.destroy', $brand) }}" onsubmit="return confirm('حذف الماركة غير المرتبطة فقط. متابعة؟')">
                    @csrf
                    @method('DELETE')
                    <button class="text-xs font-bold text-red-700">حذف الماركة</button>
                </form>
            </details>
        @empty
            <x-ui.empty-state title="لا توجد ماركات بعد" description="أضيفي أول ماركة لتظهر ضمن الكتالوج."/>
        @endforelse
    </div>

    <div class="mt-6">{{ $brands->links() }}</div>
</x-layouts.admin>
