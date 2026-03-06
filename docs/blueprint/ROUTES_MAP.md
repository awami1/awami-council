# ROUTES_MAP.md — خريطة المسارات
# مجلس عائلة العوامي (Awami Council)

---

## 1. مسارات الموقع العام (Public Routes)

جميع المسارات تُعرَّف في `router.php` ضمن مصفوفة `$routes`.

| المسار | الصفحة | العنوان | Scripts | الوصف |
|--------|--------|---------|---------|-------|
| `/` | `pages/home.php` | (الافتراضي) | `countdown.js` | الصفحة الرئيسية — Hero + عد تنازلي + فعاليات + أخبار + عن المجلس + قيم |
| `/council` | `pages/council.php` | المجلس | `countdown.js` | صفحة المجلس — عد تنازلي + مناصب + لجان |
| `/tree` | `pages/tree.php` | شجرة العائلة | `tree.js` | شجرة العائلة التفاعلية (D3.js) — يُحمَّل `d3.v7.min.js` من CDN |
| `/news` | `pages/news.php` | الأخبار | `news.js` | قائمة الأخبار — فلترة بالفئات + Pagination |
| `/events` | `pages/events.php` | الفعاليات | (لا يوجد) | قائمة الفعاليات — فلترة بالحالة |
| `/gallery` | `pages/gallery.php` | المعرض | `media.js` | معرض الصور والفيديوهات — فلترة + Lightbox |
| `/contact` | `pages/contact.php` | تواصل معنا | (لا يوجد) | نموذج تواصل + FAQ accordion |
| `/eid` | `pages/eid.php` | تهنئة العيد | `eid.js` | منشئ بطاقة تهنئة العيد (Canvas) |
| `/riwaq` | `pages/riwaq.php` | الرِّوَاق | (لا يوجد) | صفحة مستقلة (standalone — بدون Layout) |

### صفحات خاصة
| المسار | الصفحة | الوصف |
|--------|--------|-------|
| (أي مسار غير معرف) | `pages/404.php` | صفحة غير موجودة (404) |
| `/sitemap.xml` | `sitemap.php` | خريطة الموقع XML |

---

## 2. مسارات AJAX Navigation

عند التصفح عبر AJAX (النقر على رابط داخلي)، يُرسل الطلب مع:
- Header: `X-Requested-With: XMLHttpRequest`
- أو: Query parameter `?_ajax=1`

**الاستجابة**: محتوى الصفحة فقط (بدون Layout/Header/Footer)

### خريطة pageScripts في `ajax-nav.js`

```javascript
pageScripts = {
  '/':        ['/public/js/countdown.js'],
  '/council': ['/public/js/countdown.js'],
  '/tree':    ['https://d3js.org/d3.v7.min.js', '/public/js/tree.js'],
  '/gallery': ['/public/js/media.js'],
  '/news':    ['/public/js/news.js'],
  '/events':  [],
  '/eid':     ['/public/js/eid.js'],
  '/contact': []
}
```

**مهم**: عند إضافة صفحة جديدة، يجب تسجيل Scripts في **مكانين**:
1. مصفوفة `scripts` في `router.php` (للتحميل المباشر)
2. كائن `pageScripts` في `ajax-nav.js` (للتصفح AJAX)

---

## 3. لوحة التحكم (Admin Routes)

| المسار | الملف | الوصف |
|--------|-------|-------|
| `/admin` | `admin/index.php` | لوحة التحكم الكاملة (SPA مستقلة) |
| `/admin/` | `admin/index.php` | (نفس الأعلى) |

### أقسام لوحة التحكم الداخلية (SPA Navigation)

لوحة التحكم هي SPA مستقلة — التنقل بين الأقسام يتم عبر JavaScript (`showPage()`):

| القسم | الاسم الداخلي | الوصف |
|-------|---------------|-------|
| لوحة التحكم | `dashboard` | إحصائيات عامة + نشاط أخير |
| المجلس | `council` | مناصب المجلس |
| الأعضاء | `members` | إدارة الأعضاء CRUD |
| الرسوم | `fees` | مدفوعات الأعضاء |
| التذكيرات | `reminders` | تذكيرات الدفع |
| اللجان | `committees` | إدارة اللجان |
| الميزانية | `budget` | المعاملات المالية |
| الفعاليات | `events` | إدارة الفعاليات |
| التقويم | `calendar` | عرض تقويمي |
| شجرة العائلة | `family-tree` | إدارة الشجرة |
| التصويت | `voting` | إدارة الاستطلاعات |
| التقارير الذكية | `smart-reports` | تحليل مالي ذكي |
| سجل التدقيق | `audit` | عرض سجل العمليات |
| التصدير | `export` | تصدير CSV |
| إعدادات الموقع | `website-settings` | تخصيص الموقع |

