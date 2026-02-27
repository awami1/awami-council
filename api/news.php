<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';

// ──────────────────────────────────────────────────────────────
// إنشاء جدول الأخبار إن لم يكن موجوداً
// ──────────────────────────────────────────────────────────────

function ensureNewsTable(): void
{
    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS news (
            id TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            content TEXT NOT NULL DEFAULT '',
            excerpt TEXT NOT NULL DEFAULT '',
            image TEXT NOT NULL DEFAULT '',
            category TEXT NOT NULL DEFAULT 'عام',
            author TEXT NOT NULL DEFAULT '',
            status TEXT NOT NULL DEFAULT 'published',
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `news` (
            `id` VARCHAR(64) NOT NULL,
            `title` VARCHAR(500) NOT NULL,
            `content` TEXT,
            `excerpt` VARCHAR(500) NOT NULL DEFAULT '',
            `image` VARCHAR(500) NOT NULL DEFAULT '',
            `category` VARCHAR(100) NOT NULL DEFAULT 'عام',
            `author` VARCHAR(200) NOT NULL DEFAULT '',
            `status` ENUM('published','draft') NOT NULL DEFAULT 'published',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

ensureNewsTable();

// ──────────────────────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────────────────────

function parseNewsId(): ?string
{
    $id = $_GET['id'] ?? null;
    if ($id !== null && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id)) {
        respond(400, ['error' => 'Invalid ID format.']);
    }
    return $id;
}

function sanitize(mixed $value, string $field): string
{
    if (!is_string($value) && !is_numeric($value)) {
        respond(422, ['error' => "Field '{$field}' must be a string."]);
    }
    return trim((string) $value);
}

function newsToShape(array $row): array
{
    return [
        'id'         => $row['id'],
        'title'      => $row['title'],
        'content'    => $row['content']    ?? '',
        'excerpt'    => $row['excerpt']    ?? '',
        'image'      => $row['image']      ?? '',
        'category'   => $row['category']   ?? 'عام',
        'author'     => $row['author']     ?? '',
        'status'     => $row['status']     ?? 'published',
        'created_at' => $row['created_at'] ?? '',
        'updated_at' => $row['updated_at'] ?? '',
    ];
}

// ──────────────────────────────────────────────────────────────
// VALIDATION
// ──────────────────────────────────────────────────────────────

const NEWS_STATUSES   = ['published', 'draft'];
const NEWS_CATEGORIES = ['عام', 'فعاليات', 'إعلانات', 'اجتماعات', 'مالية', 'اجتماعية'];

function validateNewsPayload(array $data, bool $requireAll = true): array
{
    $fields = [];

    if ($requireAll || array_key_exists('title', $data)) {
        $v = sanitize($data['title'] ?? '', 'title');
        if ($requireAll && $v === '') {
            respond(422, ['error' => '"title" مطلوب.']);
        }
        if ($v !== '') $fields['title'] = $v;
    }

    if ($requireAll || array_key_exists('content', $data)) {
        $fields['content'] = sanitize($data['content'] ?? '', 'content');
    }

    if ($requireAll || array_key_exists('excerpt', $data)) {
        $fields['excerpt'] = sanitize($data['excerpt'] ?? '', 'excerpt');
    }

    if ($requireAll || array_key_exists('image', $data)) {
        $fields['image'] = sanitize($data['image'] ?? '', 'image');
    }

    if ($requireAll || array_key_exists('category', $data)) {
        $v = sanitize($data['category'] ?? 'عام', 'category');
        $fields['category'] = $v;
    }

    if ($requireAll || array_key_exists('author', $data)) {
        $fields['author'] = sanitize($data['author'] ?? '', 'author');
    }

    if ($requireAll || array_key_exists('status', $data)) {
        $v = sanitize($data['status'] ?? 'published', 'status');
        if (!in_array($v, NEWS_STATUSES, true)) {
            respond(422, ['error' => '"status" must be one of: ' . implode(', ', NEWS_STATUSES)]);
        }
        $fields['status'] = $v;
    }

    return $fields;
}

// ──────────────────────────────────────────────────────────────
// HANDLERS
// ──────────────────────────────────────────────────────────────

function handleNewsGetAll(): void
{
    $pdo    = getPDO();
    $where  = [];
    $params = [];

    // الزوار يرون المنشور فقط، المشرفون يرون الكل
    $isAdmin = isAuthenticated();
    if (!$isAdmin) {
        $where[] = "status = 'published'";
    } elseif (!empty($_GET['status'])) {
        $s = $_GET['status'];
        if (in_array($s, NEWS_STATUSES, true)) {
            $where[]           = 'status = :status';
            $params[':status'] = $s;
        }
    }

    if (!empty($_GET['category'])) {
        $where[]             = 'category = :category';
        $params[':category'] = $_GET['category'];
    }

    if (!empty($_GET['search'])) {
        $where[]           = '(title LIKE :search OR content LIKE :search2)';
        $params[':search'] = '%' . $_GET['search'] . '%';
        $params[':search2'] = '%' . $_GET['search'] . '%';
    }

    $sql = 'SELECT * FROM news';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map('newsToShape', $stmt->fetchAll());

    respond(200, ['data' => $rows, 'total' => count($rows)]);
}

function handleNewsGetOne(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'الخبر غير موجود.']);
    }

    // الزوار لا يرون المسودات
    if ($row['status'] !== 'published' && !isAuthenticated()) {
        respond(404, ['error' => 'الخبر غير موجود.']);
    }

    respond(200, ['data' => newsToShape($row)]);
}

