# CODEX_PROMPT.md — البرومبت الذي تبدأ به مع Codex

أريدك أن تبني مشروع **Glowy Cosmetics** كتطبيق Laravel Production-ready.

قبل أي تنفيذ، اقرأ الملفات التالية الموجودة في Root المشروع بالكامل واعتبرها مصدر الحقيقة:

1. `AGENTS.md`
2. `SCOPE.md`
3. `ARCHITECTURE.md`
4. `UI_UX_GUIDE.md`
5. `IMPLEMENTATION_PLAN.md`

## المطلوب منك

1. افحص المشروع الحالي بالكامل قبل التعديل.
2. اعرض لي ملخصًا قصيرًا جدًا عن:
   - حالة المشروع الحالية
   - Stack الموجود
   - أي تعارض مع الملفات أعلاه
   - أول Phase ستبدأ بها
3. بعد ذلك ابدأ التنفيذ الفعلي حسب `IMPLEMENTATION_PLAN.md`.
4. لا تتوقف عند إعطائي خطة فقط.
5. لا تتجاوز Scope.
6. لا تضف Payment Gateway.
7. الدفع Cash on Delivery فقط.
8. التوصيل يعتمد على Google Maps + Driving Route Distance + Distance Tiers.
9. لا تستخدم Filament.
10. حافظ على RTL والهوية البصرية لـ Glowy.
11. بعد كل Phase شغل الاختبارات وأصلح المشاكل قبل الانتقال للي بعدها.
12. لا تقل إن المشروع مكتمل قبل نجاح Final Verification بالكامل.

## قاعدة مهمة

إذا وجدت قرارًا غير واضح:

- ارجع أولًا إلى `AGENTS.md`.
- ثم `SCOPE.md`.
- ثم `ARCHITECTURE.md`.
- لا تخترع Feature جديدة من عندك.
- إذا كان القرار يؤثر على Scope أو Architecture بشكل كبير، توقف واسألني قبل تنفيذه.

ابدأ الآن بفحص المشروع، ثم نفذ Phase 0 وPhase 1.
