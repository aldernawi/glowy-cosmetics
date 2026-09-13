# AGENTS.md — Glowy Cosmetics

## 1. تعريف المشروع

اسم المشروع: **Glowy Cosmetics**

المشروع عبارة عن متجر إلكتروني عربي RTL متخصص في:
- Skincare
- Dermocosmetics
- العناية بالبشرة
- واقي الشمس
- السيرومات
- المرطبات
- الغسولات
- العناية بالعين
- العناية بالشعر
- العناية بالجسم

الموقع الحالي للاستفادة من المحتوى والهوية فقط:
https://glowy.ly

> لا تنسخ تصميم الموقع الحالي. المطلوب إعادة بناء كاملة بهوية Glowy مع الحفاظ على الشعار والروح البصرية.

---

## 2. الهدف

إنشاء تطبيق Laravel واحد يحتوي على:

1. **Storefront** للعملاء.
2. **Custom Admin Dashboard** للإدارة.
3. إدارة المنتجات والتصنيفات والماركات والمخزون.
4. Cart + Checkout + Orders.
5. تحديد موقع العميل من Google Maps داخل Checkout.
6. حساب رسوم التوصيل حسب **مسافة الطريق الفعلية** من نقطة انطلاق Glowy إلى موقع العميل.
7. الدفع **Cash on Delivery فقط**.
8. تقارير وإعدادات وإدارة محتوى الصفحة الرئيسية.

---

## 3. Stack الإلزامي

استخدم:

- Laravel 13
- PHP 8.4+
- MySQL 8+
- Blade
- Livewire 4
- Alpine.js عند الحاجة
- Tailwind CSS 4
- Vite
- Laravel Authentication
- Spatie Laravel Permission عند الحاجة
- Pest أو PHPUnit

### ممنوع
- لا تستخدم Filament.
- لا تستخدم Admin Template جاهز.
- لا تستخدم Bootstrap إلا إذا كان موجودًا بالفعل في المشروع ويصعب فصله، وفي مشروع جديد استخدم Tailwind فقط.
- لا تضع Business Logic في Controllers أو Livewire Components بشكل عشوائي.
- لا تستخدم Float للأموال.
- لا تستخدم Route Closures لصفحات التطبيق إذا كانت ستمنع `route:cache`.

---

## 4. أسلوب العمل

قبل أي تعديل:

1. افحص المشروع.
2. اقرأ:
   - composer.json
   - package.json
   - routes
   - migrations
   - models
   - services
   - tests
   - README
   - git status
3. افهم النمط الحالي قبل إضافة أي Architecture جديدة.
4. لا تمسح أو تستبدل كودًا موجودًا بدون سبب واضح.

بعد كل مرحلة:

- شغل الاختبارات.
- افحص RTL.
- افحص Responsive.
- افحص Browser Console.
- افحص Laravel logs.
- أصلح المشاكل قبل الانتقال للمرحلة التالية.

لا تعتبر المرحلة مكتملة لمجرد أن الصفحة "تفتح".

---

## 5. جودة الكود

استخدم:

- Form Requests للـ validation عندما يكون ذلك مناسبًا.
- Policies / Gates للصلاحيات.
- Services للـ business logic.
- Enums أو constants منظمة للحالات.
- Database Transactions للعمليات الحساسة.
- Row locking عندما يلزم لمنع Overselling.
- Eager Loading لمنع N+1.
- Indexes مناسبة في قاعدة البيانات.
- DTOs / Value Objects فقط عندما تضيف قيمة فعلية، بدون Overengineering.

Services المقترحة:

- CartService
- CheckoutService
- OrderService
- PricingService
- StockService
- DeliveryService
- GoogleRoutesService
- CouponService
- HomepageService

---

## 6. الهوية البصرية

الشعار الحالي يجب الحفاظ عليه.

ألوان الهوية الأساسية:

- Primary Rose: `#B07366`
- Dark Plum / Chocolate: `#3A2F33`
- Main Background: `#FCFBFA`
- Soft Blush: `#F8EEEB`
- Soft Champagne: `#E8D7CE`
- Primary Text: `#2E2528`
- Muted Text: `#786B6E`

