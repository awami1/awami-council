# API_SPEC.md — مواصفات واجهة برمجة التطبيقات
# مجلس عائلة العوامي (Awami Council)

---

## 1. معلومات عامة

- **Base URL**: `/api/`
- **تنسيق الاستجابة**: JSON (UTF-8, Unicode غير مُرمَّز)
- **المصادقة**: Session-based (Cookie: `awami_session`)
- **CSRF**: Header `X-CSRF-Token` مطلوب لـ POST/PUT/DELETE
- **حد حجم الطلب**: 64KB (يُرفض مع 413 إذا تُجاوز)
- **معرفات الكيانات**: UUID v4 (VARCHAR(36))
- **CORS**: Same-origin فقط (Access-Control-Allow-Origin: الدومين نفسه)

### أنماط الاستجابة

**نجاح (قائمة)**:
```json
{ "data": [...], "total": 10 }
```

**نجاح (كيان واحد)**:
```json
{ "data": { "id": "xxx", ... } }
```

**نجاح (عملية)**:
```json
{ "ok": true }
// أو
{ "message": "تم الحذف بنجاح." }
```

**خطأ**:
```json
{ "error": "وصف الخطأ" }
```

### رموز الحالة المستخدمة

| الرمز | الاستخدام |
|-------|----------|
| 200 | نجاح |
| 201 | تم الإنشاء |
| 204 | CORS Preflight |
| 400 | طلب غير صالح |
| 401 | غير مصادق |
| 403 | CSRF غير صالح |
| 404 | غير موجود |
| 405 | Method غير مسموح |
| 409 | تعارض (مكرر) |
| 413 | حجم الطلب كبير |
| 422 | بيانات غير صالحة |
| 429 | تجاوز الحد المسموح |
| 500 | خطأ داخلي |
| 503 | الخدمة غير مهيأة |

---

## 2. المصادقة (`auth.php`)

### 2.1 فحص حالة الجلسة

```
GET /api/auth.php?action=check
Auth: لا
```

**استجابة**:
```json
{ "authenticated": true }
```

### 2.2 تسجيل الدخول

```
POST /api/auth.php?action=login
Auth: لا
```

**الطلب**:
```json
{
  "username": "admin",
  "password": "كلمة_المرور"
}
```

**قيود**:
- `username`: حد أقصى 100 حرف
- `password`: حد أقصى 200 حرف
- Rate Limit: 5 محاولات / 15 دقيقة لكل IP

**استجابة النجاح** (200):
```json
{
  "success": true,
  "csrf_token": "hex_string_64_chars"
}
```

**استجابة الفشل** (401):
```json
{ "error": "اسم المستخدم أو كلمة المرور غير صحيحة" }
```

**تجاوز الحد** (429):
```json
{ "error": "تم تجاوز الحد الأقصى لمحاولات الدخول. حاول بعد X ثانية" }
```

### 2.3 تسجيل الخروج

```
POST /api/auth.php?action=logout
Auth: نعم
```

**استجابة**: `{ "success": true }`

---

## 3. الأعضاء (`members.php`)

**المصادقة**: مطلوبة لجميع العمليات
**CSRF**: مطلوب

### 3.1 جلب جميع الأعضاء

```
GET /api/members.php
```

