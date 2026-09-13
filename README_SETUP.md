# Glowy Codex Pack

ضع هذه الملفات في **Root** مشروع Laravel:

```text
your-project/
├── AGENTS.md
├── SCOPE.md
├── ARCHITECTURE.md
├── UI_UX_GUIDE.md
├── IMPLEMENTATION_PLAN.md
├── CODEX_PROMPT.md
├── app/
├── routes/
├── database/
└── ...
```

ثم افتح Codex داخل المشروع وقل له:

> اقرأ `CODEX_PROMPT.md` ونفّذ التعليمات الموجودة فيه.

## لماذا فصلنا الملفات؟

- `AGENTS.md`: قواعد ثابتة يتبعها Codex طوال المشروع.
- `SCOPE.md`: ما يدخل وما لا يدخل في V1.
- `ARCHITECTURE.md`: القرارات التقنية الأساسية.
- `UI_UX_GUIDE.md`: هوية وتصميم Glowy.
- `IMPLEMENTATION_PLAN.md`: ترتيب المراحل.
- `CODEX_PROMPT.md`: أمر البداية.

بهذا لا تحتاج إلى لصق Prompt ضخم في كل Session، وتقل احتمالية أن ينسى Codex قواعد المشروع أو يضيف Features غير مطلوبة.

## Google Maps لاحقًا

عند الوصول لمرحلة التوصيل ستحتاج إعداد Google Cloud APIs المناسبة ومفاتيح `.env`.

لا تضع أي API key حقيقية داخل هذه الملفات أو Git.
