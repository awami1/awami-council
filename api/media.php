<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
require_once __DIR__ . '/validation.php';
verifyCsrf();

// ──────────────────────────────────────────────────────────────
// إنشاء الجداول (albums أولاً بسبب FK)
// ──────────────────────────────────────────────────────────────

// إنشاء جدول albums إذا لم يكن موجوداً (مطلوب قبل media بسبب FK)
if (!function_exists('ensureAlbumsTable')) {
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
}

ensureAlbumsTable();

function ensureMediaTable(): void
{
    static $done = false;
    if ($done) return;
    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS media (
            id TEXT PRIMARY KEY,
            album_id TEXT DEFAULT NULL,
            title TEXT NOT NULL,
            type TEXT NOT NULL DEFAULT 'images',
            url TEXT NOT NULL,
            date TEXT DEFAULT NULL,
            tags TEXT NOT NULL DEFAULT '[]',
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE SET NULL
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `media` (
            `id` VARCHAR(64) NOT NULL,
            `album_id` VARCHAR(64) DEFAULT NULL,
            `title` VARCHAR(500) NOT NULL,
            `type` ENUM('images','videos','youtube') NOT NULL DEFAULT 'images',
            `url` VARCHAR(500) NOT NULL,
            `date` DATE DEFAULT NULL,
            `tags` JSON DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`album_id`) REFERENCES `albums`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $done = true;
}

ensureMediaTable();

// ──────────────────────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────────────────────

const MEDIA_TYPES = ['images', 'videos', 'youtube'];

function mediaToShape(array $row): array
{
    $tags = $row['tags'] ?? '[]';
    if (is_string($tags)) {
        $tags = json_decode($tags, true) ?: [];
    }
    return [
        'id'         => $row['id'],
        'album_id'   => $row['album_id'] ?? null,
        'title'      => $row['title'],
        'type'       => $row['type']       ?? 'images',
        'url'        => $row['url'],
        'date'       => $row['date']       ?? null,
        'tags'       => $tags,
        'sort_order' => (int) ($row['sort_order'] ?? 0),
        'created_at' => $row['created_at'] ?? '',
        'updated_at' => $row['updated_at'] ?? '',
    ];
}

// ──────────────────────────────────────────────────────────────
// VALIDATION
// ──────────────────────────────────────────────────────────────

function validateMediaPayload(array $data, bool $requireAll = true): array
{
    $fields = [];

    if ($requireAll || array_key_exists('title', $data)) {
        $v = sanitizeString($data['title'] ?? '', 'title');
        if ($requireAll && $v === '') {
            respond(422, ['error' => '"title" مطلوب.']);
        }
        if ($v !== '') $fields['title'] = $v;
    }

    if ($requireAll || array_key_exists('url', $data)) {
        $v = sanitizeString($data['url'] ?? '', 'url');
        if ($requireAll && $v === '') {
            respond(422, ['error' => '"url" مطلوب.']);
        }
        if ($v !== '') $fields['url'] = $v;
    }

    if ($requireAll || array_key_exists('type', $data)) {
        $v = sanitizeString($data['type'] ?? 'images', 'type');
        if (!in_array($v, MEDIA_TYPES, true)) {
            respond(422, ['error' => '"type" يجب أن يكون أحد: ' . implode('، ', MEDIA_TYPES)]);
        }
        $fields['type'] = $v;
    }

    if ($requireAll || array_key_exists('album_id', $data)) {
        $v = $data['album_id'] ?? null;
        if ($v !== null && $v !== '') {
            $v = sanitizeString($v, 'album_id');
            $fields['album_id'] = $v;
        } else {
            $fields['album_id'] = null;
        }
    }

    if ($requireAll || array_key_exists('date', $data)) {
        $v = sanitizeString($data['date'] ?? '', 'date');
        $fields['date'] = $v !== '' ? $v : null;
    }

    if ($requireAll || array_key_exists('tags', $data)) {
        $tags = $data['tags'] ?? [];
        if (is_string($tags)) {
            $tags = json_decode($tags, true) ?: [];
        }
        $fields['tags'] = json_encode(is_array($tags) ? $tags : [], JSON_UNESCAPED_UNICODE);
    }

    if ($requireAll || array_key_exists('sort_order', $data)) {
        $fields['sort_order'] = (int) ($data['sort_order'] ?? 0);
    }

    return $fields;
}

// ──────────────────────────────────────────────────────────────
// HANDLERS
// ──────────────────────────────────────────────────────────────

function handleMediaGetAll(): void
{
    $pdo    = getPDO();
    $where  = [];
    $params = [];

    if (!empty($_GET['album_id'])) {
        $where[]              = 'album_id = :album_id';
        $params[':album_id']  = $_GET['album_id'];
    }

    if (!empty($_GET['type'])) {
        $t = $_GET['type'];
        if (in_array($t, MEDIA_TYPES, true)) {
            $where[]          = 'type = :type';
            $params[':type']  = $t;
        }
    }

    if (!empty($_GET['search'])) {
        $where[]             = '(title LIKE :search)';
        $params[':search']   = '%' . $_GET['search'] . '%';
    }

    $sql = 'SELECT * FROM media';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY sort_order ASC, created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map('mediaToShape', $stmt->fetchAll());

    respond(200, ['data' => $rows, 'total' => count($rows)]);
}

function handleMediaGetOne(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM media WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'العنصر غير موجود.']);
    }

    respond(200, ['data' => mediaToShape($row)]);
}

