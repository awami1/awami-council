<?php
/**
 * audit_helper.php — دالة تسجيل التدقيق
 * يُضمَّن في كل endpoint يحتاج تسجيل تدقيق
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * سجّل حدثاً في سجل التدقيق
 *
 * @param string $action     الإجراء (إضافة، تعديل، حذف، تصدير، دخول، خروج)
 * @param string $entityType نوع الكيان (عضو، دفعة، معاملة، فعالية، تصويت، إعدادات، ...)
 * @param string $entityId   معرّف الكيان
 * @param string $entityName اسم الكيان (للعرض)
 * @param array  $details    تفاصيل إضافية (JSON)
 */
function logAudit(
    string $action,
    string $entityType,
    string $entityId = '',
    string $entityName = '',
    array  $details = []
): void {
    try {
        $pdo = getPDO();

        // تأكد من وجود الجدول
        ensureAuditTable($pdo);

        $user = 'admin';
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['awami_user'])) {
            $user = $_SESSION['awami_user'];
        }

        // استخدام REMOTE_ADDR فقط — X-Forwarded-For قابل للتزوير من العميل
        $ip = trim($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        $pdo->prepare(
            'INSERT INTO audit_log (id, user, action, entity_type, entity_id, entity_name, details, ip_address)
             VALUES (:id, :user, :action, :entity_type, :entity_id, :entity_name, :details, :ip)'
        )->execute([
            ':id'          => uid(),
            ':user'        => $user,
            ':action'      => $action,
            ':entity_type' => $entityType,
            ':entity_id'   => $entityId,
            ':entity_name' => $entityName,
            ':details'     => json_encode($details, JSON_UNESCAPED_UNICODE),
            ':ip'          => $ip,
        ]);
    } catch (\Throwable $e) {
        // لا نريد أن يفشل الـ endpoint بسبب فشل التدقيق
        error_log('Audit log error: ' . $e->getMessage());
    }
}

/**
 * تأكد من وجود جدول التدقيق (يُنشأ تلقائياً إن لم يكن موجوداً)
 */
function ensureAuditTable(PDO $pdo): void
{
    static $checked = false;
    if ($checked) return;

    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            user VARCHAR(100) NOT NULL DEFAULT 'admin',
            action VARCHAR(50) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id VARCHAR(36) DEFAULT '',
            entity_name VARCHAR(300) DEFAULT '',
            details TEXT DEFAULT '{}',
            ip_address VARCHAR(45) DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `audit_log` (
            `id` VARCHAR(36) NOT NULL,
            `user` VARCHAR(100) NOT NULL DEFAULT 'admin',
            `action` VARCHAR(50) NOT NULL,
            `entity_type` VARCHAR(50) NOT NULL,
            `entity_id` VARCHAR(36) DEFAULT '',
            `entity_name` VARCHAR(300) DEFAULT '',
            `details` JSON DEFAULT NULL,
            `ip_address` VARCHAR(45) DEFAULT '',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_audit_entity` (`entity_type`),
            INDEX `idx_audit_action` (`action`),
            INDEX `idx_audit_date` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $checked = true;
}