الشخصية:

- Feminine
- Elegant
- Soft
- Premium
- Cute
- Clean
- Modern
- Warm

لكن **Cute لا يعني طفولي**.

تجنب:
- الوردي المبالغ فيه.
- Glassmorphism المبالغ.
- Gradients في كل مكان.
- الظلال الثقيلة.
- الواجهات العامة Generic.
- الأنيميشن المزعج.

استخدم Sparkles الموجودة في شعار Glowy بشكل خفيف جدًا.

---

## 7. RTL واللغة

- العربية هي اللغة الأساسية.
- التصميم RTL-first.
- كل Component يجب أن يعمل في RTL.
- لا تخلط عربي وإنجليزي في واجهة العميل بدون داعٍ.
- اجعل المشروع قابلًا لدعم الإنجليزية لاحقًا.
- استخدم ترجمة منظمة للنصوص المهمة بدل نشر النصوص Hardcoded في كل الملفات.

خطوط مقترحة:
- Arabic Headings: Alexandria
- Arabic Body: Tajawal
- English: Inter

---

## 8. Storefront

يجب أن يحتوي على:

- Announcement Bar
- Header
- Search
- Navigation
- Hero
- Shop by Concern
- Shop by Category
- New Arrivals
- Best Sellers
- Featured Brands
- Skincare Routine
- Why Glowy
- Social/Instagram section اختياري
- Footer

Navigation المقترحة:
- الرئيسية
- العناية بالبشرة
- واقي الشمس
- العناية بالشعر
- العناية بالجسم
- الماركات
- العروض
- وصل حديثاً

Search placeholder:
`ابحثي عن منتج، ماركة أو احتياج لبشرتك...`

---

## 9. تجربة التسوق حسب احتياج البشرة

Glowy ليس Catalog حسب Brand فقط.

يجب دعم Concerns مثل:
- حب الشباب
- التصبغات
- الجفاف والترطيب
- البشرة الحساسة
- علامات التقدم بالعمر
- المسام والدهون
- الهالات والعناية بالعين
- الحماية من الشمس

ويجب أن تكون قابلة للإدارة من Dashboard.

---

## 10. المنتجات

Product يدعم:

- name_ar
- name_en
- slug
- SKU
- brand
- category
- subcategory
- description
- benefits
- usage
- warnings
- size/volume
- price
- compare_at_price
- active
- featured
- new flag اختياري
- images
- skin types
- concerns
- ingredients
- variants

لا تجعل كل الحقول إلزامية.

إذا كان المحتوى غير موجود، اخفِ القسم في Frontend.

---

## 11. Product Variants

يمكن أن يكون المنتج:

- بدون Variants
- أو بعدة Variants

Variant يدعم:
- SKU
- size / volume
- price
- compare_at_price
- stock quantity
- active

المخزون يجب أن يرتبط بالعنصر القابل للبيع فعليًا.

---

## 12. Cart

- Guest Cart مطلوب.
- احفظ Cart بشكل موثوق.
- لا تثق في السعر القادم من Browser.
- الأسعار تُحسب Server-side.
- لا تسمح بكمية أكبر من المتوفر.
- دعم Coupon Code.
- Cart Drawer + Cart Page بتصميم مناسب.

---

## 13. Checkout — قاعدة المشروع المهمة

كل الطلبات تتم من الموقع.

لا يوجد Payment Gateway.

طريقة الدفع الوحيدة:

**Cash on Delivery**

لا تعرض Payment Selector إذا كان هناك خيار واحد فقط.

بيانات Checkout:

- الاسم الكامل
- رقم الهاتف
- رقم هاتف إضافي اختياري
- المدينة / المنطقة نص مساعد
- العنوان التفصيلي اختياري
- علامة مميزة للموقع اختياري
- ملاحظات الطلب اختياري
- موقع التسليم على Google Maps — إلزامي

---

## 14. Google Maps Location Picker

داخل Checkout:

- اعرض Google Map.
- العميل يحدد موقعه بالنقر على الخريطة.
- Marker قابل للتحريك.
- دعم البحث باستخدام Places Autocomplete.
- يمكن إضافة زر "استخدام موقعي الحالي" بشرط موافقة المستخدم.
- لا تجعل Browser Geolocation إلزاميًا.
- أضف زر واضح: `تأكيد موقع التسليم`.

