<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/audit_helper.php';

startAdminSession();
verifyCsrf();

// ──────────────────────────────────────────────────────────────
// إنشاء جدول قصص الرِّوَاق إن لم يكن موجوداً
// ──────────────────────────────────────────────────────────────

function ensureGalleryStoriesTable(): void
{
    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_stories (
            id TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            subtitle TEXT NOT NULL DEFAULT '',
            type TEXT NOT NULL DEFAULT 'سيرة ذاتية',
            year_range TEXT NOT NULL DEFAULT '',
            quote TEXT NOT NULL DEFAULT '',
            full_text TEXT NOT NULL DEFAULT '',
            author_name TEXT NOT NULL DEFAULT '',
            read_time INTEGER NOT NULL DEFAULT 5,
            color_primary TEXT NOT NULL DEFAULT '#0B3D2E',
            color_secondary TEXT NOT NULL DEFAULT '#1A6B4A',
            color_accent TEXT NOT NULL DEFAULT '#D4AF37',
            display_order INTEGER NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_gs_active_order ON gallery_stories (is_active, display_order)");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `gallery_stories` (
            `id` VARCHAR(64) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `subtitle` VARCHAR(500) NOT NULL DEFAULT '',
            `type` ENUM('سيرة ذاتية','رثاء','قصة نجاح','ذكريات','وصايا') NOT NULL DEFAULT 'سيرة ذاتية',
            `year_range` VARCHAR(100) NOT NULL DEFAULT '',
            `quote` TEXT,
            `full_text` LONGTEXT,
            `author_name` VARCHAR(255) NOT NULL DEFAULT '',
            `read_time` INT NOT NULL DEFAULT 5,
            `color_primary` VARCHAR(7) NOT NULL DEFAULT '#0B3D2E',
            `color_secondary` VARCHAR(7) NOT NULL DEFAULT '#1A6B4A',
            `color_accent` VARCHAR(7) NOT NULL DEFAULT '#D4AF37',
            `display_order` INT NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_gs_active_order` (`is_active`, `display_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

ensureGalleryStoriesTable();

// ──────────────────────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────────────────────

function galleryStoryToShape(array $row): array
{
    return [
        'id'              => $row['id'],
        'title'           => $row['title'],
        'subtitle'        => $row['subtitle']        ?? '',
        'type'            => $row['type']             ?? 'سيرة ذاتية',
        'year_range'      => $row['year_range']       ?? '',
        'quote'           => $row['quote']            ?? '',
        'full_text'       => $row['full_text']        ?? '',
        'author_name'     => $row['author_name']      ?? '',
        'read_time'       => (int) ($row['read_time'] ?? 5),
        'color_primary'   => $row['color_primary']    ?? '#0B3D2E',
        'color_secondary' => $row['color_secondary']  ?? '#1A6B4A',
        'color_accent'    => $row['color_accent']     ?? '#D4AF37',
        'display_order'   => (int) ($row['display_order'] ?? 0),
        'is_active'       => (int) ($row['is_active'] ?? 1),
        'created_at'      => $row['created_at']       ?? '',
        'updated_at'      => $row['updated_at']       ?? '',
    ];
}

// ──────────────────────────────────────────────────────────────
// VALIDATION
// ──────────────────────────────────────────────────────────────

const GS_TYPES = ['سيرة ذاتية', 'رثاء', 'قصة نجاح', 'ذكريات', 'وصايا'];

function validateGalleryStoryPayload(array $data, bool $requireAll = true): array
{
    $fields = [];

    if ($requireAll || array_key_exists('title', $data)) {
        $v = sanitizeString($data['title'] ?? '', 'title');
        if ($requireAll && $v === '') {
            respond(422, ['error' => '"title" مطلوب.']);
        }
        if ($v !== '') $fields['title'] = $v;
    }

    if ($requireAll || array_key_exists('subtitle', $data)) {
        $fields['subtitle'] = sanitizeString($data['subtitle'] ?? '', 'subtitle');
    }

    if ($requireAll || array_key_exists('type', $data)) {
        $v = sanitizeString($data['type'] ?? 'سيرة ذاتية', 'type');
        if (!in_array($v, GS_TYPES, true)) {
            respond(422, ['error' => 'نوع غير صالح. الأنواع المسموحة: ' . implode('، ', GS_TYPES)]);
        }
        $fields['type'] = $v;
    }

    if ($requireAll || array_key_exists('year_range', $data)) {
        $fields['year_range'] = sanitizeString($data['year_range'] ?? '', 'year_range');
    }

    if ($requireAll || array_key_exists('quote', $data)) {
        $fields['quote'] = sanitizeString($data['quote'] ?? '', 'quote');
    }

    if ($requireAll || array_key_exists('full_text', $data)) {
        // full_text يحتوي HTML — لا نستخدم sanitizeString
        $fields['full_text'] = is_string($data['full_text'] ?? '') ? ($data['full_text'] ?? '') : '';
    }

    if ($requireAll || array_key_exists('author_name', $data)) {
        $fields['author_name'] = sanitizeString($data['author_name'] ?? '', 'author_name');
    }

    if ($requireAll || array_key_exists('read_time', $data)) {
        $fields['read_time'] = max(1, (int) ($data['read_time'] ?? 5));
    }

    if ($requireAll || array_key_exists('color_primary', $data)) {
        $fields['color_primary'] = sanitizeString($data['color_primary'] ?? '#0B3D2E', 'color_primary');
    }

    if ($requireAll || array_key_exists('color_secondary', $data)) {
        $fields['color_secondary'] = sanitizeString($data['color_secondary'] ?? '#1A6B4A', 'color_secondary');
    }

    if ($requireAll || array_key_exists('color_accent', $data)) {
        $fields['color_accent'] = sanitizeString($data['color_accent'] ?? '#D4AF37', 'color_accent');
    }

    if ($requireAll || array_key_exists('display_order', $data)) {
        $fields['display_order'] = max(0, (int) ($data['display_order'] ?? 0));
    }

    if ($requireAll || array_key_exists('is_active', $data)) {
        $fields['is_active'] = ((int) ($data['is_active'] ?? 1)) ? 1 : 0;
    }

    return $fields;
}

// ──────────────────────────────────────────────────────────────
// HANDLERS
// ──────────────────────────────────────────────────────────────

function handleGSGetAll(): void
{
    $pdo    = getPDO();
    $where  = [];
    $params = [];

    $isAdmin = isAuthenticated();
    if (!$isAdmin) {
        $where[] = 'is_active = 1';
    } elseif (isset($_GET['active'])) {
        $where[]            = 'is_active = :active';
        $params[':active']  = (int) $_GET['active'];
    }

    if (!empty($_GET['type'])) {
        $where[]          = 'type = :type';
        $params[':type']  = $_GET['type'];
    }

    if (!empty($_GET['search'])) {
        $where[]            = '(title LIKE :search OR subtitle LIKE :search2 OR author_name LIKE :search3)';
        $params[':search']  = '%' . $_GET['search'] . '%';
        $params[':search2'] = '%' . $_GET['search'] . '%';
        $params[':search3'] = '%' . $_GET['search'] . '%';
    }

    $sql = 'SELECT * FROM gallery_stories';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY display_order ASC, created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map('galleryStoryToShape', $stmt->fetchAll());

    respond(200, ['data' => $rows, 'total' => count($rows)]);
}

function handleGSGetOne(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM gallery_stories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'القصة غير موجودة.']);
    }

    if (!$row['is_active'] && !isAuthenticated()) {
        respond(404, ['error' => 'القصة غير موجودة.']);
    }

    respond(200, ['data' => galleryStoryToShape($row)]);
}