function handleNewsPost(): void
{
    requireAuth();
    $pdo    = getPDO();
    $data   = bodyJson();
    $fields = validateNewsPayload($data, requireAll: true);

    $id = isset($data['id']) && preg_match('/^[a-zA-Z0-9_-]{1,64}$/', (string) $data['id'])
        ? $data['id']
        : uid();

    $now = date('Y-m-d H:i:s');

    $pdo->prepare(
        'INSERT INTO news (id, title, content, excerpt, image, category, author, status, created_at, updated_at)
         VALUES (:id, :title, :content, :excerpt, :image, :category, :author, :status, :created_at, :updated_at)'
    )->execute([
        ':id'         => $id,
        ':title'      => $fields['title'],
        ':content'    => $fields['content'],
        ':excerpt'    => $fields['excerpt'],
        ':image'      => $fields['image'],
        ':category'   => $fields['category'],
        ':author'     => $fields['author'],
        ':status'     => $fields['status'],
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);

    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(201, ['data' => newsToShape($stmt->fetch())]);
}

function handleNewsPut(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM news WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        respond(404, ['error' => 'الخبر غير موجود.']);
    }

    $data   = bodyJson();
    $fields = validateNewsPayload($data, requireAll: false);

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
        'UPDATE news SET ' . implode(', ', $setClauses) . ' WHERE id = :id'
    )->execute($params);

    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(200, ['data' => newsToShape($stmt->fetch())]);
}

function handleNewsDelete(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT title FROM news WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'الخبر غير موجود.']);
    }

    $pdo->prepare('DELETE FROM news WHERE id = :id')->execute([':id' => $id]);

    respond(200, ['message' => "تم حذف الخبر '{$row['title']}'."]);
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseNewsId();

try {
    match (true) {
        $method === 'GET'    && $id === null  => handleNewsGetAll(),
        $method === 'GET'    && $id !== null  => handleNewsGetOne($id),
        $method === 'POST'                    => handleNewsPost(),
        $method === 'PUT'    && $id !== null  => handleNewsPut($id),
        $method === 'DELETE' && $id !== null  => handleNewsDelete($id),
        $method === 'PUT'    && $id === null  => respond(400, ['error' => '?id= required for PUT.']),
        $method === 'DELETE' && $id === null  => respond(400, ['error' => '?id= required for DELETE.']),
        default                               => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error.', 'detail' => $e->getMessage()]);
}