**استجابة**:
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "أحمد العوامي",
      "family": "العوامي",
      "phone": "0501234567",
      "id_num": "1234567890",
      "join_date": "2020-01-15",
      "status": "نشط",
      "notes": "",
      "branch_id": null,
      "created_at": "2024-01-01 00:00:00",
      "updated_at": "2024-01-01 00:00:00"
    }
  ],
  "total": 1
}
```

### 3.2 إنشاء عضو

```
POST /api/members.php
```

**الطلب**:
```json
{
  "name": "أحمد العوامي",
  "family": "العوامي",
  "phone": "0501234567",
  "id_num": "1234567890",
  "join_date": "2024-01-15",
  "status": "نشط",
  "notes": "",
  "branch_id": null
}
```

**الحقول المطلوبة**: لا يوجد (الاسم مطلوب ضمنياً)
**القيم المقبولة لـ status**: `نشط`, `معفي`, `غير نشط`

### 3.3 تعديل عضو

```
PUT /api/members.php?id=uuid
```

**الطلب**: نفس حقول الإنشاء (فقط الحقول المراد تعديلها)

**الحقول المسموحة**: `name`, `family`, `phone`, `id_num`, `join_date`, `status`, `notes`, `branch_id`

### 3.4 حذف عضو

```
DELETE /api/members.php?id=uuid
```

### 3.5 إضافة عضو للجنة

```
POST /api/members.php?action=add_committee
Body: { "committeeId": "c1", "memberId": "uuid" }
```

### 3.6 إزالة عضو من لجنة

```
POST /api/members.php?action=remove_committee
Body: { "committeeId": "c1", "memberId": "uuid" }
```

---

## 4. المدفوعات (`payments.php`)

### 4.1 جلب المدفوعات

```
GET /api/payments.php
GET /api/payments.php?member_id=X
GET /api/payments.php?period_id=Y
GET /api/payments.php?status=مدفوع
```

### 4.2 إنشاء دفعة

```
POST /api/payments.php
```

```json
{
  "member_id": "uuid",
  "period_id": "uuid",
  "amount": 200.00,
  "required": 200.00,
  "pay_date": "2024-03-01",
  "method": "تحويل بنكي",
  "status": "مدفوع",
  "notes": ""
}
```

**القيم المقبولة لـ status**: `مدفوع`, `لم يدفع`, `معفي`

### 4.3 Upsert (إنشاء أو تحديث)

```
POST /api/payments.php?upsert=1
```

نفس الـ Body — يُنشئ إذا لم يوجد، يُحدِّث إذا وُجد (بـ member_id + period_id).

### 4.4 حذف بالمفتاح المركب

```
DELETE /api/payments.php?member_id=X&period_id=Y
```

---

## 5. المعاملات المالية (`transactions.php`)

### 5.1 جلب المعاملات

```
GET /api/transactions.php
GET /api/transactions.php?type=إيراد
GET /api/transactions.php?date_from=2024-01-01&date_to=2024-12-31
GET /api/transactions.php?committee_id=c1
```

**استجابة** (شكل State المُحوَّل):
```json
{
  "data": [
    {
      "id": "uuid",
      "type": "إيراد",
      "amount": 200.0,
      "category": "اشتراكات",
      "committee": "",
      "desc": "اشتراك أحمد — فترة 2024",
      "date": "2024-03-01",
      "memberId": "uuid",
      "periodId": "uuid",
      "created_at": "..."
    }
  ]
}
```

### 5.2 إنشاء معاملة

```
POST /api/transactions.php
```

```json
{
  "type": "مصروف",
  "amount": 500.00,
  "category": "فعاليات",
  "description": "تكاليف رحلة العمرة",
  "tx_date": "2024-03-15",
  "committee_id": "c1"
}
```

**القيم المقبولة لـ type**: `إيراد`, `مصروف`

**ملاحظة**: يقبل أسماء حقول بديلة: `desc` بدل `description`, `date` بدل `tx_date`, `committee` بدل `committee_id`

### 5.3 إدخال بالجملة

```
POST /api/transactions.php?bulk=1
```

```json
{
  "transactions": [
    { "type": "إيراد", "amount": 200, "description": "...", "tx_date": "2024-01-01" },
    { "type": "مصروف", "amount": 150, "description": "...", "tx_date": "2024-01-02" }
  ]
}
```

**استجابة**:
```json
{
  "inserted": 2,
  "duplicates_skipped": 0,
  "data": [...]
}
```

**كشف التكرار**: description + amount + tx_date (فارق < 0.01)

---

## 6. الفترات المالية (`periods.php`)

### 6.1 جلب الفترات

```
GET /api/periods.php
```

### 6.2 إنشاء فترة

```
POST /api/periods.php
Body: { "name": "اشتراك 2024", "fee_amount": 200, "start_date": "2024-01-01", "end_date": "2024-12-31" }
```

### 6.3 حذف فترة

```
DELETE /api/periods.php?id=uuid
```

---

## 7. الفعاليات (`events.php`)

### 7.1 جلب الفعاليات

```
GET /api/events.php
GET /api/events.php?status=قادم
GET /api/events.php?committee_id=c1
GET /api/events.php?date_from=2024-01-01&date_to=2024-12-31
```

**استجابة** (شكل State):
```json
{
  "data": [{
    "id": "uuid",
    "name": "رحلة العمرة",
    "committeeId": "c1",
    "status": "قادم",
    "date": "2024-03-15",
    "budget": 5000.0,
    "participants": 30,
    "lead": "محمد",
    "notes": "",
    "icon": "🕋",
    "images": ["https://..."],
    "created_at": "..."
  }]
}
```

**القيم المقبولة لـ status**: `قادم`, `جاري`, `مكتمل`, `ملغي`

### 7.2 إنشاء فعالية

```
POST /api/events.php
```

**الحقل المطلوب**: `name`
**الصور**: مصفوفة URLs (لا يُقبل base64)

---

## 8. اللجان (`committees.php`)

### 8.1 جلب اللجان

```
GET /api/committees.php
```

**استجابة** (مع إحصائيات مُلحقة):
```json
{
  "data": [{
    "id": "c1",
    "name": "لجنة العمرة الرجبية",
    "icon": "🕋",
    "color": "linear-gradient(135deg,#1a6b3c,#2d9955)",
    "description": "...",
    "advisory": false,
    "members_count": 5,
    "member_count": 8,
    "event_count": 3,
    "sort_order": 1
  }]
}
```

**ملاحظة**: `member_count` = max(linked_count, manual_count)

### 8.2 إنشاء لجنة

```
POST /api/committees.php
Body: {
  "name": "لجنة جديدة",
  "icon": "🏛️",
  "color": "linear-gradient(135deg,#47915C,#2d6b40)",
  "description": "وصف اللجنة",
  "advisory": false,
  "members_count": 0,
  "sort_order": 12
}
```

---

## 9. التصويت (`polls.php`)

### 9.1 إنشاء استطلاع

```
POST /api/polls.php
```

```json
{
  "title": "هل توافقون على زيادة الاشتراك؟",
  "options": ["نعم", "لا", "محايد"],
  "committee": "c1",
  "end": "2024-06-01"
}
```

**القيود**: 2 خيارات على الأقل

### 9.2 التصويت

```
POST /api/polls.php?action=vote
```

```json
{
  "poll_id": "uuid",
  "option_index": 0,
  "user_id": "user_default"
}
```

**سلوك**: Upsert — إذا صوّت المستخدم سابقاً يُحدَّث صوته.

### 9.3 إغلاق استطلاع

```
PUT /api/polls.php?action=close&id=uuid
```

### 9.4 شكل الاستجابة

```json
{
  "data": {
    "id": "uuid",
    "title": "...",
    "committee": "c1",
    "end": "2024-06-01",
    "active": true,
    "created": "2024-03-01",
    "options": [
      { "text": "نعم", "votes": ["user_default", "user_2"], "_id": 1 },
      { "text": "لا", "votes": [], "_id": 2 }
    ]
  }
}
```

---

## 10. شجرة العائلة (`family-tree.php`)

### 10.1 جلب الشجرة (عام)

```
GET /api/family-tree.php
Auth: لا
```

```json
{
  "members": [
    {
      "id": "uuid",
      "name": "عبدالله العوامي",
      "parent_id": null,
      "gender": "ذكر",
      "is_alive": true,
      "spouse_name": "فاطمة",
      "sort_order": 0,
      "created_at": "..."
    }
  ]
}
```

### 10.2 إضافة فرد

```
POST /api/family-tree.php
Auth: نعم + CSRF
```

```json
{
  "name": "محمد عبدالله العوامي",
  "parent_id": "uuid_of_parent",
  "gender": "ذكر",
  "is_alive": true,
  "spouse_name": "سارة",
  "sort_order": 1
}
```

**التحقق**: الاسم مطلوب، parent_id يجب أن يكون موجوداً

### 10.3 حذف فرد

```
DELETE /api/family-tree.php?id=uuid
```

**سلوك**: يُنقل أبناء المحذوف لجده (parent_id = parent_id of deleted)

---

## 11. الأخبار (`news.php`)

### 11.1 جلب الأخبار

```
GET /api/news.php                    → المنشور فقط (زوار)
GET /api/news.php (مع Auth)          → الكل (مشرف)
GET /api/news.php?category=إعلانات
GET /api/news.php?search=كلمة
GET /api/news.php?status=draft       → (مشرف فقط)
```

### 11.2 إنشاء خبر

```
POST /api/news.php
Auth: نعم
```

```json
{
  "title": "إعلان مهم",
  "content": "<p>المحتوى الكامل</p>",
  "excerpt": "ملخص الخبر",
  "image": "https://...",
  "category": "إعلانات",
  "author": "إدارة المجلس",
  "status": "published"
}
```

**الفئات**: `عام`, `فعاليات`, `إعلانات`, `اجتماعات`, `مالية`, `اجتماعية`
**الحالات**: `published`, `draft`

---

## 12. رسائل التواصل (`messages.php`)

### 12.1 إرسال رسالة (عام)

```
POST /api/messages.php
Auth: لا
Rate Limit: 5 رسائل / ساعة لكل IP
```

```json
{
  "name": "زائر",
  "phone": "0501234567",
  "subject": "استفسار",
  "message": "نص الرسالة",
  "website": ""
}
```

**ملاحظة**: حقل `website` هو Honeypot — إذا مُلئ يُقبل الطلب صامتاً بدون حفظ.

### 12.2 جلب الرسائل (مشرف)

```
GET /api/messages.php
GET /api/messages.php?is_read=0
Auth: نعم
```

**استجابة** تشمل: `data`, `total`, `unread`

### 12.3 تحديد كمقروءة

```
PUT /api/messages.php?id=uuid
Body: { "is_read": 1 }
```

---

## 13. إعدادات الموقع (`settings.php`)

### 13.1 جلب الإعدادات

```
GET /api/settings.php
Auth: نعم
```

### 13.2 حفظ قسم محدد

```
POST /api/settings.php
Auth: نعم
Body: { "section": "hero", "data": { "title": "...", "description": "..." } }
```

### 13.3 حفظ كامل

```
POST /api/settings.php
Auth: نعم
Body: { "header": {...}, "hero": {...}, "stats": {...}, ... }
```

---

## 14. المعرض (`media.php`)

**ملاحظة**: المعرض يُخزن داخل `website_settings.data.media` كمصفوفة JSON.

### 14.1 إنشاء عنصر

```
POST /api/media.php
Auth: نعم
```

```json
{
  "title": "صورة الفعالية",
  "url": "https://...",
  "type": "images",
  "date": "2024-03-01",
  "tags": ["عمرة", "2024"]
}
```

**أنواع type**: `images`, `videos`, `youtube`

---

## 15. الاجتماع القادم (`meeting.php`)

### 15.1 جلب بيانات الاجتماع (عام)

```
GET /api/meeting.php
Auth: لا
```

```json
{
  "meeting": {
    "date": "2024-06-15 20:00:00",
    "title": "الجلسة العمومية للمجلس",
    "visible": true
  }
}
```

### 15.2 تحديث الاجتماع

```
POST /api/meeting.php
Auth: نعم
Body: { "date": "2024-06-15 20:00:00", "title": "...", "visible": true }
```

---

## 16. التقارير المحفوظة (`reports.php`)

### 16.1 حفظ تقرير

```
POST /api/reports.php
```

```json
{
  "title": "تقرير Q1 2024",
  "description": "...",
  "report_data": { ... },
  "summary": { ... },
  "file_name": "data.xlsx",
  "total_transactions": 150,
  "total_income": 50000.00,
  "total_expense": 30000.00,
  "net_profit": 20000.00
}
```

### 16.2 أرشفة / استعادة

```
PUT /api/reports.php?id=uuid&action=archive
PUT /api/reports.php?id=uuid&action=restore
```

---

## 17. التصدير (`export.php`)

```
GET /api/export.php?type=members
GET /api/export.php?type=payments&period_id=uuid
GET /api/export.php?type=transactions&date_from=2024-01-01&date_to=2024-12-31
Auth: نعم
```

**الاستجابة**: ملف CSV مع `Content-Type: text/csv; charset=utf-8` و BOM للتوافق مع Excel.

---

## 18. سجل التدقيق (`audit.php`)

```
GET /api/audit.php
GET /api/audit.php?entity_type=عضو
GET /api/audit.php?action=حذف
GET /api/audit.php?limit=50
Auth: نعم
```

**استجابة**:
```json
{
  "data": [{
    "id": "uuid",
    "user": "admin",
    "action": "إضافة",
    "entity_type": "عضو",
    "entity_id": "uuid",
    "entity_name": "أحمد",
    "details": {},
    "ip_address": "192.168.1.1",
    "created_at": "2024-03-01 10:30:00"
  }]
}
```

---

## 19. قواعد التحقق المشتركة

### parseId()
- يقرأ `$_GET['id']`
- يتحقق من الصيغة: `/^[a-zA-Z0-9_-]{1,64}$/`
- يُرجع `null` إذا لم يُرسل، أو يُرجع خطأ 400 إذا كان غير صالح

### sanitizeString()
- يتحقق أن القيمة string أو numeric
- يُنظف بـ `trim()`
- يُرجع خطأ 422 إذا كان النوع خاطئاً

### bodyJson()
- يقرأ من `php://input`
- حد أقصى: 64KB (قابل للتخصيص)
- يُرجع خطأ 413 عند التجاوز
- يُرجع مصفوفة فارغة إذا لم يوجد body
