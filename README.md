# Glowy Cosmetics

متجر Laravel عربي RTL للعناية بالبشرة والجمال. التقنية الأساسية: Laravel 13، PHP 8.4، MySQL 8، Blade، Livewire 4، Tailwind 4، وVite.

## التشغيل المحلي

1. انسخ `.env.example` إلى `.env` وأنشئ مفتاح التطبيق:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

2. أنشئ قاعدة MySQL واضبط `DB_CONNECTION=mysql` وبياناتها في `.env`.

3. لتوليد مدير محلي اختياري، أضف إلى `.env` قيمًا قوية وآمنة:

   ```dotenv
   GLOWY_ADMIN_NAME="Glowy Admin"
   GLOWY_ADMIN_EMAIL=admin@example.test
   GLOWY_ADMIN_PASSWORD=change-this-to-a-strong-password
   ```

   اترك البريد وكلمة المرور فارغين إذا لم ترد إنشاء مدير تلقائيًا.

4. ثبّت الاعتمادات وشغّل الترحيلات والبناء:

   ```powershell
   composer install
   npm install
   php artisan migrate --seed
   npm run build
   ```

## التحقق

```powershell
php artisan optimize:clear
vendor/bin/pint --test
php artisan test
php artisan route:cache
php artisan config:cache
php artisan view:cache
```

الخريطة تستخدم Leaflet وOpenStreetMap بلا مفتاح متصفح. أضف `OPENROUTESERVICE_API_KEY` فقط في `.env` لحساب مسافة القيادة من الخادم، واحفظه خارج Git. البحث المجاني عن المواقع عبر Nominatim محدود ومقصود للبحث اليدوي، وليس الإكمال التلقائي عند كل ضغطة.

## الإدارة والإطلاق

- محتوى الصفحة الرئيسية وبيانات التواصل وSEO: `/admin/content`
- تقارير التشغيل وتصدير CSV: `/admin/reports`
- خريطة الموقع: `/sitemap.xml`
- تعليمات محركات البحث: `/robots.txt`

صور المنتجات وصورة Hero الجديدة تُحوّل إلى WebP عند الرفع. تأكد في الإنتاج من تفعيل امتداد GD وربط `storage/app/public` عبر `php artisan storage:link`.
