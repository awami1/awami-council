# DATABASE_SCHEMA.md — هيكل قاعدة البيانات
# مجلس عائلة العوامي (Awami Council)

---

## 1. نظرة عامة

- **محرك الإنتاج**: MySQL 8.0+ (utf8mb4_unicode_ci)
- **محرك التطوير**: SQLite 3.x (WAL mode, Foreign Keys ON)
- **المعرفات**: UUID v4 (VARCHAR(36)) — تُنشأ بدالة `uid()`
- **الترميز**: utf8mb4 لدعم الأحرف العربية والـ Emoji
- **إنشاء الجداول**: `api/setup.php` (يُنفَّذ مرة واحدة عند التثبيت)

---

## 2. مخطط العلاقات (ER Diagram — نصي)

```
members ──────────────┬──── payments ────── periods
   │                  │
   │                  ├──── committee_members ────── committees
   │                  │
   └── branch_id ───→ family_branches
                      │
family_tree ──────────┤ (parent_id → family_tree.id)
                      │
events ───────────────┤ (committee_id → committees.id)
                      │
transactions ─────────┤ (member_id, period_id, committee_id)
                      │
polls ── poll_options ── poll_votes
                      │
news                  │ (مستقل)
messages              │ (مستقل)
website_settings      │ (سجل واحد — JSON)
next_meeting          │ (سجل واحد)
saved_reports         │ (مستقل)
stories               │ (مستقل)
gallery_stories       │ (مستقل)
audit_log             │ (مستقل — تسجيل تلقائي)
```

---

## 3. تعريف الجداول

### 3.1 `members` — الأعضاء

| العمود | النوع (MySQL) | النوع (SQLite) | القيود | الوصف |
|--------|---------------|----------------|--------|-------|
| `id` | VARCHAR(36) | VARCHAR(36) | PRIMARY KEY | UUID |
| `name` | VARCHAR(200) | VARCHAR(200) | NOT NULL | اسم العضو |
| `family` | VARCHAR(200) | VARCHAR(200) | NOT NULL DEFAULT '' | اسم العائلة |
| `phone` | VARCHAR(20) | VARCHAR(20) | DEFAULT NULL | رقم الهاتف |
| `id_num` | VARCHAR(20) | VARCHAR(20) | UNIQUE, DEFAULT NULL | رقم الهوية |
| `join_date` | DATE | DATE | DEFAULT NULL | تاريخ الانضمام |
| `status` | ENUM('نشط','معفي','غير نشط') | TEXT CHECK(...) | NOT NULL DEFAULT 'نشط' | حالة العضو |
| `notes` | TEXT | TEXT | — | ملاحظات |
| `branch_id` | VARCHAR(36) | VARCHAR(36) | DEFAULT NULL | FK → family_branches |
| `created_at` | TIMESTAMP | DATETIME | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |
| `updated_at` | TIMESTAMP | DATETIME | NOT NULL DEFAULT CURRENT_TIMESTAMP | ON UPDATE (MySQL) |

**الفهارس**: `uq_id_num` (UNIQUE على id_num)، `idx_status` (MySQL فقط)

---

### 3.2 `periods` — الفترات المالية

| العمود | النوع | القيود | الوصف |
|--------|-------|--------|-------|
| `id` | VARCHAR(36) | PRIMARY KEY | UUID |
| `name` | VARCHAR(200) | NOT NULL | اسم الفترة |
| `fee_amount` | DECIMAL(10,2) | NOT NULL DEFAULT 0 | مبلغ الاشتراك |
| `start_date` | DATE | DEFAULT NULL | تاريخ البداية |
| `end_date` | DATE | DEFAULT NULL | تاريخ النهاية |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

---

