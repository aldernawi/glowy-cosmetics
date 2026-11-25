<x-layouts.admin title="محتوى المتجر | Glowy">
    <h1 class="text-3xl font-bold text-glowy-plum">محتوى الصفحة الرئيسية</h1>
    <p class="mt-2 text-glowy-muted">تحكم مركزي في الرسالة، الأقسام المميزة، التواصل وبيانات الظهور في البحث.</p>
    @if(session('status'))<p class="mt-5 rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('status') }}</p>@endif
    @if($errors->any())<div class="mt-5 rounded-2xl bg-rose-50 p-4 text-sm text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.content.update') }}" enctype="multipart/form-data" class="mt-8 grid gap-8">@csrf @method('PUT')
        <section class="grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-2"><h2 class="text-xl font-bold text-glowy-plum md:col-span-2">Hero والبنر الترويجي</h2>
            <x-ui.input name="hero_title" label="عنوان Hero" :value="old('hero_title',$content['hero_title'])" required/><x-ui.input name="hero_cta_label" label="نص الزر" :value="old('hero_cta_label',$content['hero_cta_label'])" required/>
            <x-ui.input name="hero_cta_url" label="رابط زر Hero" :value="old('hero_cta_url',$content['hero_cta_url'])" placeholder="/products" dir="ltr"/>
            <label class="grid gap-2 text-sm font-bold md:col-span-2">الوصف<textarea name="hero_subtitle" class="rounded-xl border-glowy-champagne" rows="3" required>{{ old('hero_subtitle',$content['hero_subtitle']) }}</textarea></label>
            <label class="grid gap-2 text-sm font-bold md:col-span-2">صورة Hero<input type="file" name="hero_image" accept="image/*" class="rounded-xl border border-glowy-champagne p-3 text-sm"><span class="font-normal text-glowy-muted">اتركيها فارغة للاحتفاظ بالصورة الحالية.</span></label>
            <x-ui.input name="promo_title" label="عنوان البانر" :value="old('promo_title',$content['promo_title'])" required/><x-ui.input name="promo_cta_label" label="نص زر البانر" :value="old('promo_cta_label',$content['promo_cta_label'])" required/>
            <x-ui.input name="promo_cta_url" label="رابط زر البانر" :value="old('promo_cta_url',$content['promo_cta_url'])" placeholder="/products" dir="ltr"/>
            <label class="grid gap-2 text-sm font-bold md:col-span-2">وصف البانر<textarea name="promo_body" class="rounded-xl border-glowy-champagne" rows="3" required>{{ old('promo_body',$content['promo_body']) }}</textarea></label>
        </section>
        <section class="grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-2"><div class="md:col-span-2"><h2 class="text-xl font-bold text-glowy-plum">الأقسام المختارة</h2><p class="mt-1 text-sm text-glowy-muted">عند عدم الاختيار تُستخدم علامات «مميز» و«وصل حديثًا» من الكتالوج.</p></div>
            @foreach([['featured_category_ids','التصنيفات المميزة',$categories],['featured_brand_ids','الماركات المميزة',$brands],['featured_concern_ids','احتياجات البشرة',$concerns],['new_product_ids','منتجات وصل حديثًا',$products],['best_seller_ids','الأكثر مبيعًا',$products]] as [$name,$label,$options])
                <label class="grid gap-2 text-sm font-bold"><span>{{ $label }}</span><select name="{{ $name }}[]" multiple class="min-h-36 rounded-xl border-glowy-champagne">@foreach($options as $option)<option value="{{ $option->id }}" @selected(in_array($option->id,old($name,$content[$name])))>{{ $option->name_ar }}</option>@endforeach</select></label>
            @endforeach
        </section>
        <section class="grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-2"><h2 class="text-xl font-bold text-glowy-plum md:col-span-2">الإعلان والتواصل</h2><x-ui.input name="announcement" label="شريط الإعلان" :value="old('announcement',$content['announcement'])"/><x-ui.input name="phone" label="الهاتف" :value="old('phone',$content['phone'])"/><x-ui.input name="whatsapp" label="واتساب بدون +" :value="old('whatsapp',$content['whatsapp'])" dir="ltr"/><x-ui.input name="email" label="البريد" :value="old('email',$content['email'])"/><x-ui.input name="address" label="العنوان" :value="old('address',$content['address'])"/><x-ui.input name="working_hours" label="ساعات العمل" :value="old('working_hours',$content['working_hours'])"/></section>
        <section class="grid gap-4 rounded-3xl border border-glowy-champagne bg-white p-6 md:grid-cols-2"><h2 class="text-xl font-bold text-glowy-plum md:col-span-2">SEO الرئيسي</h2><x-ui.input name="meta_title" label="عنوان الصفحة" :value="old('meta_title',$content['meta_title'])"/><label class="grid gap-2 text-sm font-bold md:col-span-2">الوصف لمحركات البحث<textarea name="meta_description" class="rounded-xl border-glowy-champagne" rows="2">{{ old('meta_description',$content['meta_description']) }}</textarea></label></section>
        <x-ui.button type="submit" class="w-fit">حفظ محتوى المتجر</x-ui.button>
    </form>

    <section class="mt-8 rounded-3xl border border-glowy-champagne bg-white p-6">
        <div><h2 class="text-xl font-bold text-glowy-plum">بنرات إضافية</h2><p class="mt-1 text-sm text-glowy-muted">تظهر البنرات المفعلة بعد أقسام المنتجات، بترتيب الرقم الأصغر أولًا.</p></div>
        <form method="POST" action="{{ route('admin.content.banners.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 md:grid-cols-2">@csrf
            <x-ui.input name="title" label="عنوان البنر" required/><x-ui.input name="sort_order" type="number" min="0" label="الترتيب" value="0" required/>
            <label class="grid gap-2 text-sm font-bold md:col-span-2">الوصف<textarea name="body" class="rounded-xl border-glowy-champagne" rows="2"></textarea></label>
            <x-ui.input name="cta_label" label="نص الزر (اختياري)"/><x-ui.input name="cta_url" label="رابط الزر (اختياري)" placeholder="/products" dir="ltr"/>
            <label class="grid gap-2 text-sm font-bold">الصورة (اختيارية)<input name="image" type="file" accept="image/*" class="rounded-xl border border-glowy-champagne p-3 text-sm"></label>
            <label class="flex items-end gap-2 pb-3 text-sm font-bold"><input name="is_active" type="checkbox" value="1" checked class="rounded border-glowy-champagne text-glowy-rose focus:ring-glowy-rose"> ظاهر للعميلات</label>
            <x-ui.button type="submit" class="w-fit">إضافة البنر</x-ui.button>
        </form>
        <div class="mt-7 grid gap-4">
            @forelse($banners as $banner)
                <form method="POST" action="{{ route('admin.content.banners.update',$banner) }}" enctype="multipart/form-data" class="grid gap-3 rounded-2xl bg-glowy-ivory p-4 md:grid-cols-2">@csrf @method('PATCH')
                    <x-ui.input name="title" label="العنوان" :value="$banner->title" required/><x-ui.input name="sort_order" type="number" min="0" label="الترتيب" :value="$banner->sort_order" required/>
                    <label class="grid gap-2 text-sm font-bold md:col-span-2">الوصف<textarea name="body" class="rounded-xl border-glowy-champagne" rows="2">{{ $banner->body }}</textarea></label>
                    <x-ui.input name="cta_label" label="نص الزر" :value="$banner->cta_label"/><x-ui.input name="cta_url" label="رابط الزر" :value="$banner->cta_url" dir="ltr"/>
                    <label class="grid gap-2 text-sm font-bold">استبدال الصورة<input name="image" type="file" accept="image/*" class="rounded-xl border border-glowy-champagne p-3 text-sm"></label>
                    <label class="flex items-end gap-2 pb-3 text-sm font-bold"><input name="is_active" type="checkbox" value="1" @checked($banner->is_active) class="rounded border-glowy-champagne text-glowy-rose focus:ring-glowy-rose"> ظاهر</label>
                    <div class="flex flex-wrap items-center gap-3"><x-ui.button type="submit">حفظ</x-ui.button><button form="banner-delete-{{ $banner->id }}" type="submit" class="text-sm font-bold text-red-700">حذف البنر</button></div>
                    @if($banner->image_path)<img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="" class="h-20 w-28 rounded-xl object-cover">@endif
                </form>
                <form id="banner-delete-{{ $banner->id }}" method="POST" action="{{ route('admin.content.banners.destroy',$banner) }}">@csrf @method('DELETE')</form>
            @empty
                <p class="rounded-2xl bg-glowy-ivory p-4 text-sm text-glowy-muted">لا توجد بنرات إضافية. يمكنكِ إضافة ما تحتاجينه فقط.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-8 rounded-3xl border border-glowy-champagne bg-white p-6">
        <h2 class="text-xl font-bold text-glowy-plum">إظهار وترتيب أقسام الرئيسية</h2><p class="mt-1 text-sm text-glowy-muted">تعطيل القسم يخفيه من الصفحة دون حذف منتجاته أو محتواه.</p>
        <form method="POST" action="{{ route('admin.content.sections.update') }}" class="mt-5 grid gap-3 md:grid-cols-2">@csrf @method('PUT')
            @foreach(['concerns'=>'احتياجات البشرة','categories'=>'التصنيفات','new_arrivals'=>'وصل حديثًا','best_sellers'=>'الأكثر طلبًا','promotion'=>'البلوك الترويجي','brands'=>'الماركات','routine'=>'روتين العناية','why_glowy'=>'لماذا Glowy؟','banners'=>'البنرات الإضافية'] as $key => $label)
                <div class="flex items-center justify-between gap-4 rounded-2xl bg-glowy-ivory p-4"><input type="hidden" name="sections[{{ $loop->index }}][key]" value="{{ $key }}"><label class="flex items-center gap-2 text-sm font-bold"><input name="sections[{{ $loop->index }}][is_visible]" type="checkbox" value="1" @checked($sections[$key]['is_visible']) class="rounded border-glowy-champagne text-glowy-rose focus:ring-glowy-rose"> {{ $label }}</label><label class="flex items-center gap-2 text-xs font-bold text-glowy-muted">الترتيب <input class="w-16 rounded-lg border-glowy-champagne px-2 py-1 text-center" name="sections[{{ $loop->index }}][sort_order]" type="number" min="0" value="{{ $sections[$key]['sort_order'] }}"></label></div>
            @endforeach
            <x-ui.button type="submit" class="w-fit">حفظ ترتيب الأقسام</x-ui.button>
        </form>
    </section>
</x-layouts.admin>