function handleMediaPost(): void
{
    requireAuth();

    // Bulk insert
    if (!empty($_GET['bulk'])) {
        handleMediaBulkPost();
        return;
    }

    $pdo    = getPDO();
    $data   = bodyJson();
    $fields = validateMediaPayload($data, requireAll: true);

    $id = isset($data['id']) && preg_match('/^[a-zA-Z0-9_-]{1,64}$/', (string) $data['id'])
        ? $data['id']
        : uid();

    $now = date('Y-m-d H:i:s');

    $pdo->prepare(
        'INSERT INTO media (id, album_id, title, type, url, date, tags, sort_order, created_at, updated_at)
         VALUES (:id, :album_id, :title, :type, :url, :date, :tags, :sort_order, :created_at, :updated_at)'
    )->execute([
        ':id'         => $id,
        ':album_id'   => $fields['album_id'] ?? null,
        ':title'      => $fields['title'],
        ':type'       => $fields['type']       ?? 'images',
        ':url'        => $fields['url'],
        ':date'       => $fields['date']       ?? null,
        ':tags'       => $fields['tags']       ?? '[]',
        ':sort_order' => $fields['sort_order'] ?? 0,
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);

    logAudit('إضافة', 'ميديا', $id, $fields['title']);

    $stmt = $pdo->prepare('SELECT * FROM media WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(201, ['data' => mediaToShape($stmt->fetch())]);
}

function handleMediaBulkPost(): void
{
    $pdo  = getPDO();
    $body = bodyJson();
    $items = $body['items'] ?? [];

    if (empty($items) || !is_array($items)) {
        respond(422, ['error' => '"items" مطلوب كمصفوفة.']);
    }

    $sharedAlbumId = $body['album_id'] ?? null;
    $sharedDate    = $body['date']     ?? null;
    $sharedTags    = $body['tags']     ?? [];

    $created = [];
    $now = date('Y-m-d H:i:s');

    foreach ($items as $item) {
        if (!is_array($item)) continue;

        // دمج القيم المشتركة مع قيم العنصر
        if (!isset($item['album_id']) && $sharedAlbumId) $item['album_id'] = $sharedAlbumId;
        if (!isset($item['date'])     && $sharedDate)    $item['date']     = $sharedDate;
        if (!isset($item['tags'])     && $sharedTags)    $item['tags']     = $sharedTags;

        $fields = validateMediaPayload($item, requireAll: true);
        $id = uid();

        $pdo->prepare(
            'INSERT INTO media (id, album_id, title, type, url, date, tags, sort_order, created_at, updated_at)
             VALUES (:id, :album_id, :title, :type, :url, :date, :tags, :sort_order, :created_at, :updated_at)'
        )->execute([
            ':id'         => $id,
            ':album_id'   => $fields['album_id'] ?? null,
            ':title'      => $fields['title'],
            ':type'       => $fields['type']       ?? 'images',
            ':url'        => $fields['url'],
            ':date'       => $fields['date']       ?? null,
            ':tags'       => $fields['tags']       ?? '[]',
            ':sort_order' => $fields['sort_order'] ?? 0,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        logAudit('إضافة', 'ميديا', $id, $fields['title']);

        $stmt = $pdo->prepare('SELECT * FROM media WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $created[] = mediaToShape($stmt->fetch());
    }

    respond(201, ['data' => $created, 'total' => count($created)]);
}

function handleMediaPut(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id, title FROM media WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        respond(404, ['error' => 'العنصر غير موجود.']);
    }

    $data   = bodyJson();
    $fields = validateMediaPayload($data, requireAll: false);

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
        'UPDATE media SET ' . implode(', ', $setClauses) . ' WHERE id = :id'
    )->execute($params);

    logAudit('تعديل', 'ميديا', $id, $fields['title'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM media WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(200, ['data' => mediaToShape($stmt->fetch())]);
}

function handleMediaDelete(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT title FROM media WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'العنصر غير موجود.']);
    }

    $pdo->prepare('DELETE FROM media WHERE id = :id')->execute([':id' => $id]);

    logAudit('حذف', 'ميديا', $id, $row['title']);

    respond(200, ['message' => "تم حذف '{$row['title']}'."]);
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();

try {
    match (true) {
        $method === 'GET'    && $id === null  => handleMediaGetAll(),
        $method === 'GET'    && $id !== null  => handleMediaGetOne($id),
        $method === 'POST'                    => handleMediaPost(),
        $method === 'PUT'    && $id !== null  => handleMediaPut($id),
        $method === 'DELETE' && $id !== null  => handleMediaDelete($id),
        $method === 'PUT'    && $id === null  => respond(400, ['error' => '?id= مطلوب لعملية التعديل.']),
        $method === 'DELETE' && $id === null  => respond(400, ['error' => '?id= مطلوب لعملية الحذف.']),
        default                               => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (PDOException $e) {
    error_log('PDOException in media: ' . $e->getMessage());
    respond(500, ['error' => 'خطأ في قاعدة البيانات.']);
}
