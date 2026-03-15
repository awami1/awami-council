<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
require_once __DIR__ . '/validation.php';
verifyCsrf();

// ──────────────────────────────────────────────────────────────
// إنشاء جدول الألبومات إن لم يكن موجوداً
// ──────────────────────────────────────────────────────────────

function ensureAlbumsTable(): void
{
    static $done = false;
    if ($done) return;
    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS albums (
            id TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            cover_url TEXT NOT NULL DEFAULT '',
            date TEXT DEFAULT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `albums` (
            `id` VARCHAR(64) NOT NULL,
            `title` VARCHAR(500) NOT NULL,
            `description` TEXT,
            `cover_url` VARCHAR(500) NOT NULL DEFAULT '',
            `date` DATE DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $done = true;
}

ensureAlbumsTable();

// ──────────────────────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────────────────────

function albumToShape(array $row): array
{
    return [
        'id'          => $row['id'],
        'title'       => $row['title'],
        'description' => $row['description'] ?? '',
        'cover_url'   => $row['cover_url']   ?? '',
        'date'        => $row['date']        ?? null,
        'sort_order'  => (int) ($row['sort_order'] ?? 0),
        'media_count' => (int) ($row['media_count'] ?? 0),
        'created_at'  => $row['created_at']  ?? '',
        'updated_at'  => $row['updated_at']  ?? '',
    ];
}

// ──────────────────────────────────────────────────────────────
// VALIDATION
// ──────────────────────────────────────────────────────────────

function validateAlbumPayload(array $data, bool $requireAll = true): array
{
    $fields = [];

    if ($requireAll || array_key_exists('title', $data)) {
        $v = sanitizeString($data['title'] ?? '', 'title');
        if ($requireAll && $v === '') {
            respond(422, ['error' => '"title" مطلوب.']);
        }
        if ($v !== '') $fields['title'] = $v;
    }

    if ($requireAll || array_key_exists('description', $data)) {
        $fields['description'] = sanitizeString($data['description'] ?? '', 'description');
    }

    if ($requireAll || array_key_exists('cover_url', $data)) {
        $fields['cover_url'] = sanitizeString($data['cover_url'] ?? '', 'cover_url');
    }

    if ($requireAll || array_key_exists('date', $data)) {
        $v = sanitizeString($data['date'] ?? '', 'date');
        $fields['date'] = $v !== '' ? $v : null;
    }

    if ($requireAll || array_key_exists('sort_order', $data)) {
        $fields['sort_order'] = (int) ($data['sort_order'] ?? 0);
    }

    return $fields;
}

// ──────────────────────────────────────────────────────────────
// HANDLERS
// ──────────────────────────────────────────────────────────────

function handleAlbumsGetAll(): void
{
    $pdo    = getPDO();
    $where  = [];
    $params = [];

    if (!empty($_GET['search'])) {
        $where[]           = '(a.title LIKE :search OR a.description LIKE :search2)';
        $params[':search'] = '%' . $_GET['search'] . '%';
        $params[':search2'] = '%' . $_GET['search'] . '%';
    }

    $sql = 'SELECT a.*, (SELECT COUNT(*) FROM media m WHERE m.album_id = a.id) AS media_count FROM albums a';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY a.sort_order ASC, a.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map('albumToShape', $stmt->fetchAll());

    respond(200, ['data' => $rows, 'total' => count($rows)]);
}

function handleAlbumsGetOne(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT a.*, (SELECT COUNT(*) FROM media m WHERE m.album_id = a.id) AS media_count FROM albums a WHERE a.id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'الألبوم غير موجود.']);
    }

    $album = albumToShape($row);

    // جلب الميديا داخل الألبوم
    $mediaStmt = $pdo->prepare('SELECT * FROM media WHERE album_id = :album_id ORDER BY sort_order ASC, created_at DESC');
    $mediaStmt->execute([':album_id' => $id]);
    $mediaRows = $mediaStmt->fetchAll();

    $album['media'] = array_map(function ($r) {
        return [
            'id'         => $r['id'],
            'album_id'   => $r['album_id'],
            'title'      => $r['title'],
            'type'       => $r['type']       ?? 'images',
            'url'        => $r['url'],
            'date'       => $r['date']       ?? null,
            'tags'       => json_decode($r['tags'] ?? '[]', true) ?: [],
            'sort_order' => (int) ($r['sort_order'] ?? 0),
            'created_at' => $r['created_at'] ?? '',
            'updated_at' => $r['updated_at'] ?? '',
        ];
    }, $mediaRows);

    respond(200, ['data' => $album]);
}