---

## 4. مسارات API

جميع الـ APIs تقع تحت `/api/` وتتبع نمط REST:

### 4.1 المصادقة

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/auth.php?action=check` | لا | تحقق حالة الجلسة |
| POST | `/api/auth.php?action=login` | لا | تسجيل الدخول |
| POST | `/api/auth.php?action=logout` | نعم | تسجيل الخروج |

### 4.2 الأعضاء

| Method | المسار | Auth | CSRF | الوصف |
|--------|--------|------|------|-------|
| GET | `/api/members.php` | نعم | نعم | جميع الأعضاء |
| GET | `/api/members.php?id=X` | نعم | نعم | عضو واحد |
| POST | `/api/members.php` | نعم | نعم | إضافة عضو |
| PUT | `/api/members.php?id=X` | نعم | نعم | تعديل عضو |
| DELETE | `/api/members.php?id=X` | نعم | نعم | حذف عضو |
| POST | `/api/members.php?action=add_committee` | نعم | نعم | إضافة عضو للجنة |
| POST | `/api/members.php?action=remove_committee` | نعم | نعم | إزالة عضو من لجنة |

### 4.3 المدفوعات

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/payments.php` | نعم | جميع المدفوعات (?member_id, ?period_id, ?status) |
| GET | `/api/payments.php?id=X` | نعم | دفعة واحدة |
| POST | `/api/payments.php` | نعم | إضافة دفعة |
| POST | `/api/payments.php?upsert=1` | نعم | إضافة/تحديث بالعضو+الفترة |
| PUT | `/api/payments.php?id=X` | نعم | تعديل دفعة |
| DELETE | `/api/payments.php?id=X` | نعم | حذف دفعة |
| DELETE | `/api/payments.php?member_id=X&period_id=Y` | نعم | حذف بالعضو+الفترة |

### 4.4 المعاملات المالية

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/transactions.php` | نعم | جميع المعاملات (?type, ?date_from, ?date_to, ?committee_id) |
| GET | `/api/transactions.php?id=X` | نعم | معاملة واحدة |
| POST | `/api/transactions.php` | نعم | إضافة معاملة |
| POST | `/api/transactions.php?bulk=1` | نعم | إضافة بالجملة |
| PUT | `/api/transactions.php?id=X` | نعم | تعديل معاملة |
| DELETE | `/api/transactions.php?id=X` | نعم | حذف معاملة |

### 4.5 الفترات المالية

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/periods.php` | نعم | جميع الفترات |
| POST | `/api/periods.php` | نعم | إنشاء فترة |
| DELETE | `/api/periods.php?id=X` | نعم | حذف فترة |

### 4.6 الفعاليات

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/events.php` | نعم | جميع الفعاليات (?status, ?committee_id, ?date_from, ?date_to) |
| GET | `/api/events.php?id=X` | نعم | فعالية واحدة |
| POST | `/api/events.php` | نعم | إضافة فعالية |
| PUT | `/api/events.php?id=X` | نعم | تعديل فعالية |
| DELETE | `/api/events.php?id=X` | نعم | حذف فعالية |

### 4.7 الفعاليات العامة

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/public_events.php` | لا | الفعاليات العامة |

### 4.8 اللجان

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/committees.php` | نعم | جميع اللجان |
| GET | `/api/committees.php?id=X` | نعم | لجنة واحدة |
| POST | `/api/committees.php` | نعم | إنشاء لجنة |
| PUT | `/api/committees.php?id=X` | نعم | تعديل لجنة |
| DELETE | `/api/committees.php?id=X` | نعم | حذف لجنة |

### 4.9 التصويت

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/polls.php` | نعم | جميع الاستطلاعات (?active, ?committee_id) |
| GET | `/api/polls.php?id=X` | نعم | استطلاع واحد |
| POST | `/api/polls.php` | نعم | إنشاء استطلاع |
| POST | `/api/polls.php?action=vote` | نعم | التصويت |
| PUT | `/api/polls.php?action=close&id=X` | نعم | إغلاق استطلاع |
| DELETE | `/api/polls.php?id=X` | نعم | حذف استطلاع |

### 4.10 شجرة العائلة

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/family-tree.php` | **لا** | جميع أفراد الشجرة |
| POST | `/api/family-tree.php` | نعم | إضافة فرد |
| PUT | `/api/family-tree.php?id=X` | نعم | تعديل فرد |
| DELETE | `/api/family-tree.php?id=X` | نعم | حذف فرد |

### 4.11 فروع العائلة

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/branches.php` | نعم | جميع الفروع |
| POST | `/api/branches.php` | نعم | إضافة/تعديل فرع |
| DELETE | `/api/branches.php?id=X` | نعم | حذف فرع |