احفظ عند الاختيار:

- latitude
- longitude
- formatted_address إن توفر
- place_id إن توفر

لا تسمح بإكمال الطلب بدون Location صالح.

---

## 15. نقطة انطلاق Glowy

في Admin Dashboard:

`الإعدادات → التوصيل`

Admin يحدد موقع Glowy على Google Maps.

احفظ:

- store_latitude
- store_longitude
- store_formatted_address
- store_place_id

لا تكتب إحداثيات Glowy Hardcoded.

---

## 16. حساب مسافة التوصيل

التوصيل يعتمد على **Driving Route Distance** وليس اسم المدينة.

Origin:
موقع Glowy المحفوظ.

Destination:
موقع العميل المختار.

استخدم Google Routes API / Route Matrix الحديثة لحساب:

- distance
- duration

Travel mode:
`DRIVING`

### مهم
لا تستخدم Haversine كحساب نهائي للسعر.

يمكن استخدامها فقط لفحص تقريبي أو fallback داخلي، لكن السعر النهائي يجب أن يعتمد على مسافة الطريق الفعلية إذا كانت الخدمة متاحة.

---

## 17. أسعار التوصيل حسب المسافة

Admin ينشئ Distance Tiers.

مثال فقط:

- 0 → 10 كم = 10 د.ل
- أكثر من 10 → 20 كم = 15 د.ل
- أكثر من 20 → 30 كم = 20 د.ل
- أكثر من 30 → 40 كم = 25 د.ل

هذه القيم ليست Hardcoded.

الحد الأقصى للتوصيل هو نهاية أعلى Tier فعال.

إذا تجاوز العميل أعلى Tier:
- امنع إنشاء الطلب.
- اعرض رسالة عربية لطيفة.
- وفر رابط WhatsApp اختياري.

لا تسمح بوجود Overlapping Tiers.

---

## 18. أمان رسوم التوصيل

Frontend يعرض النتيجة فقط.

عند Create Order:

Backend يجب أن يعيد التحقق من:

1. موقع Glowy.
2. موقع العميل.
3. مسافة الطريق.
4. Delivery Tier.
5. Delivery Fee.
6. Cart subtotal.
7. discount.
8. final total.

ممنوع الوثوق بـ:
- delivery_fee المرسلة من المتصفح.
- distance المرسلة من المتصفح.
- total المرسل من المتصفح.

---

## 19. Order Snapshot

عند إنشاء الطلب احفظ Snapshot:

- customer data
- delivery_latitude
- delivery_longitude
- delivery_address
- delivery_place_id
- delivery_distance_km
- delivery_duration_minutes
- delivery_fee
- store_latitude_snapshot
- store_longitude_snapshot
- subtotal
- discount
- total

Order Items تحفظ:
- product name
- variant
- SKU
- unit price
- quantity
- subtotal

حتى لا تتغير الطلبات القديمة إذا تغير المنتج أو الأسعار لاحقًا.

---

## 20. Order Status

Database values:

- pending
- confirmed
- preparing
- ready
- out_for_delivery
- delivered
- cancelled
- returned

UI Arabic labels:
- قيد الانتظار
- تم التأكيد
- قيد التجهيز
- جاهز
- خرج للتوصيل
- تم التوصيل
- ملغي
- مرتجع

Payment:
- method: cash_on_delivery
- status: unpaid / paid

Order Status وPayment Status منفصلان.

---

## 21. المخزون

استخدم StockService.

لا تعدل quantity مباشرة من عدة أماكن.

Stock movements:
- opening
- purchase / manual_in
- order_sale
- adjustment
- return
- damage / manual_out

كل حركة تحفظ:
- sellable item
- delta
- before
- after
- source/reference
- user/admin
- notes
- timestamp

لا تسمح بمخزون سالب.

---

## 22. Admin Dashboard

Prefix:
`/admin`

Sidebar:

- لوحة التحكم
- الطلبات
- المنتجات
  - جميع المنتجات
  - إضافة منتج
  - التصنيفات
  - الماركات
  - أنواع البشرة
  - احتياجات البشرة
  - المكونات
- المخزون
  - المخزون الحالي
  - حركات المخزون
  - منخفض المخزون
- العملاء
- الخصومات والكوبونات
- المحتوى
  - البنرات
  - أقسام الرئيسية
- التوصيل
- التقارير
- المستخدمون والصلاحيات
- الإعدادات

Dashboard KPIs:
- مبيعات اليوم
- مبيعات الشهر
- طلبات اليوم
- الطلبات قيد التنفيذ
- متوسط قيمة الطلب
- عدد العملاء
- منخفض المخزون
- نفد من المخزون

---

## 23. Homepage CMS

لا تجعل Homepage Hardcoded بالكامل.

Admin يستطيع إدارة:
- Hero
- Hero image
- title
- subtitle
- CTA
- banners
- featured categories
- featured concerns
- featured brands
- new arrivals
- best sellers
- promotional blocks

بدون بناء CMS معقد غير ضروري.

---

## 24. SEO

طبق:
- page titles
- meta descriptions
- canonical URLs
- OpenGraph
- product structured data
- breadcrumbs
- sitemap
- robots.txt

لا تسمح لـ filter query combinations بإنشاء SEO duplication بلا تحكم.

---

## 25. Performance

- Pagination: 24 منتج افتراضيًا.
- لا تحمل مئات المنتجات دفعة واحدة.
- Lazy loading.
- Responsive images.
- WebP/AVIF عندما يكون عمليًا.
- Eager loading.
- Database indexes.
- Caching عندما يفيد.
- تجنب N+1.

---

## 26. Google API Security

- ضع المفاتيح في `.env`.
- لا Commit للمفاتيح.
- استخدم config مناسب.
- Browser key يجب أن يكون مقيدًا بـ HTTP Referrer Restrictions.
- اسمح فقط بالـ APIs المطلوبة.
- Server-side credentials لا ترسل للBrowser.

عند فشل حساب المسافة:
- لا تخمن السعر.
- لا تجعل التوصيل مجانيًا.
- لا تنشئ الطلب.
- اعرض Retry ورسالة واضحة.

---

## 27. الاختبارات المطلوبة

اختبر على الأقل:

- Authentication
- Admin authorization
- Product listing
- Filters
- Search
- Cart add/remove
- Stock restrictions
- Coupon validation
- Checkout
- Delivery tier selection
- Boundary distances
- Outside delivery range
- Prevent overlapping tiers
- Delivery fee server-side validation
- Order totals
- Stock reduction
- Overselling prevention
- Order cancellation
- Return stock logic
- Order snapshot
- Admin product management
- Permissions

وفي النهاية:

```bash
php artisan optimize:clear
php artisan route:cache
php artisan config:cache
php artisan view:cache
php artisan test
```

يجب أن تنجح جميعها قبل إعلان اكتمال المشروع.

---

## 28. معيار الإنجاز

لا تقل أن المشروع مكتمل قبل اختبار التدفق التالي:

Homepage
→ Products
→ Filters
→ Product Page
→ Add To Cart
→ Cart
→ Checkout
→ تحديد الموقع على Google Maps
→ حساب المسافة
→ حساب رسوم التوصيل
→ تأكيد الطلب
→ Admin يرى الطلب
→ تغيير الحالة
→ التأكد من المخزون
→ التأكد من Snapshot

واختبر:
- 375px
- 430px
- 768px
- 1024px
- 1440px+
- RTL
- Empty states
- 404
- Broken images
- Route cache
- Permissions
- Database integrity

---

## 29. قاعدة أخيرة

لا أريد مشروعًا يبدو كـ:
"Laravel admin + products".

أريد:
"Premium skincare boutique بواجهة احترافية ونظام تجارة إلكترونية موثوق خلفها."

Storefront:
Soft + Elegant + Premium + Cute + Modern.

Admin:
Clean + Fast + Practical + Professional.

وعند الشك اختر:
**الوضوح + المساحات البيضاء + جودة الصور + RTL + Mobile UX**
بدل الزخرفة والتعقيد.
