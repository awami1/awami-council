---
paths:
  - "admin/**/*"
---

# Admin Panel Rules

## البنية الحالية

- `admin/index.php` — ملف واحد (~1036 سطر PHP+HTML) يعمل كـ SPA
- `admin/js/admin-app.js` — المنطق الرئيسي (~2350 سطر، global scope)
- `admin/js/admin-core.js` — أدوات مشتركة
- `admin/pages/modals.php` — قوالب النوافذ المنبثقة
- الأدمن له `<head>` خاص به (لا يستخدم `includes/head.php`)

## CSRF

- `admin/index.php` يضمّن `api.js` عبر `readfile()` ويحقن CSRF token
- `apiFetch` في الأدمن يضيف `X-CSRF-Token` تلقائياً ويتعامل مع 401 redirects

## عند إضافة ميزة جديدة

- أضف HTML القسم في `admin/index.php`
- أضف المنطق في `admin/js/admin-app.js`
- أضف رابط في sidebar الأدمن
- **يُفضل** فصل أقسام JS الكبيرة في ملفات منفصلة (مثل `admin/js/admin-finance.js`) لتجنب تضخم `admin-app.js`

## تحذيرات

- لا يوجد CSP حالياً في لوحة الأدمن
- كل الدوال في global scope — احذر من تعارض أسماء الدوال
- مكتبة XLSX تُحمّل من CDN (`cdnjs.cloudflare.com`)
