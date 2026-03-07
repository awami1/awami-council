<?php
/**
 * سكربت تنفيذ هجرة المرحلة الأولى على MySQL (الإنتاج)
 *
 * الاستخدام: php run-migration-phase1.php
 * أو عبر المتصفح: https://alawami.site/database/run-migration-phase1.php?key=MIGRATE_2026
 *
 * ⚠️  احذف هذا الملف فوراً بعد التنفيذ!
 */
declare(strict_types=1);

// ── حماية: مفتاح أمان للتشغيل عبر المتصفح ──
if (php_sapi_name() !== 'cli') {
    if (($_GET['key'] ?? '') !== 'MIGRATE_2026') {
        http_response_code(403);
        die('⛔ مفتاح الأمان مطلوب: ?key=MIGRATE_2026');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

// ── الاتصال بقاعدة البيانات ──
require_once __DIR__ . '/../api/config.php';

$pdo = getPDO();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "═══════════════════════════════════════════\n";
echo " هجرة المرحلة الأولى — نظام هوية الأعضاء\n";
echo "═══════════════════════════════════════════\n\n";

// التحقق أننا على MySQL
if (isSQLite()) {
    die("⛔ هذا السكربت للإنتاج (MySQL) فقط. البيئة الحالية: SQLite\n");
}

$errors = [];
$success = [];

// ─────────────────────────────────────────
// 1. جدول member_users
// ─────────────────────────────────────────
echo "── 1. جدول member_users ──\n";
try {
    $exists = $pdo->query("SHOW TABLES LIKE 'member_users'")->rowCount() > 0;
    if ($exists) {
        echo "   ⏭️  موجود مسبقاً — تخطي\n";
        $success[] = 'member_users (موجود)';
    } else {
        $pdo->exec("
            CREATE TABLE `member_users` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "   ✅ تم إنشاء جدول member_users\n";
        $success[] = 'member_users (جديد)';
    }
} catch (PDOException $e) {
    echo "   ❌ خطأ: {$e->getMessage()}\n";
    $errors[] = "member_users: {$e->getMessage()}";
}

// ─────────────────────────────────────────
// 2. تعديل جدول members — إضافة 'منقطع' للـ ENUM
// ─────────────────────────────────────────
echo "\n── 2. تعديل ENUM حالة العضوية ──\n";
try {
    $col = $pdo->query("SHOW COLUMNS FROM `members` WHERE Field = 'status'")->fetch();
    if ($col && str_contains($col['Type'], 'منقطع')) {
        echo "   ⏭️  'منقطع' موجودة مسبقاً في ENUM — تخطي\n";
        $success[] = "status ENUM (موجود)";
    } else {
        $pdo->exec("
            ALTER TABLE `members`
            MODIFY COLUMN `status` ENUM('نشط', 'منقطع', 'معفي', 'غير نشط') NOT NULL DEFAULT 'نشط'
        ");
        echo "   ✅ تم تحديث ENUM ليشمل 'منقطع'\n";
        $success[] = "status ENUM (محدّث)";
    }
} catch (PDOException $e) {
    echo "   ❌ خطأ: {$e->getMessage()}\n";
    $errors[] = "status ENUM: {$e->getMessage()}";
}

// ─────────────────────────────────────────
// 3. إضافة حقل status_override
// ─────────────────────────────────────────
echo "\n── 3. حقل status_override ──\n";
try {
    $exists = $pdo->query("SHOW COLUMNS FROM `members` LIKE 'status_override'")->rowCount() > 0;
    if ($exists) {
        echo "   ⏭️  موجود مسبقاً — تخطي\n";
        $success[] = 'status_override (موجود)';
    } else {
        $pdo->exec("
            ALTER TABLE `members`
            ADD COLUMN `status_override` TINYINT(1) NOT NULL DEFAULT 0
            COMMENT '1 = حالة يدوية بواسطة المدير، 0 = محسوبة تلقائياً'
        ");
        echo "   ✅ تم إضافة حقل status_override\n";
        $success[] = 'status_override (جديد)';
    }
} catch (PDOException $e) {
    echo "   ❌ خطأ: {$e->getMessage()}\n";
    $errors[] = "status_override: {$e->getMessage()}";
}

// ─────────────────────────────────────────
// 4. إضافة حقل status_override_note
// ─────────────────────────────────────────
echo "\n── 4. حقل status_override_note ──\n";
try {
    $exists = $pdo->query("SHOW COLUMNS FROM `members` LIKE 'status_override_note'")->rowCount() > 0;
    if ($exists) {
        echo "   ⏭️  موجود مسبقاً — تخطي\n";
        $success[] = 'status_override_note (موجود)';
    } else {
        $pdo->exec("
            ALTER TABLE `members`
            ADD COLUMN `status_override_note` VARCHAR(300) DEFAULT NULL
        ");
        echo "   ✅ تم إضافة حقل status_override_note\n";
        $success[] = 'status_override_note (جديد)';
    }
} catch (PDOException $e) {
    echo "   ❌ خطأ: {$e->getMessage()}\n";
    $errors[] = "status_override_note: {$e->getMessage()}";
}

// ─────────────────────────────────────────
// 5. إضافة حقل start_year لـ committee_members
// ─────────────────────────────────────────
echo "\n── 5. حقل committee_members.start_year ──\n";
try {
    $exists = $pdo->query("SHOW COLUMNS FROM `committee_members` LIKE 'start_year'")->rowCount() > 0;
    if ($exists) {
        echo "   ⏭️  موجود مسبقاً — تخطي\n";
        $success[] = 'start_year (موجود)';
    } else {
        $pdo->exec("ALTER TABLE `committee_members` ADD COLUMN `start_year` YEAR DEFAULT NULL");
        echo "   ✅ تم إضافة حقل start_year\n";
        $success[] = 'start_year (جديد)';
    }
} catch (PDOException $e) {
    echo "   ❌ خطأ: {$e->getMessage()}\n";
    $errors[] = "start_year: {$e->getMessage()}";
}

// ─────────────────────────────────────────
// 6. إضافة حقل end_year لـ committee_members
// ─────────────────────────────────────────
echo "\n── 6. حقل committee_members.end_year ──\n";
try {
    $exists = $pdo->query("SHOW COLUMNS FROM `committee_members` LIKE 'end_year'")->rowCount() > 0;
    if ($exists) {
        echo "   ⏭️  موجود مسبقاً — تخطي\n";
        $success[] = 'end_year (موجود)';
    } else {
        $pdo->exec("ALTER TABLE `committee_members` ADD COLUMN `end_year` YEAR DEFAULT NULL COMMENT 'NULL = عضوية حالية'");
        echo "   ✅ تم إضافة حقل end_year\n";
        $success[] = 'end_year (جديد)';
    }
} catch (PDOException $e) {
    echo "   ❌ خطأ: {$e->getMessage()}\n";
    $errors[] = "end_year: {$e->getMessage()}";
}

// ─────────────────────────────────────────
// 7. إضافة حقل role لـ committee_members
// ─────────────────────────────────────────
echo "\n── 7. حقل committee_members.role ──\n";
try {
    $exists = $pdo->query("SHOW COLUMNS FROM `committee_members` LIKE 'role'")->rowCount() > 0;
    if ($exists) {
        echo "   ⏭️  موجود مسبقاً — تخطي\n";
        $success[] = 'role (موجود)';
    } else {
        $pdo->exec("ALTER TABLE `committee_members` ADD COLUMN `role` VARCHAR(100) DEFAULT NULL COMMENT 'مثال: رئيس، نائب، أمين صندوق، عضو'");
        echo "   ✅ تم إضافة حقل role\n";
        $success[] = 'role (جديد)';
    }
} catch (PDOException $e) {
    echo "   ❌ خطأ: {$e->getMessage()}\n";
    $errors[] = "role: {$e->getMessage()}";
}

// ─────────────────────────────────────────
// 8. جدول member_objections
// ─────────────────────────────────────────
echo "\n── 8. جدول member_objections ──\n";
try {
    $exists = $pdo->query("SHOW TABLES LIKE 'member_objections'")->rowCount() > 0;
    if ($exists) {
        echo "   ⏭️  موجود مسبقاً — تخطي\n";
        $success[] = 'member_objections (موجود)';
    } else {
        $pdo->exec("
            CREATE TABLE `member_objections` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "   ✅ تم إنشاء جدول member_objections\n";
        $success[] = 'member_objections (جديد)';
    }
} catch (PDOException $e) {
    echo "   ❌ خطأ: {$e->getMessage()}\n";
    $errors[] = "member_objections: {$e->getMessage()}";
}

// ═══════════════════════════════════════════
// النتيجة النهائية
// ═══════════════════════════════════════════
echo "\n═══════════════════════════════════════════\n";
echo " النتيجة\n";
echo "═══════════════════════════════════════════\n";
echo " ✅ نجح: " . count($success) . " عملية\n";
foreach ($success as $s) echo "    • {$s}\n";

if (count($errors) > 0) {
    echo "\n ❌ فشل: " . count($errors) . " عملية\n";
    foreach ($errors as $e) echo "    • {$e}\n";
    echo "\n⚠️  راجع الأخطاء أعلاه وأعد تشغيل السكربت\n";
} else {
    echo "\n🎉 تمت الهجرة بنجاح! المرحلة الأولى جاهزة.\n";
    echo "⚠️  احذف هذا الملف فوراً: rm database/run-migration-phase1.php\n";
}
