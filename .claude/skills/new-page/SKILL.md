---
name: new-page
description: إنشاء صفحة عامة جديدة مع التسجيل في router.php و ajax-nav.js والقائمة. استخدم هذه المهارة عند إضافة صفحة جديدة للموقع العام.
user-invocable: true
allowed-tools: Read, Edit, Write, Glob, Grep
argument-hint: "[page-name] [عنوان-عربي]"
---

# مهارة: إنشاء صفحة عامة جديدة

أنشئ صفحة عامة جديدة في مشروع مجلس العوامي. هذه المهارة تحل مشكلة **التسجيل المزدوج** حيث يجب تسجيل كل صفحة في مكانين (`router.php` + `ajax-nav.js`).

## المدخلات المطلوبة

استخرج من `$ARGUMENTS`:
- **اسم الصفحة** (مثل: `about`, `members-list`) — يُستخدم كاسم الملف والمسار
- **العنوان بالعربي** (مثل: `عن المجلس`, `قائمة الأعضاء`)

إذا لم يُحدد المستخدم، اسأله عن: الاسم، العنوان، ووصف مختصر للمحتوى.

## الخطوات الأربع (كلها مطلوبة)

### الخطوة 1: إنشاء `pages/{page-name}.php`

اقرأ أولاً `pages/news.php` كمرجع للنمط.

أنشئ الملف بالهيكل التالي:

```php
<?php
/**
 * {page-name}.php — {وصف الصفحة}
 */
$ws = getWS();
?>

<section>
  <div class="section-header">
    <div class="section-badge">{badge بالعربي}</div>
    <h2 class="section-title">{العنوان}</h2>
    <p class="section-subtitle">{وصف مختصر بالعربي}</p>
  </div>

  <div style="max-width:1000px;margin:0 auto">
    <!-- محتوى الصفحة -->
  </div>
</section>
```

### قواعد مهمة:
- كل النصوص بالعربي
- استخدم CSS classes من `components.css` (`section-header`, `section-title`, `section-badge`, `animate-in`)
- استخدم CSS custom properties للألوان (`var(--green)`, `var(--text)`, etc.)
- دعم Dark mode تلقائي (لا تستخدم ألوان مباشرة)
- `$ws = getWS();` إذا تحتاج إعدادات الموقع

### الخطوة 2: إضافة Route في `router.php` (مهم جداً)

اقرأ `router.php` وأضف entry جديد في مصفوفة `$routes` (بعد آخر route موجود):

```php
'/{page-name}' => ['file' => 'pages/{page-name}.php', 'page' => '{page-name}', 'title' => '{العنوان بالعربي}', 'scripts' => ['/public/js/{page-name}.js']],
```

- إذا الصفحة لا تحتاج JS، اترك `'scripts' => []`
- إذا الصفحة مستقلة (HTML كامل بدون layout)، أضف `'standalone' => true`

### الخطوة 3: إضافة entry في `ajax-nav.js` (الخطوة اللي تنتسى دايماً!)

اقرأ `public/js/ajax-nav.js` وأضف entry في كائن `pageScripts` (سطر 6-14):

```javascript
'/{page-name}': ['/public/js/{page-name}.js'],
```

**تنبيه حاسم**: هذه الخطوة **لازم** تتطابق مع scripts في `router.php`. إذا نسيتها:
- الصفحة تعمل عند الدخول المباشر (direct URL)
- لكن **تنكسر** عند النقر على رابط من صفحة أخرى (AJAX navigation)

### الخطوة 4: إضافة رابط في `includes/header.php` (اختياري)

اقرأ `includes/header.php` وأضف رابط في `<nav id="mainNav">`:

```php
<a href="/{page-name}"<?= ($currentPage ?? '') === '{page-name}' ? ' class="active"' : '' ?>>{العنوان}</a>
```

- إذا الصفحة مستقلة (standalone)، أضف `data-no-ajax` للرابط
- ضع الرابط في المكان المنطقي بين الروابط الموجودة

### الخطوة 5: إنشاء JS (إذا مطلوب)

إذا الصفحة تحتاج JavaScript، أنشئ `public/js/{page-name}.js`:

```javascript
/**
 * {page-name}.js — {وصف}
 */
(function() {
  'use strict';
  // كود الصفحة هنا
})();
```

## تحقق نهائي

بعد الانتهاء تأكد من:
- [ ] الملف `pages/{page-name}.php` موجود ومحتواه بالعربي
- [ ] Route مضاف في `router.php` مع العنوان والسكريبتات
- [ ] Entry مضاف في `ajax-nav.js` بنفس السكريبتات **بالضبط**
- [ ] رابط مضاف في `header.php` (إذا مطلوب)
- [ ] ملف JS مضاف في `public/js/` (إذا مطلوب)
- [ ] اختبار: الصفحة تعمل بـ direct URL **و** بالنقر من صفحة أخرى
