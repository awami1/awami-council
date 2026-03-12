---
paths:
  - "public/**/*"
  - "pages/**/*.php"
  - "includes/**/*.php"
---

# Frontend Rules

## JavaScript

- **Vanilla JS فقط** — لا frameworks
- كل API calls عبر الـ API clients في `public/js/api.js` (مثل `MembersAPI.getAll()`) — **ممنوع** `fetch()` مباشر
- استخدم `const`/`let` في كل الملفات — **ماعدا** `ajax-nav.js` اللي يستخدم `var` (IIFE)
- **ممنوع** `innerHTML` من مدخلات المستخدم بدون escaping

## CSS

- استخدم CSS custom properties فقط: `var(--green)`, `var(--text)`, `var(--radius)` — **ممنوع** ألوان مباشرة
- كل لون جديد لازم يتعرف في `:root` **و** `[data-theme="dark"]` في `public/css/variables.css`
- استخدم `margin-inline-start/end` بدل `left`/`right` للتوافق مع RTL
- الألوان الأساسية: emerald `#1A5C32`، navy `#1B3456`، gold `#c8a84b`
- الخطوط: Cairo (body)، Amiri (headings)، Saudi (display)

## Page Templates

- كل النصوص بالعربي
- استخدم CSS classes من `components.css`: `section-header`, `section-title`, `section-badge`, `animate-in`
- `$ws = getWS();` لجلب إعدادات الموقع

## تسجيل مزدوج (CRITICAL)

عند إضافة صفحة جديدة، سكريبتات الصفحة لازم تتسجل في **مكانين**:
1. مصفوفة `scripts` في `router.php`
2. كائن `pageScripts` في `public/js/ajax-nav.js`

نسيان أي منهم يكسر الصفحة:
- بدون router.php → تنكسر عند الدخول المباشر (direct URL)
- بدون ajax-nav.js → تنكسر عند النقر من صفحة أخرى (AJAX navigation)

> **استخدم `/new-page` skill** عشان يسجل تلقائياً في المكانين.
