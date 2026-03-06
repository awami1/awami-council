# دليل: إضافة ميزة جديدة

## إضافة صفحة عامة جديدة

الأسرع: استخدم مهارة `/new-page` في Claude Code.

### يدوياً:

1. أنشئ `pages/{page}.php` بقالب section-header
2. أضف route في `router.php` → مصفوفة `$routes`
3. أضف entry في `public/js/ajax-nav.js` → كائن `pageScripts` **(لا تنسى!)**
4. أضف رابط في `includes/header.php` → `<nav id="mainNav">`
5. أنشئ `public/js/{page}.js` إذا تحتاج JS

### اختبار:
- دخول مباشر: `http://localhost:8080/{page}`
- نقر من صفحة أخرى (AJAX navigation)
- زر الرجوع في المتصفح
- الوضع الداكن

---

## إضافة API endpoint جديد

الأسرع: استخدم مهارة `/new-api-endpoint` في Claude Code.

### يدوياً:

1. أنشئ `api/{resource}.php` بالقالب الموحد:
   - `declare(strict_types=1)`
   - requires: config, auth_guard, audit_helper, validation
   - `ensureTable()` مع SQL مزدوج
   - الدوال الخمس + match router
2. أضف API client في `public/js/api.js`
3. أضف SQL في `api/setup.php`

### اختبار:
```bash
# GET all
curl http://localhost:8080/api/{resource}.php

# POST (يحتاج auth)
curl -X POST http://localhost:8080/api/{resource}.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=..." \
  -H "X-CSRF-Token: ..." \
  -d '{"field": "value"}'
```

---

## إضافة قسم في لوحة التحكم

1. أضف HTML القسم في `admin/index.php`
2. أضف المنطق في `admin/js/admin-app.js`
3. أضف رابط في sidebar الأدمن
4. تأكد من استخدام `apiFetch` (يضيف CSRF تلقائياً)

---

## إضافة نمط CSS جديد

1. أضف المتغير في `public/css/variables.css`:
   - في `:root` (الوضع الفاتح)
   - في `[data-theme="dark"]` (الوضع الداكن)
2. استخدم `var(--variable)` في الملف المناسب
3. اختبر في الوضعين الفاتح والداكن