### 3.3 `payments` — المدفوعات

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(36) | PRIMARY KEY | UUID |
| `member_id` | VARCHAR(36) | NOT NULL, FK → members(id) ON DELETE CASCADE | العضو |
| `period_id` | VARCHAR(36) | NOT NULL, FK → periods(id) ON DELETE CASCADE | الفترة |
| `amount` | DECIMAL(10,2) | NOT NULL DEFAULT 0 | المبلغ المدفوع |
| `required` | DECIMAL(10,2) | NOT NULL DEFAULT 0 | المبلغ المطلوب |
| `pay_date` | DATE | DEFAULT NULL | تاريخ الدفع |
| `method` | VARCHAR(100) | DEFAULT NULL | طريقة الدفع |
| `status` | ENUM('مدفوع','لم يدفع','معفي') | NOT NULL DEFAULT 'لم يدفع' | حالة الدفع |
| `notes` | TEXT | — | ملاحظات |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |
| `updated_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**القيود**: UNIQUE(`member_id`, `period_id`)
**الفهارس**: `idx_payments_member`, `idx_payments_period`

---

### 3.4 `transactions` — المعاملات المالية

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(36) | PRIMARY KEY | UUID |
| `type` | ENUM('إيراد','مصروف') | NOT NULL | نوع المعاملة |
| `amount` | DECIMAL(10,2) | NOT NULL DEFAULT 0 | المبلغ |
| `category` | VARCHAR(100) | DEFAULT NULL | الفئة |
| `committee_id` | VARCHAR(36) | DEFAULT NULL | اللجنة (Soft FK) |
| `description` | VARCHAR(500) | NOT NULL DEFAULT '' | الوصف |
| `tx_date` | DATE | DEFAULT NULL | تاريخ المعاملة |
| `member_id` | VARCHAR(36) | DEFAULT NULL | العضو (Soft FK) |
| `period_id` | VARCHAR(36) | DEFAULT NULL | الفترة (Soft FK) |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**الفهارس**: `idx_type`, `idx_tx_date`

---

### 3.5 `events` — الفعاليات

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(36) | PRIMARY KEY | UUID |
| `name` | VARCHAR(200) | NOT NULL | اسم الفعالية |
| `committee_id` | VARCHAR(36) | DEFAULT NULL | اللجنة المنظمة |
| `status` | ENUM('قادم','جاري','مكتمل','ملغي') | NOT NULL DEFAULT 'قادم' | الحالة |
| `event_date` | DATE | DEFAULT NULL | التاريخ |
| `budget` | DECIMAL(10,2) | NOT NULL DEFAULT 0 | الميزانية |
| `participants` | INT | NOT NULL DEFAULT 0 | عدد المشاركين |
| `lead` | VARCHAR(200) | DEFAULT NULL | المسؤول |
| `notes` | TEXT | — | ملاحظات |
| `icon` | VARCHAR(10) | DEFAULT '🎉' | أيقونة Emoji |
| `images` | JSON / TEXT | DEFAULT NULL | قائمة URLs للصور |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**الفهارس**: `idx_events_date`

---

### 3.6 `committees` — اللجان

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(36) | PRIMARY KEY | UUID |
| `name` | VARCHAR(200) | NOT NULL | اسم اللجنة |
| `icon` | VARCHAR(10) | NOT NULL DEFAULT '🏛️' | أيقونة |
| `color` | VARCHAR(200) | NOT NULL DEFAULT 'linear-gradient(...)' | لون التدرج |
| `description` | TEXT | DEFAULT '' | الوصف |
| `advisory` | TINYINT(1) / INTEGER | NOT NULL DEFAULT 0 | استشارية؟ |
| `members_count` | INT / INTEGER | NOT NULL DEFAULT 0 | عدد الأعضاء (يدوي) |
| `sort_order` | INT / INTEGER | NOT NULL DEFAULT 0 | ترتيب العرض |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**ملاحظة**: يُزرع 11 لجنة افتراضية عند إنشاء الجدول إذا كان فارغاً.

---

### 3.7 `committee_members` — ربط أعضاء باللجان

| العمود | النوع | القيود | الوصف |
|--------|-------|--------|-------|
| `committee_id` | VARCHAR(36) | NOT NULL | FK (Soft) → committees |
| `member_id` | VARCHAR(36) | NOT NULL, FK → members(id) ON DELETE CASCADE | العضو |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**المفتاح الأساسي**: PRIMARY KEY(`committee_id`, `member_id`)

---

### 3.8 `polls` — التصويتات

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(36) | PRIMARY KEY | UUID |
| `title` | VARCHAR(500) | NOT NULL | عنوان الاستطلاع |
| `options` | JSON / TEXT | DEFAULT NULL | (قديم — الخيارات في جدول منفصل الآن) |
| `committee_id` | VARCHAR(36) | NOT NULL DEFAULT '' | اللجنة |
| `end_date` | DATE | DEFAULT NULL | تاريخ الانتهاء |
| `is_active` | TINYINT(1) / INTEGER | NOT NULL DEFAULT 1 | نشط؟ |
| `created_date` | DATE | NOT NULL DEFAULT CURDATE() | تاريخ الإنشاء |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

---

### 3.9 `poll_options` — خيارات التصويت

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT / INTEGER AUTOINCREMENT | PRIMARY KEY | — |
| `poll_id` | VARCHAR(36) | NOT NULL, FK → polls(id) ON DELETE CASCADE | الاستطلاع |
| `sort_order` | TINYINT / INTEGER | NOT NULL DEFAULT 0 | الترتيب |
| `text` | VARCHAR(500) | NOT NULL | نص الخيار |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**الفهارس**: `idx_po_poll_id`

---

### 3.10 `poll_votes` — أصوات التصويت

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT / INTEGER AUTOINCREMENT | PRIMARY KEY | — |
| `poll_id` | VARCHAR(36) | NOT NULL, FK → polls(id) ON DELETE CASCADE | الاستطلاع |
| `option_id` | BIGINT UNSIGNED / INTEGER | NOT NULL, FK → poll_options(id) ON DELETE CASCADE | الخيار |
| `user_id` | VARCHAR(100) | NOT NULL DEFAULT 'user_default' | معرف المصوت |
| `voted_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**القيود**: UNIQUE(`poll_id`, `user_id`) — صوت واحد لكل مستخدم لكل استطلاع

