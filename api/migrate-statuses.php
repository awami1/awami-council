<?php
/**
 * Migration: تغيير حالات العضوية
 * نشط → مشترك | غير نشط → غير مشترك | معفي → مشترك
 * + إزالة حالة "معفي" من payments
 *
 * يُنفَّذ مرة واحدة فقط عبر: GET /api/migrate-statuses.php
 * يتطلب تسجيل دخول المدير
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
requireAuth();

$pdo    = getPDO();
$sqlite = isSQLite();
$log    = [];

try {
    $pdo->beginTransaction();

    // ─── 1. ترحيل بيانات members ───────────────────────────────────
    $updates = [
        ["UPDATE members SET status = 'مشترك' WHERE status = 'نشط'",       'نشط → مشترك'],
        ["UPDATE members SET status = 'غير مشترك' WHERE status = 'غير نشط'", 'غير نشط → غير مشترك'],
        ["UPDATE members SET status = 'مشترك' WHERE status = 'معفي'",       'معفي → مشترك'],
    ];

    foreach ($updates as [$sql, $label]) {
        $count = $pdo->exec($sql);
        $log[] = "{$label}: {$count} سجل";
    }

    // ─── 2. تعديل ENUM / CHECK لجدول members ───────────────────────
    if ($sqlite) {
        // SQLite: إعادة إنشاء الجدول مع CHECK الجديد
        // حفظ البيانات → حذف الجدول القديم → إنشاء جديد → إعادة البيانات

        // نسخ البيانات
        $pdo->exec("CREATE TABLE members_backup AS SELECT * FROM members");

        // حذف الجدول الأصلي
        $pdo->exec("DROP TABLE members");

        // إنشاء الجدول بالحالات الجديدة
        $pdo->exec("CREATE TABLE members (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            family VARCHAR(200) NOT NULL DEFAULT '',
            phone VARCHAR(20) DEFAULT NULL,
            id_num VARCHAR(20) DEFAULT NULL,
            join_date DATE DEFAULT NULL,
            status TEXT NOT NULL DEFAULT 'مشترك' CHECK(status IN ('مشترك','منقطع','غير مشترك')),
            status_override INTEGER NOT NULL DEFAULT 0,
            status_override_note VARCHAR(300) DEFAULT NULL,
            notes TEXT,
            branch_id VARCHAR(36) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        // إعادة البيانات
        $pdo->exec("INSERT INTO members SELECT * FROM members_backup");

        // حذف النسخة الاحتياطية
        $pdo->exec("DROP TABLE members_backup");

        // إعادة إنشاء الفهرس
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_id_num ON members(id_num)");

        $log[] = 'SQLite: أُعيد إنشاء جدول members مع CHECK الجديد';
    } else {
        // MySQL: تعديل ENUM مباشرة
        $pdo->exec("ALTER TABLE members MODIFY COLUMN status ENUM('مشترك','منقطع','غير مشترك') NOT NULL DEFAULT 'مشترك'");
        $log[] = 'MySQL: تم تعديل ENUM لعمود status في members';
    }

    // ─── 3. ترحيل بيانات payments ──────────────────────────────────
    $count = $pdo->exec("UPDATE payments SET status = 'لم يدفع' WHERE status = 'معفي'");
    $log[] = "payments معفي → لم يدفع: {$count} سجل";

    // ─── 4. تعديل ENUM / CHECK لجدول payments ─────────────────────
    if ($sqlite) {
        // SQLite: إعادة إنشاء الجدول
        $pdo->exec("CREATE TABLE payments_backup AS SELECT * FROM payments");
        $pdo->exec("DROP TABLE payments");

        $pdo->exec("CREATE TABLE payments (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            member_id VARCHAR(36) NOT NULL,
            period_id VARCHAR(36) NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            required DECIMAL(10,2) NOT NULL DEFAULT 0,
            pay_date DATE DEFAULT NULL,
            method VARCHAR(100) DEFAULT NULL,
            status TEXT NOT NULL DEFAULT 'لم يدفع' CHECK(status IN ('مدفوع','لم يدفع')),
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(member_id, period_id),
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
            FOREIGN KEY (period_id) REFERENCES periods(id) ON DELETE CASCADE
        )");

        $pdo->exec("INSERT INTO payments SELECT * FROM payments_backup");
        $pdo->exec("DROP TABLE payments_backup");

        // إعادة إنشاء الفهارس
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_payments_member ON payments(member_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_payments_period ON payments(period_id)");

        $log[] = 'SQLite: أُعيد إنشاء جدول payments مع CHECK الجديد';
    } else {
        // MySQL: تعديل ENUM
        $pdo->exec("ALTER TABLE payments MODIFY COLUMN status ENUM('مدفوع','لم يدفع') NOT NULL DEFAULT 'لم يدفع'");
        $log[] = 'MySQL: تم تعديل ENUM لعمود status في payments';
    }

    $pdo->commit();

    logAudit('ترحيل حالات العضوية', 'migration', '', '', ['log' => $log]);

    respond(200, [
        'status'  => 'SUCCESS',
        'message' => 'تم ترحيل حالات العضوية بنجاح',
        'log'     => $log,
    ]);

} catch (\Throwable $e) {
    $pdo->rollBack();
    respond(500, [
        'status'  => 'FAILED',
        'error'   => $e->getMessage(),
        'log'     => $log,
    ]);
}