### 4.12 الأخبار

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/news.php` | **لا*** | الأخبار (?category, ?status, ?search) |
| GET | `/api/news.php?id=X` | **لا*** | خبر واحد |
| POST | `/api/news.php` | نعم | إضافة خبر |
| PUT | `/api/news.php?id=X` | نعم | تعديل خبر |
| DELETE | `/api/news.php?id=X` | نعم | حذف خبر |

> *GET عام يعرض المنشور فقط. المشرف يرى الكل.

### 4.13 المعرض

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/media.php` | نعم | جميع العناصر |
| POST | `/api/media.php` | نعم | إضافة عنصر |
| PUT | `/api/media.php?id=X` | نعم | تعديل عنصر |
| DELETE | `/api/media.php?id=X` | نعم | حذف عنصر |

### 4.14 الرسائل

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/messages.php` | نعم | جميع الرسائل (?is_read) |
| POST | `/api/messages.php` | **لا** | إرسال رسالة (Rate Limited) |
| PUT | `/api/messages.php?id=X` | نعم | تحديد كمقروءة |
| DELETE | `/api/messages.php?id=X` | نعم | حذف رسالة |

### 4.15 إعدادات الموقع

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/settings.php` | نعم | جلب الإعدادات |
| POST | `/api/settings.php` | نعم | حفظ الإعدادات (كاملة أو قسم) |

### 4.16 الاجتماع القادم

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/meeting.php` | **لا** | بيانات الاجتماع |
| POST | `/api/meeting.php` | نعم | تحديث الاجتماع |
| DELETE | `/api/meeting.php` | نعم | حذف الاجتماع |

### 4.17 التذكيرات

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/reminders.php?action=unpaid` | نعم | الأعضاء غير المسددين |
| GET | `/api/reminders.php?action=history` | نعم | سجل التذكيرات |
| POST | `/api/reminders.php?action=log_single` | نعم | تسجيل تذكير فردي |
| POST | `/api/reminders.php?action=log_bulk` | نعم | تسجيل تذكيرات بالجملة |

### 4.18 سجل التدقيق

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/audit.php` | نعم | سجل التدقيق (?entity_type, ?action, ?limit) |

### 4.19 التصدير

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/export.php?type=members` | نعم | تصدير أعضاء CSV |
| GET | `/api/export.php?type=payments` | نعم | تصدير مدفوعات CSV |
| GET | `/api/export.php?type=transactions` | نعم | تصدير معاملات CSV |

### 4.20 التقارير المحفوظة

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/reports.php` | نعم | جميع التقارير |
| GET | `/api/reports.php?id=X` | نعم | تقرير واحد |
| POST | `/api/reports.php` | نعم | حفظ تقرير |
| PUT | `/api/reports.php?id=X` | نعم | تحديث تقرير |
| PUT | `/api/reports.php?id=X&action=archive` | نعم | أرشفة |
| PUT | `/api/reports.php?id=X&action=restore` | نعم | استعادة |
| DELETE | `/api/reports.php?id=X` | نعم | حذف |

### 4.21 القصص

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/stories.php` | متنوع | جميع القصص |
| POST | `/api/stories.php` | نعم | إنشاء قصة |
| PUT | `/api/stories.php?id=X` | نعم | تعديل قصة |
| DELETE | `/api/stories.php?id=X` | نعم | حذف قصة |

### 4.22 قصص المعرض

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/gallery-stories.php` | متنوع | جميع قصص المعرض |
| POST | `/api/gallery-stories.php` | نعم | إنشاء |
| PUT | `/api/gallery-stories.php?id=X` | نعم | تعديل |
| DELETE | `/api/gallery-stories.php?id=X` | نعم | حذف |

### 4.23 إنشاء الجداول

| Method | المسار | Auth | الوصف |
|--------|--------|------|-------|
| GET | `/api/setup.php` | نعم | إنشاء جميع جداول DB |

---

## 5. Sitemap Priorities

```
/          → priority: 1.0,  changefreq: daily
/news      → priority: 0.9,  changefreq: daily
/council   → priority: 0.8,  changefreq: monthly
/events    → priority: 0.8,  changefreq: weekly
/tree      → priority: 0.7,  changefreq: monthly
/gallery   → priority: 0.7,  changefreq: weekly
/contact   → priority: 0.6,  changefreq: monthly
/eid       → priority: 0.5,  changefreq: yearly
```
