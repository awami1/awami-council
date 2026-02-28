<?php
/**
 * reports.php — إدارة التقارير المحفوظة
 * CRUD للتقارير الذكية مع دعم الأرشفة
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
require_once __DIR__ . '/validation.php';

requireAuth();
verifyCsrf();

// التأكد من وجود الجدول
ensureReportsTable();

function ensureReportsTable(): void
{
    static $checked = false;
    if ($checked) return;

    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS saved_reports (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            title VARCHAR(300) NOT NULL,
            description TEXT,
            report_type VARCHAR(50) NOT NULL DEFAULT 'smart_analysis',
            report_data TEXT NOT NULL,
            summary TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK(status IN ('active','archived')),
            file_name VARCHAR(200),
            total_transactions INTEGER DEFAULT 0,
            total_income DECIMAL(12,2) DEFAULT 0,
            total_expense DECIMAL(12,2) DEFAULT 0,
            net_profit DECIMAL(12,2) DEFAULT 0,
            created_by VARCHAR(36),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `saved_reports` (
            `id` VARCHAR(36) NOT NULL,
            `title` VARCHAR(300) NOT NULL,
            `description` TEXT,
            `report_type` VARCHAR(50) NOT NULL DEFAULT 'smart_analysis',
            `report_data` JSON NOT NULL,
            `summary` JSON DEFAULT NULL,
            `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
            `file_name` VARCHAR(200) DEFAULT NULL,
            `total_transactions` INT DEFAULT 0,
            `total_income` DECIMAL(12,2) DEFAULT 0,
            `total_expense` DECIMAL(12,2) DEFAULT 0,
            `net_profit` DECIMAL(12,2) DEFAULT 0,
            `created_by` VARCHAR(36) DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_reports_status` (`status`),
            INDEX `idx_reports_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $checked = true;
}

// ---- Handlers ----

function handleGetAll(): void
{
    $pdo = getPDO();
    $status = $_GET['status'] ?? null;

    if ($status && in_array($status, ['active', 'archived'], true)) {
        $stmt = $pdo->prepare(
            'SELECT id, title, description, report_type, status, file_name,
                    total_transactions, total_income, total_expense, net_profit,
                    created_by, created_at, updated_at
             FROM saved_reports WHERE status = :status ORDER BY created_at DESC'
        );
        $stmt->execute([':status' => $status]);
    } else {
        $stmt = $pdo->query(
            'SELECT id, title, description, report_type, status, file_name,
                    total_transactions, total_income, total_expense, net_profit,
                    created_by, created_at, updated_at
             FROM saved_reports ORDER BY created_at DESC'
        );
    }

    respond(200, ['data' => $stmt->fetchAll()]);
}

function handleGetOne(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM saved_reports WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'التقرير غير موجود.']);
    }

    // فك JSON
    $row['report_data'] = json_decode($row['report_data'], true);
    $row['summary']     = $row['summary'] ? json_decode($row['summary'], true) : null;

    respond(200, ['data' => $row]);
}

function handlePost(): void
{
    $pdo  = getPDO();
    $body = bodyJson(524288); // 512KB limit for report data

    $title = trim($body['title'] ?? '');
    if (!$title) {
        respond(422, ['error' => 'عنوان التقرير مطلوب.']);
    }

    if (empty($body['report_data'])) {
        respond(422, ['error' => 'بيانات التقرير مطلوبة.']);
    }

    $id = uid();
    $stmt = $pdo->prepare(
        'INSERT INTO saved_reports
            (id, title, description, report_type, report_data, summary, status, file_name,
             total_transactions, total_income, total_expense, net_profit, created_by)
         VALUES
            (:id, :title, :description, :report_type, :report_data, :summary, :status, :file_name,
             :total_transactions, :total_income, :total_expense, :net_profit, :created_by)'
    );

    $stmt->execute([
        ':id'                 => $id,
        ':title'              => $title,
        ':description'        => trim($body['description'] ?? ''),
        ':report_type'        => $body['report_type'] ?? 'smart_analysis',
        ':report_data'        => json_encode($body['report_data'], JSON_UNESCAPED_UNICODE),
        ':summary'            => isset($body['summary']) ? json_encode($body['summary'], JSON_UNESCAPED_UNICODE) : null,
        ':status'             => 'active',
        ':file_name'          => $body['file_name'] ?? null,
        ':total_transactions' => (int)($body['total_transactions'] ?? 0),
        ':total_income'       => (float)($body['total_income'] ?? 0),
        ':total_expense'      => (float)($body['total_expense'] ?? 0),
        ':net_profit'         => (float)($body['net_profit'] ?? 0),
        ':created_by'         => $_SESSION['awami_user'] ?? 'admin',
    ]);

    logAudit('إضافة', 'تقرير', $id, $title);

    respond(201, ['data' => ['id' => $id, 'title' => $title], 'message' => 'تم حفظ التقرير بنجاح.']);
}

function handlePut(string $id): void
{
    $pdo  = getPDO();
    $body = bodyJson(524288);

    // التحقق من وجود التقرير
    $stmt = $pdo->prepare('SELECT id, title FROM saved_reports WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        respond(404, ['error' => 'التقرير غير موجود.']);
    }

    // أرشفة
    if (isset($_GET['archive'])) {
        $newStatus = ($body['status'] ?? '') === 'active' ? 'active' : 'archived';
        $stmt = $pdo->prepare('UPDATE saved_reports SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $newStatus, ':id' => $id]);
        $action = $newStatus === 'archived' ? 'أرشفة' : 'استعادة';
        logAudit($action, 'تقرير', $id, $existing['title']);
        respond(200, ['message' => $newStatus === 'archived' ? 'تم أرشفة التقرير.' : 'تم استعادة التقرير.']);
    }

    // تحديث عام
    $fields = [];
    $params = [':id' => $id];

    if (isset($body['title']) && trim($body['title'])) {
        $fields[] = 'title = :title';
        $params[':title'] = trim($body['title']);
    }
    if (array_key_exists('description', $body)) {
        $fields[] = 'description = :description';
        $params[':description'] = trim($body['description'] ?? '');
    }
    if (isset($body['report_data'])) {
        $fields[] = 'report_data = :report_data';
        $params[':report_data'] = json_encode($body['report_data'], JSON_UNESCAPED_UNICODE);
    }
    if (isset($body['summary'])) {
        $fields[] = 'summary = :summary';
        $params[':summary'] = json_encode($body['summary'], JSON_UNESCAPED_UNICODE);
    }
    if (isset($body['total_transactions'])) {
        $fields[] = 'total_transactions = :total_transactions';
        $params[':total_transactions'] = (int)$body['total_transactions'];
    }
    if (isset($body['total_income'])) {
        $fields[] = 'total_income = :total_income';
        $params[':total_income'] = (float)$body['total_income'];
    }
    if (isset($body['total_expense'])) {
        $fields[] = 'total_expense = :total_expense';
        $params[':total_expense'] = (float)$body['total_expense'];
    }
    if (isset($body['net_profit'])) {
        $fields[] = 'net_profit = :net_profit';
        $params[':net_profit'] = (float)$body['net_profit'];
    }

    if (isSQLite()) {
        $fields[] = "updated_at = datetime('now')";
    }

    if (empty($fields)) {
        respond(422, ['error' => 'لا توجد بيانات للتحديث.']);
    }

    $sql = 'UPDATE saved_reports SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $pdo->prepare($sql)->execute($params);

    logAudit('تعديل', 'تقرير', $id, $params[':title'] ?? $existing['title']);

    respond(200, ['message' => 'تم تحديث التقرير بنجاح.']);
}

function handleDelete(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id, title FROM saved_reports WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'التقرير غير موجود.']);
    }

    $pdo->prepare('DELETE FROM saved_reports WHERE id = :id')->execute([':id' => $id]);

    logAudit('حذف', 'تقرير', $id, $row['title']);

    respond(200, ['message' => 'تم حذف التقرير.']);
}

// ---- Router ----
$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();

try {
    match (true) {
        $method === 'GET'    && $id === null  => handleGetAll(),
        $method === 'GET'    && $id !== null  => handleGetOne($id),
        $method === 'POST'                    => handlePost(),
        $method === 'PUT'    && $id !== null  => handlePut($id),
        $method === 'DELETE' && $id !== null  => handleDelete($id),
        default                               => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (\Throwable $e) {
    error_log('Reports API error: ' . $e->getMessage());
    respond(500, ['error' => 'حدث خطأ داخلي.']);
}