---

### 3.11 `family_tree` — شجرة العائلة

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(36) | PRIMARY KEY | UUID |
| `name` | VARCHAR(200) | NOT NULL | الاسم |
| `parent_id` | VARCHAR(36) | DEFAULT NULL, FK → family_tree(id) ON DELETE SET NULL | الأب |
| `gender` | ENUM('ذكر','أنثى') | NOT NULL DEFAULT 'ذكر' | الجنس |
| `is_alive` | TINYINT(1) / INTEGER | NOT NULL DEFAULT 1 | على قيد الحياة؟ |
| `spouse_name` | VARCHAR(200) | DEFAULT NULL | اسم الزوج/ة |
| `sort_order` | INT / INTEGER | NOT NULL DEFAULT 0 | ترتيب العرض |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**الفهارس**: `idx_ft_parent`
**سلوك الحذف**: عند حذف شخص، يُنقل أبناؤه لجده (`ON DELETE SET NULL` + تحديث يدوي)

---

### 3.12 `family_branches` — فروع العائلة

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(36) | PRIMARY KEY | UUID |
| `name` | VARCHAR(200) | NOT NULL | اسم الفرع |
| `head` | VARCHAR(200) | DEFAULT NULL | رئيس الفرع |
| `count` | INT / INTEGER | NOT NULL DEFAULT 0 | عدد الأعضاء |
| `color` | VARCHAR(20) | NOT NULL DEFAULT '#47915C' | اللون |
| `notes` | TEXT | — | ملاحظات |
| `members` | JSON / TEXT | DEFAULT NULL | قائمة أسماء الأعضاء |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

---

### 3.13 `news` — الأخبار

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(64) | PRIMARY KEY | UUID |
| `title` | VARCHAR(500) | NOT NULL | العنوان |
| `content` | TEXT | — | المحتوى الكامل |
| `excerpt` | VARCHAR(500) | NOT NULL DEFAULT '' | المقتطف |
| `image` | VARCHAR(500) | NOT NULL DEFAULT '' | رابط الصورة |
| `category` | VARCHAR(100) | NOT NULL DEFAULT 'عام' | الفئة |
| `author` | VARCHAR(200) | NOT NULL DEFAULT '' | الكاتب |
| `status` | ENUM('published','draft') | NOT NULL DEFAULT 'published' | الحالة |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |
| `updated_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**الفهارس**: `idx_news_created`

---

### 3.14 `messages` — رسائل التواصل

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(64) | PRIMARY KEY | UUID |
| `name` | VARCHAR(200) | NOT NULL | اسم المرسل |
| `email` | VARCHAR(300) | NOT NULL DEFAULT '' | البريد الإلكتروني |
| `phone` | VARCHAR(50) | NOT NULL DEFAULT '' | رقم الهاتف |
| `subject` | VARCHAR(500) | NOT NULL DEFAULT '' | الموضوع |
| `message` | TEXT | NOT NULL | الرسالة |
| `is_read` | TINYINT(1) / INTEGER | NOT NULL DEFAULT 0 | مقروءة؟ |
| `created_at` | DATETIME | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**الفهارس**: `idx_messages_created`

---

### 3.15 `website_settings` — إعدادات الموقع

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | INT | PRIMARY KEY, DEFAULT 1 | سجل واحد دائماً (id=1) |
| `data` | JSON / MEDIUMTEXT | NOT NULL | إعدادات JSON كاملة |
| `updated_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**هيكل JSON في `data`**:
```json
{
  "header": { "title": "...", "subtitle": "..." },
  "hero": { "title": "...", "description": "..." },
  "stats": { "years": 32, "committees": 11, "members": "+100" },
  "about": { "mission": "...", "vision": "..." },
  "councilPositions": [...],
  "values": [...],
  "logo": null,
  "media": [...],
  "contact": { "whatsapp": "..." }
}
```

