<?php
/**
 * report-archive.php — أرشيف التقارير
 * حفظ وإدارة سجلات التقارير والملفات المالية
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
require_once __DIR__ . '/validation.php';

requireAuth();
verifyCsrf();

ensureReportArchiveTable();

function ensureReportArchiveTable(): void
{
    static $checked = false;
    if ($checked) return;

    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS report_archive (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            title VARCHAR(300) NOT NULL,
            report_type TEXT NOT NULL DEFAULT 'أخرى'
                CHECK(report_type IN ('مالي','إداري','محضر اجتماع','كشف حساب','أخرى')),
            source TEXT NOT NULL DEFAULT 'manual'
                CHECK(source IN ('manual','import','generated')),
            description TEXT,
            file_url VARCHAR(500) DEFAULT NULL,
            file_type VARCHAR(20) DEFAULT NULL,
            report_date DATE NOT NULL,
            period_id VARCHAR(36) DEFAULT NULL,
            committee_id VARCHAR(36) DEFAULT NULL,
            import_stats TEXT DEFAULT NULL,
            status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','archived')),
            created_by VARCHAR(36) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `report_archive` (
            `id` VARCHAR(36) NOT NULL,
            `title` VARCHAR(300) NOT NULL,
            `report_type` ENUM('مالي','إداري','محضر اجتماع','كشف حساب','أخرى') NOT NULL DEFAULT 'أخرى',
            `source` ENUM('manual','import','generated') NOT NULL DEFAULT 'manual',
            `description` TEXT,
            `file_url` VARCHAR(500) DEFAULT NULL,
            `file_type` VARCHAR(20) DEFAULT NULL,
            `report_date` DATE NOT NULL,
            `period_id` VARCHAR(36) DEFAULT NULL,
            `committee_id` VARCHAR(36) DEFAULT NULL,
            `import_stats` JSON DEFAULT NULL,
            `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
            `created_by` VARCHAR(36) DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_archive_type` (`report_type`),
            INDEX `idx_archive_date` (`report_date`),
            INDEX `idx_archive_source` (`source`),
            INDEX `idx_archive_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $checked = true;
}

function archiveToShape(array $row): array
{
    return [
        'id'           => $row['id'],
        'title'        => $row['title'],
        'report_type'  => $row['report_type'] ?? 'أخرى',
        'source'       => $row['source'] ?? 'manual',
        'description'  => $row['description'] ?? '',
        'file_url'     => $row['file_url'] ?? '',
        'file_type'    => $row['file_type'] ?? '',
        'report_date'  => $row['report_date'] ?? '',
        'period_id'    => $row['period_id'] ?? '',
        'committee_id' => $row['committee_id'] ?? '',
        'import_stats' => is_string($row['import_stats'] ?? null)
            ? json_decode($row['import_stats'], true)
            : ($row['import_stats'] ?? null),
        'status'       => $row['status'] ?? 'active',
        'created_by'   => $row['created_by'] ?? '',
        'created_at'   => $row['created_at'] ?? '',
        'updated_at'   => $row['updated_at'] ?? '',
    ];
}

// ---- Handlers ----

function handlePost(): void
{
    $pdo  = getPDO();
    $body = bodyJson();

    $title = sanitizeString($body['title'] ?? '', 'title');
    if (!$title) {
        respond(422, ['error' => 'عنوان التقرير مطلوب.']);
    }

    $reportDate = $body['report_date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$reportDate)) {
        respond(422, ['error' => 'تاريخ التقرير غير صالح.']);
    }

    $validTypes = ['مالي', 'إداري', 'محضر اجتماع', 'كشف حساب', 'أخرى'];
    $reportType = $body['report_type'] ?? 'أخرى';
    if (!in_array($reportType, $validTypes, true)) {
        $reportType = 'أخرى';
    }

    $validSources = ['manual', 'import', 'generated'];
    $source = $body['source'] ?? 'manual';
    if (!in_array($source, $validSources, true)) {
        $source = 'manual';
    }

    $id = uid();
    $importStats = isset($body['import_stats']) && is_array($body['import_stats'])
        ? json_encode($body['import_stats'], JSON_UNESCAPED_UNICODE)
        : null;

    $stmt = $pdo->prepare(
        'INSERT INTO report_archive
            (id, title, report_type, source, description, file_url, file_type,
             report_date, period_id, committee_id, import_stats, status, created_by)
         VALUES
            (:id, :title, :report_type, :source, :description, :file_url, :file_type,
             :report_date, :period_id, :committee_id, :import_stats, :status, :created_by)'
    );

    $stmt->execute([
        ':id'           => $id,
        ':title'        => $title,
        ':report_type'  => $reportType,
        ':source'       => $source,
        ':description'  => sanitizeString($body['description'] ?? '', 'description'),
        ':file_url'     => sanitizeString($body['file_url'] ?? '', 'file_url'),
        ':file_type'    => sanitizeString($body['file_type'] ?? '', 'file_type'),
        ':report_date'  => $reportDate,
        ':period_id'    => $body['period_id'] ?? null,
        ':committee_id' => $body['committee_id'] ?? null,
        ':import_stats' => $importStats,
        ':status'       => 'active',
        ':created_by'   => $_SESSION['awami_user'] ?? 'admin',
    ]);

    logAudit('إضافة', 'أرشيف تقرير', $id, $title);

    respond(201, ['data' => ['id' => $id, 'title' => $title], 'message' => 'تم حفظ التقرير في الأرشيف.']);
}

// ---- Router ----
$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();

try {
    match (true) {
        $method === 'POST' => handlePost(),
        default            => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (\Throwable $e) {
    error_log('Report Archive API error: ' . $e->getMessage());
    respond(500, ['error' => 'حدث خطأ داخلي.']);
}
