<x-layouts.admin title="التصنيفات | Glowy">
    <h1 class="text-3xl font-bold text-glowy-plum">التصنيفات</h1>
    @if(session('status'))<p class="mt-5 rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('status') }}</p>@endif
    @if ($errors->any())<div class="mt-5 rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-800" role="alert"><ul class="grid gap-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form class="mt-8 grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-3" method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">@csrf
        <x-ui.input name="name_ar" label="الاسم بالعربية" required/><x-ui.input name="name_en" label="الاسم بالإنجليزية" dir="ltr"/><x-ui.input name="slug" label="الرابط" dir="ltr" required/>
        <label class="grid gap-2 text-sm font-bold">التصنيف الأب<select name="parent_id" class="min-h-11 rounded-xl border-glowy-champagne"><option value="">رئيسي</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name_ar }}</option>@endforeach</select></label>
        <x-ui.input name="sort_order" label="الترتيب" type="number" min="0" value="0"/><label class="grid gap-2 text-sm font-bold">الصورة<input type="file" name="image" accept="image/*" class="text-sm"></label>
        <label class="grid gap-2 text-sm font-bold md:col-span-3">الوصف<textarea name="description" rows="2" class="rounded-xl border-glowy-champagne px-4 py-3"></textarea></label>
        <div class="flex gap-4 text-sm font-bold"><label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> نشط</label><label><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1"> مميز</label></div><x-ui.button type="submit">إضافة التصنيف</x-ui.button>
    </form>
    <div class="mt-8 space-y-3">@forelse($categories as $category)
        <details class="rounded-2xl border border-glowy-champagne bg-white p-5"><summary class="cursor-pointer font-bold text-glowy-plum">{{ $category->name_ar }} <span class="text-xs font-normal text-glowy-muted">{{ $category->parent?->name_ar ?? 'رئيسي' }}</span></summary>
            <form class="mt-5 grid gap-3 md:grid-cols-3" method="POST" action="{{ route('admin.categories.update',$category) }}" enctype="multipart/form-data">@csrf @method('PATCH')
                <x-ui.input name="name_ar" label="الاسم" :value="$category->name_ar" required/><x-ui.input name="name_en" label="English" :value="$category->name_en" dir="ltr"/><x-ui.input name="slug" label="الرابط" :value="$category->slug" dir="ltr" required/>
                <label class="grid gap-2 text-sm font-bold">الأب<select name="parent_id" class="min-h-11 rounded-xl border-glowy-champagne"><option value="">رئيسي</option>@foreach($categories->where('id','!=',$category->id) as $parent)<option value="{{ $parent->id }}" @selected($category->parent_id===$parent->id)>{{ $parent->name_ar }}</option>@endforeach</select></label>
                <x-ui.input name="sort_order" label="الترتيب" :value="$category->sort_order" type="number" min="0"/><label class="grid gap-2 text-sm font-bold">استبدال الصورة<input type="file" name="image" accept="image/*" class="text-sm"></label>
                <label class="grid gap-2 text-sm font-bold md:col-span-3">الوصف<textarea name="description" rows="2" class="rounded-xl border-glowy-champagne px-4 py-3">{{ $category->description }}</textarea></label>
                <div class="flex gap-4 text-sm font-bold"><label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> نشط</label><label><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" @checked($category->is_featured)> مميز</label></div><x-ui.button type="submit">حفظ</x-ui.button>
            </form><form class="mt-3" method="POST" action="{{ route('admin.categories.destroy',$category) }}">@csrf @method('DELETE')<button class="text-xs font-bold text-red-700">حذف</button></form>
        </details>
    @empty<x-ui.empty-state title="لا توجد تصنيفات بعد" description="أضيفي أول تصنيف لتنظيم المتجر."/>@endforelse</div>
</x-layouts.admin>