function handleGSPost(): void
{
    requireAuth();
    $pdo    = getPDO();
    $data   = bodyJson();
    $fields = validateGalleryStoryPayload($data, requireAll: true);

    $id  = uid();
    $now = date('Y-m-d H:i:s');

    $cols = array_keys($fields);
    $cols[] = 'id';
    $cols[] = 'created_at';
    $cols[] = 'updated_at';

    $placeholders = array_map(fn($c) => ":{$c}", $cols);
    $params = [];
    foreach ($fields as $col => $val) {
        $params[":{$col}"] = $val;
    }
    $params[':id']         = $id;
    $params[':created_at'] = $now;
    $params[':updated_at'] = $now;

    $sql = 'INSERT INTO gallery_stories (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $pdo->prepare($sql)->execute($params);

    logAudit('إضافة', 'رِوَاق', $id, $fields['title'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM gallery_stories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(201, ['data' => galleryStoryToShape($stmt->fetch())]);
}

function handleGSPut(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id, title FROM gallery_stories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        respond(404, ['error' => 'القصة غير موجودة.']);
    }

    $data   = bodyJson();
    $fields = validateGalleryStoryPayload($data, requireAll: false);

    if (empty($fields)) {
        respond(422, ['error' => 'لا توجد حقول للتحديث.']);
    }

    $fields['updated_at'] = date('Y-m-d H:i:s');

    $setClauses = [];
    $params     = [':id' => $id];
    foreach ($fields as $col => $val) {
        $setClauses[]      = "{$col} = :{$col}";
        $params[":{$col}"] = $val;
    }

    $pdo->prepare(
        'UPDATE gallery_stories SET ' . implode(', ', $setClauses) . ' WHERE id = :id'
    )->execute($params);

    logAudit('تعديل', 'رِوَاق', $id, $fields['title'] ?? $existing['title']);

    $stmt = $pdo->prepare('SELECT * FROM gallery_stories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(200, ['data' => galleryStoryToShape($stmt->fetch())]);
}

function handleGSDelete(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT title FROM gallery_stories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'القصة غير موجودة.']);
    }

    $pdo->prepare('DELETE FROM gallery_stories WHERE id = :id')->execute([':id' => $id]);

    logAudit('حذف', 'رِوَاق', $id, $row['title']);

    respond(200, ['message' => "تم حذف القصة '{$row['title']}'."]);
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();

try {
    match (true) {
        $method === 'GET'    && $id === null  => handleGSGetAll(),
        $method === 'GET'    && $id !== null  => handleGSGetOne($id),
        $method === 'POST'                    => handleGSPost(),
        $method === 'PUT'    && $id !== null  => handleGSPut($id),
        $method === 'DELETE' && $id !== null  => handleGSDelete($id),
        $method === 'PUT'    && $id === null  => respond(400, ['error' => '?id= مطلوب لعملية PUT.']),
        $method === 'DELETE' && $id === null  => respond(400, ['error' => '?id= مطلوب لعملية DELETE.']),
        default                               => respond(405, ['error' => 'طريقة غير مسموحة.']),
    };
} catch (PDOException $e) {
    error_log('PDOException in gallery-stories: ' . $e->getMessage());
    respond(500, ['error' => 'خطأ في قاعدة البيانات.']);
}