---

### 3.16 `next_meeting` — الاجتماع القادم

| العمود | النوع | القيود | الوصف |
|--------|-------|--------|-------|
| `id` | INT | PRIMARY KEY, DEFAULT 1 | سجل واحد (id=1) |
| `date` | DATETIME | DEFAULT NULL | موعد الاجتماع |
| `title` | VARCHAR(300) | NOT NULL DEFAULT 'الجلسة العمومية للمجلس' | العنوان |
| `visible` | TINYINT(1) / INTEGER | NOT NULL DEFAULT 1 | ظاهر؟ |
| `updated_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

---

### 3.17 `saved_reports` — التقارير المحفوظة

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(36) | PRIMARY KEY | UUID |
| `title` | VARCHAR(300) | NOT NULL | عنوان التقرير |
| `description` | TEXT | — | الوصف |
| `report_type` | VARCHAR(50) | NOT NULL DEFAULT 'smart_analysis' | نوع التقرير |
| `report_data` | JSON / TEXT | NOT NULL | بيانات التقرير الكاملة |
| `summary` | JSON / TEXT | DEFAULT NULL | ملخص التقرير |
| `status` | ENUM('active','archived') | NOT NULL DEFAULT 'active' | الحالة |
| `file_name` | VARCHAR(200) | DEFAULT NULL | اسم الملف المصدري |
| `total_transactions` | INT | DEFAULT 0 | عدد المعاملات |
| `total_income` | DECIMAL(12,2) | DEFAULT 0 | إجمالي الإيرادات |
| `total_expense` | DECIMAL(12,2) | DEFAULT 0 | إجمالي المصروفات |
| `net_profit` | DECIMAL(12,2) | DEFAULT 0 | صافي الربح |
| `created_by` | VARCHAR(36) | DEFAULT NULL | المنشئ |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |
| `updated_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**الفهارس**: `idx_reports_status`, `idx_reports_created`

---

### 3.18 `stories` — القصص والسير

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(64) | PRIMARY KEY | — |
| `title` | VARCHAR(255) | NOT NULL | العنوان |
| `slug` | VARCHAR(255) | NOT NULL, UNIQUE | الرابط الودي |
| `category` | VARCHAR(50) | NOT NULL DEFAULT 'biography' | الفئة |
| `excerpt` | TEXT | — | مقتطف |
| `content` | LONGTEXT | — | المحتوى الكامل |
| `cover_image` | VARCHAR(500) | NOT NULL DEFAULT '' | صورة الغلاف |
| `person_name` | VARCHAR(255) | NOT NULL DEFAULT '' | اسم الشخص |
| `person_image` | VARCHAR(500) | NOT NULL DEFAULT '' | صورة الشخص |
| `person_bio` | TEXT | — | سيرة مختصرة |
| `person_status` | ENUM('deceased','alive') | NOT NULL DEFAULT 'alive' | حالة الشخص |
| `author_name` | VARCHAR(255) | NOT NULL DEFAULT '' | الكاتب |
| `status` | ENUM('draft','published') | NOT NULL DEFAULT 'draft' | حالة النشر |
| `is_pinned` | TINYINT | NOT NULL DEFAULT 0 | مثبت؟ |
| `published_at` | DATETIME | NULL | تاريخ النشر |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |
| `updated_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**الفهارس**: `idx_stories_status`, `idx_stories_published`

---

### 3.19 `gallery_stories` — قصص المعرض

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(64) | PRIMARY KEY | — |
| `title` | VARCHAR(255) | NOT NULL | العنوان |
| `subtitle` | VARCHAR(500) | NOT NULL DEFAULT '' | العنوان الفرعي |
| `type` | ENUM('سيرة ذاتية','رثاء','قصة نجاح','ذكريات','وصايا') | NOT NULL DEFAULT 'سيرة ذاتية' | النوع |
| `year_range` | VARCHAR(100) | NOT NULL DEFAULT '' | نطاق السنوات |
| `quote` | TEXT | — | اقتباس |
| `full_text` | LONGTEXT | — | النص الكامل |
| `author_name` | VARCHAR(255) | NOT NULL DEFAULT '' | الكاتب |
| `read_time` | INT | NOT NULL DEFAULT 5 | وقت القراءة (دقائق) |
| `color_primary` | VARCHAR(7) | NOT NULL DEFAULT '#0B3D2E' | اللون الأساسي |
| `color_secondary` | VARCHAR(7) | NOT NULL DEFAULT '#1A6B4A' | اللون الثانوي |
| `color_accent` | VARCHAR(7) | NOT NULL DEFAULT '#D4AF37' | لون التمييز |
| `display_order` | INT | NOT NULL DEFAULT 0 | ترتيب العرض |
| `is_active` | TINYINT(1) | NOT NULL DEFAULT 1 | نشط؟ |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |
| `updated_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**الفهارس**: `idx_gs_active_order`