function handleAlbumsPost(): void
{
    requireAuth();
    $pdo    = getPDO();
    $data   = bodyJson();
    $fields = validateAlbumPayload($data, requireAll: true);

    $id = isset($data['id']) && preg_match('/^[a-zA-Z0-9_-]{1,64}$/', (string) $data['id'])
        ? $data['id']
        : uid();

    $now = date('Y-m-d H:i:s');

    $pdo->prepare(
        'INSERT INTO albums (id, title, description, cover_url, date, sort_order, created_at, updated_at)
         VALUES (:id, :title, :description, :cover_url, :date, :sort_order, :created_at, :updated_at)'
    )->execute([
        ':id'          => $id,
        ':title'       => $fields['title'],
        ':description' => $fields['description'] ?? '',
        ':cover_url'   => $fields['cover_url']   ?? '',
        ':date'        => $fields['date']         ?? null,
        ':sort_order'  => $fields['sort_order']   ?? 0,
        ':created_at'  => $now,
        ':updated_at'  => $now,
    ]);

    logAudit('إضافة', 'ألبوم', $id, $fields['title']);

    $stmt = $pdo->prepare('SELECT a.*, (SELECT COUNT(*) FROM media m WHERE m.album_id = a.id) AS media_count FROM albums a WHERE a.id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(201, ['data' => albumToShape($stmt->fetch())]);
}

function handleAlbumsPut(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id, title FROM albums WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        respond(404, ['error' => 'الألبوم غير موجود.']);
    }

    $data   = bodyJson();
    $fields = validateAlbumPayload($data, requireAll: false);

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
        'UPDATE albums SET ' . implode(', ', $setClauses) . ' WHERE id = :id'
    )->execute($params);

    logAudit('تعديل', 'ألبوم', $id, $fields['title'] ?? '');

    $stmt = $pdo->prepare('SELECT a.*, (SELECT COUNT(*) FROM media m WHERE m.album_id = a.id) AS media_count FROM albums a WHERE a.id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(200, ['data' => albumToShape($stmt->fetch())]);
}

function handleAlbumsDelete(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT title FROM albums WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'الألبوم غير موجود.']);
    }

    // الميديا لا تُحذف — فقط album_id يصير NULL
    $pdo->prepare('UPDATE media SET album_id = NULL WHERE album_id = :id')->execute([':id' => $id]);
    $pdo->prepare('DELETE FROM albums WHERE id = :id')->execute([':id' => $id]);

    logAudit('حذف', 'ألبوم', $id, $row['title']);

    respond(200, ['message' => "تم حذف الألبوم '{$row['title']}'."]);
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();

try {
    match (true) {
        $method === 'GET'    && $id === null  => handleAlbumsGetAll(),
        $method === 'GET'    && $id !== null  => handleAlbumsGetOne($id),
        $method === 'POST'                    => handleAlbumsPost(),
        $method === 'PUT'    && $id !== null  => handleAlbumsPut($id),
        $method === 'DELETE' && $id !== null  => handleAlbumsDelete($id),
        $method === 'PUT'    && $id === null  => respond(400, ['error' => '?id= مطلوب لعملية التعديل.']),
        $method === 'DELETE' && $id === null  => respond(400, ['error' => '?id= مطلوب لعملية الحذف.']),
        default                               => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (PDOException $e) {
    error_log('PDOException in albums: ' . $e->getMessage());
    respond(500, ['error' => 'خطأ في قاعدة البيانات.']);
}
