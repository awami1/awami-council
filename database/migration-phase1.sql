-- =============================================
-- المرحلة الأولى: نظام هوية الأعضاء والصفحة الشخصية
-- Migration Phase 1 — Member Identity & Profile System
-- PRD v1.1 — مارس 2026
-- =============================================
-- يدعم: MySQL 8.0+ و SQLite (عبر أقسام منفصلة)
-- =============================================

-- ╔═══════════════════════════════════════════════╗
-- ║           القسم أ: MySQL (الإنتاج)            ║
-- ╚═══════════════════════════════════════════════╝

-- ─────────────────────────────────────────────────
-- §3.1 جدول جديد: member_users
-- بيانات دخول الأعضاء — مفصول عن جدول members
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `member_users` (
  `id` VARCHAR(36) NOT NULL,
  `member_id` VARCHAR(36) NOT NULL,
  `awm_id` VARCHAR(10) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_login` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = يجب تغيير كلمة السر',
  `temp_token` VARCHAR(100) DEFAULT NULL COMMENT 'رمز التفعيل المؤقت',
  `token_expiry` DATETIME DEFAULT NULL COMMENT 'صلاحية 48 ساعة',
  `last_login` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = لم يُفعَّل بعد',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_member_users_member` (`member_id`),
  UNIQUE KEY `uq_member_users_awm_id` (`awm_id`),
  CONSTRAINT `fk_member_users_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────
-- §3.2 تعديل جدول members
-- إضافة حالة 'منقطع' + حقول التجاوز اليدوي
-- ─────────────────────────────────────────────────

-- إضافة 'منقطع' لقيم حالة العضوية
ALTER TABLE `members`
MODIFY COLUMN `status` ENUM('نشط', 'منقطع', 'معفي', 'غير نشط') NOT NULL DEFAULT 'نشط';

-- حقل لتحديد إذا كانت الحالة يدوية أو تلقائية
ALTER TABLE `members`
ADD COLUMN `status_override` TINYINT(1) NOT NULL DEFAULT 0
COMMENT '1 = حالة يدوية بواسطة المدير، 0 = محسوبة تلقائياً';

-- حقل لسبب التجاوز اليدوي
ALTER TABLE `members`
ADD COLUMN `status_override_note` VARCHAR(300) DEFAULT NULL;

-- ─────────────────────────────────────────────────
-- §3.3 تعديل جدول committee_members
-- دعم الأرشفة التاريخية + دور العضو
-- ─────────────────────────────────────────────────

-- سنة بداية العضوية في اللجنة
ALTER TABLE `committee_members`
ADD COLUMN `start_year` YEAR DEFAULT NULL;

-- سنة انتهاء العضوية (NULL = عضوية حالية)
ALTER TABLE `committee_members`
ADD COLUMN `end_year` YEAR DEFAULT NULL
COMMENT 'NULL = عضوية حالية';

-- دور العضو في اللجنة
ALTER TABLE `committee_members`
ADD COLUMN `role` VARCHAR(100) DEFAULT NULL
COMMENT 'مثال: رئيس، نائب، أمين صندوق، عضو';

-- ─────────────────────────────────────────────────
-- §3.4 جدول جديد: member_objections
-- نظام اعتراضات وملاحظات الأعضاء
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `member_objections` (
  `id` VARCHAR(36) NOT NULL,
  `member_id` VARCHAR(36) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `body` TEXT NOT NULL,
  `related_to` ENUM('دفعة', 'لجنة', 'بيانات', 'أخرى') NOT NULL DEFAULT 'أخرى',
  `related_id` VARCHAR(36) DEFAULT NULL COMMENT 'معرف الكيان المعترض عليه',
  `status` ENUM('جديد', 'قيد المراجعة', 'تمت المعالجة', 'مرفوض') NOT NULL DEFAULT 'جديد',
  `admin_reply` TEXT DEFAULT NULL,
  `replied_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_objections_member` (`member_id`),
  INDEX `idx_objections_status` (`status`),
  CONSTRAINT `fk_objections_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ╔═══════════════════════════════════════════════╗
-- ║          القسم ب: SQLite (التطوير)            ║
-- ╚═══════════════════════════════════════════════╝
-- ملاحظة: SQLite لا يدعم MODIFY COLUMN أو ENUM.
-- لتنفيذ أقسام SQLite، شغّل الأوامر أدناه بشكل منفصل
-- (أو عبر setup.php الذي يتعامل مع الفروقات تلقائياً).
-- ─────────────────────────────────────────────────

-- §3.1 جدول member_users (SQLite)
/*
CREATE TABLE IF NOT EXISTS member_users (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  member_id VARCHAR(36) NOT NULL UNIQUE,
  awm_id VARCHAR(10) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_login INTEGER NOT NULL DEFAULT 1,
  temp_token VARCHAR(100) DEFAULT NULL,
  token_expiry DATETIME DEFAULT NULL,
  last_login DATETIME DEFAULT NULL,
  is_active INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);
*/

-- §3.2 تعديل جدول members (SQLite)
-- SQLite لا يدعم MODIFY COLUMN، لذلك نستخدم CHECK بدلاً من ENUM
-- الحقول الجديدة فقط يمكن إضافتها بـ ALTER TABLE
/*
ALTER TABLE members ADD COLUMN status_override INTEGER NOT NULL DEFAULT 0;
ALTER TABLE members ADD COLUMN status_override_note VARCHAR(300) DEFAULT NULL;
*/
-- ملاحظة: لتحديث CHECK constraint لعمود status ليشمل 'منقطع'،
-- يجب إعادة إنشاء الجدول عبر setup.php (SQLite لا يدعم تعديل CHECK).

-- §3.3 تعديل جدول committee_members (SQLite)
/*
ALTER TABLE committee_members ADD COLUMN start_year INTEGER DEFAULT NULL;
ALTER TABLE committee_members ADD COLUMN end_year INTEGER DEFAULT NULL;
ALTER TABLE committee_members ADD COLUMN role VARCHAR(100) DEFAULT NULL;
*/

-- §3.4 جدول member_objections (SQLite)
/*
CREATE TABLE IF NOT EXISTS member_objections (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  member_id VARCHAR(36) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  related_to TEXT NOT NULL DEFAULT 'أخرى' CHECK(related_to IN ('دفعة', 'لجنة', 'بيانات', 'أخرى')),
  related_id VARCHAR(36) DEFAULT NULL,
  status TEXT NOT NULL DEFAULT 'جديد' CHECK(status IN ('جديد', 'قيد المراجعة', 'تمت المعالجة', 'مرفوض')),
  admin_reply TEXT DEFAULT NULL,
  replied_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_objections_member ON member_objections(member_id);
CREATE INDEX IF NOT EXISTS idx_objections_status ON member_objections(status);
*/

-- =============================================
-- نهاية ملف Migration — المرحلة الأولى
-- =============================================