---

### 3.20 `audit_log` — سجل التدقيق

| العمود | النوع (MySQL) | القيود | الوصف |
|--------|---------------|--------|-------|
| `id` | VARCHAR(36) | PRIMARY KEY | UUID |
| `user` | VARCHAR(100) | NOT NULL DEFAULT 'admin' | المستخدم |
| `action` | VARCHAR(50) | NOT NULL | الإجراء (إضافة/تعديل/حذف/دخول/خروج) |
| `entity_type` | VARCHAR(50) | NOT NULL | نوع الكيان |
| `entity_id` | VARCHAR(36) | DEFAULT '' | معرف الكيان |
| `entity_name` | VARCHAR(300) | DEFAULT '' | اسم الكيان |
| `details` | JSON / TEXT | DEFAULT '{}' | تفاصيل إضافية |
| `ip_address` | VARCHAR(45) | DEFAULT '' | عنوان IP |
| `created_at` | TIMESTAMP | NOT NULL DEFAULT CURRENT_TIMESTAMP | — |

**الفهارس**: `idx_audit_entity`, `idx_audit_action`, `idx_audit_date`
**ملاحظة**: يُنشأ تلقائياً عبر `ensureAuditTable()` عند أول استخدام.

---

## 4. ملخص العلاقات (Foreign Keys)

| الجدول المصدر | العمود | الجدول المرجعي | عند الحذف |
|---------------|--------|----------------|-----------|
| `payments.member_id` | → | `members.id` | CASCADE |
| `payments.period_id` | → | `periods.id` | CASCADE |
| `committee_members.member_id` | → | `members.id` | CASCADE |
| `poll_options.poll_id` | → | `polls.id` | CASCADE |
| `poll_votes.poll_id` | → | `polls.id` | CASCADE |
| `poll_votes.option_id` | → | `poll_options.id` | CASCADE |
| `family_tree.parent_id` | → | `family_tree.id` | SET NULL |

**ملاحظة**: بعض العلاقات هي Soft FK (لا يوجد FOREIGN KEY constraint فعلي): `transactions.committee_id`, `transactions.member_id`, `events.committee_id`, `members.branch_id`.

---

## 5. ملاحظات تقنية

### 5.1 اختلافات MySQL vs SQLite

| الميزة | MySQL | SQLite |
|--------|-------|--------|
| ENUM | `ENUM('x','y')` | `TEXT CHECK(... IN ('x','y'))` |
| JSON | `JSON` نوع أصلي | `TEXT` مع JSON يدوي |
| Auto Increment | `BIGINT UNSIGNED AUTO_INCREMENT` | `INTEGER PRIMARY KEY AUTOINCREMENT` |
| Upsert | `ON DUPLICATE KEY UPDATE` | `ON CONFLICT(...) DO UPDATE` |
| Boolean | `TINYINT(1)` | `INTEGER` |
| Default Date | `DEFAULT (CURDATE())` | `DEFAULT (date('now'))` |
| Timestamps | `ON UPDATE CURRENT_TIMESTAMP` | يدوي |

### 5.2 الترحيلات (Migrations)

لا يوجد نظام migrations رسمي. التغييرات تُطبق عبر:
- `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` في `setup.php`
- `try/catch` حول ALTER TABLE في ملفات API الفردية
- `ensureXxxTable()` دوال تُنشئ الجدول عند أول استخدام

### 5.3 تهيئة قاعدة بيانات جديدة

```bash
# 1. تكوين .env مع بيانات MySQL
# 2. تشغيل setup (يتطلب مصادقة):
curl -X GET "https://your-domain/api/setup.php" \
  -H "Cookie: awami_session=xxx"
# 3. أو من المتصفح بعد تسجيل الدخول:
# زيارة /api/setup.php
```
