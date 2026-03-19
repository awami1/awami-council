<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/validation.php';

// رفع الصور لا يستخدم JSON body بل FormData — لذا نتحقق من CSRF فقط للطلبات غير-upload
$isUpload = ($_SERVER['REQUEST_METHOD'] === 'POST') && !empty($_GET['upload']);
if (!$isUpload) {
    verifyCsrf();
}

// ──────────────────────────────────────────────────────────────
// إنشاء جدول سِيَر وقصص أبناء العائلة
// ──────────────────────────────────────────────────────────────

function ensureStoriesTable(): void
{
    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS stories (
            id TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            category TEXT NOT NULL DEFAULT 'biography',
            excerpt TEXT NOT NULL DEFAULT '',
            content TEXT NOT NULL DEFAULT '',
            cover_image TEXT NOT NULL DEFAULT '',
            person_name TEXT NOT NULL DEFAULT '',
            person_image TEXT NOT NULL DEFAULT '',
            person_bio TEXT NOT NULL DEFAULT '',
            person_status TEXT NOT NULL DEFAULT 'alive',
            author_name TEXT NOT NULL DEFAULT '',
            status TEXT NOT NULL DEFAULT 'draft',
            is_pinned INTEGER NOT NULL DEFAULT 0,
            published_at TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `stories` (
            `id` VARCHAR(64) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL,
            `category` VARCHAR(50) NOT NULL DEFAULT 'biography',
            `excerpt` TEXT,
            `content` LONGTEXT,
            `cover_image` VARCHAR(500) NOT NULL DEFAULT '',
            `person_name` VARCHAR(255) NOT NULL DEFAULT '',
            `person_image` VARCHAR(500) NOT NULL DEFAULT '',
            `person_bio` TEXT,
            `person_status` ENUM('deceased','alive') NOT NULL DEFAULT 'alive',
            `author_name` VARCHAR(255) NOT NULL DEFAULT '',
            `status` ENUM('draft','published') NOT NULL DEFAULT 'draft',
            `is_pinned` TINYINT NOT NULL DEFAULT 0,
            `published_at` DATETIME NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

ensureStoriesTable();

// ──────────────────────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────────────────────

function storyToShape(array $row): array
{
    return [
        'id'            => $row['id'],
        'title'         => $row['title'],
        'slug'          => $row['slug']          ?? '',
        'category'      => $row['category']      ?? 'biography',
        'excerpt'       => $row['excerpt']        ?? '',
        'content'       => $row['content']        ?? '',
        'cover_image'   => $row['cover_image']    ?? '',
        'person_name'   => $row['person_name']    ?? '',
        'person_image'  => $row['person_image']   ?? '',
        'person_bio'    => $row['person_bio']     ?? '',
        'person_status' => $row['person_status']  ?? 'alive',
        'author_name'   => $row['author_name']    ?? '',
        'status'        => $row['status']         ?? 'draft',
        'is_pinned'     => (int)($row['is_pinned'] ?? 0),
        'published_at'  => $row['published_at']   ?? null,
        'created_at'    => $row['created_at']     ?? '',
        'updated_at'    => $row['updated_at']     ?? '',
    ];
}

/**
 * توليد slug من العنوان (يدعم العربية والإنجليزية)
 */
function generateSlug(string $title): string
{
    $slug = mb_strtolower($title, 'UTF-8');
    // استبدال المسافات بشرطة
    $slug = preg_replace('/\s+/u', '-', $slug);
    // حذف الأحرف غير المسموحة (نبقي على العربية والإنجليزية والأرقام والشرطة)
    $slug = preg_replace('/[^\p{L}\p{N}\-]/u', '', $slug);
    // حذف الشرطات المتكررة
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    if ($slug === '') {
        $slug = uid();
    }

    return $slug;
}

/**
 * التأكد من عدم تكرار الـ slug
 */
function ensureUniqueSlug(string $slug, ?string $excludeId = null): string
{
    $pdo = getPDO();
    $baseSlug = $slug;
    $counter = 1;

    while (true) {
        $sql = 'SELECT id FROM stories WHERE slug = :slug';
        $params = [':slug' => $slug];
        if ($excludeId) {
            $sql .= ' AND id != :eid';
            $params[':eid'] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) break;
        $slug = $baseSlug . '-' . (++$counter);
    }

    return $slug;
}

// ──────────────────────────────────────────────────────────────
// VALIDATION
// ──────────────────────────────────────────────────────────────

const STORY_STATUSES    = ['published', 'draft'];
const STORY_CATEGORIES  = ['biography', 'self_made', 'eulogy', 'tribute', 'other'];
const PERSON_STATUSES   = ['deceased', 'alive'];

function validateStoryPayload(array $data, bool $requireAll = true): array
{
    $fields = [];

    if ($requireAll || array_key_exists('title', $data)) {
        $v = sanitizeString($data['title'] ?? '', 'title');
        if ($requireAll && $v === '') {
            respond(422, ['error' => 'عنوان الموضوع مطلوب.']);
        }
        if ($v !== '') $fields['title'] = $v;
    }

    if ($requireAll || array_key_exists('category', $data)) {
        $v = sanitizeString($data['category'] ?? 'biography', 'category');
        if (!in_array($v, STORY_CATEGORIES, true)) {
            respond(422, ['error' => 'التصنيف غير صالح.']);
        }
        $fields['category'] = $v;
    }

    if ($requireAll || array_key_exists('excerpt', $data)) {
        $fields['excerpt'] = sanitizeString($data['excerpt'] ?? '', 'excerpt');
    }

    // المحتوى HTML — لا نستخدم sanitizeString لأنه يحتوي على HTML من المحرر
    if ($requireAll || array_key_exists('content', $data)) {
        $fields['content'] = is_string($data['content'] ?? '') ? trim($data['content'] ?? '') : '';
    }

    if ($requireAll || array_key_exists('cover_image', $data)) {
        $fields['cover_image'] = sanitizeString($data['cover_image'] ?? '', 'cover_image');
    }

    if ($requireAll || array_key_exists('person_name', $data)) {
        $fields['person_name'] = sanitizeString($data['person_name'] ?? '', 'person_name');
    }

    if ($requireAll || array_key_exists('person_image', $data)) {
        $fields['person_image'] = sanitizeString($data['person_image'] ?? '', 'person_image');
    }

    if ($requireAll || array_key_exists('person_bio', $data)) {
        $fields['person_bio'] = sanitizeString($data['person_bio'] ?? '', 'person_bio');
    }

    if ($requireAll || array_key_exists('person_status', $data)) {
        $v = sanitizeString($data['person_status'] ?? 'alive', 'person_status');
        if (!in_array($v, PERSON_STATUSES, true)) {
            respond(422, ['error' => 'حالة الشخصية غير صالحة.']);
        }
        $fields['person_status'] = $v;
    }

    if ($requireAll || array_key_exists('author_name', $data)) {
        $fields['author_name'] = sanitizeString($data['author_name'] ?? '', 'author_name');
    }

    if ($requireAll || array_key_exists('status', $data)) {
        $v = sanitizeString($data['status'] ?? 'draft', 'status');
        if (!in_array($v, STORY_STATUSES, true)) {
            respond(422, ['error' => 'حالة النشر غير صالحة.']);
        }
        $fields['status'] = $v;
    }

    if (array_key_exists('is_pinned', $data)) {
        $fields['is_pinned'] = $data['is_pinned'] ? 1 : 0;
    }

    if (array_key_exists('published_at', $data)) {
        $v = is_string($data['published_at']) ? trim($data['published_at']) : '';
        $fields['published_at'] = $v ?: null;
    }

    return $fields;
}

// ──────────────────────────────────────────────────────────────
// HANDLERS
// ──────────────────────────────────────────────────────────────

function handleStoriesGetAll(): void
{
    $pdo    = getPDO();
    $where  = [];
    $params = [];

    // الزوار يرون المنشور فقط
    $isAdmin = isAuthenticated();
    if (!$isAdmin) {
        $where[] = "status = 'published'";
    } elseif (!empty($_GET['status'])) {
        $s = $_GET['status'];
        if (in_array($s, STORY_STATUSES, true)) {
            $where[]           = 'status = :status';
            $params[':status'] = $s;
        }
    }

    if (!empty($_GET['category'])) {
        $c = $_GET['category'];
        if (in_array($c, STORY_CATEGORIES, true)) {
            $where[]             = 'category = :category';
            $params[':category'] = $c;
        }
    }

    if (!empty($_GET['person_status'])) {
        $ps = $_GET['person_status'];
        if (in_array($ps, PERSON_STATUSES, true)) {
            $where[]                 = 'person_status = :person_status';
            $params[':person_status'] = $ps;
        }
    }

    if (!empty($_GET['search'])) {
        $where[]           = '(title LIKE :search OR person_name LIKE :search2 OR excerpt LIKE :search3)';
        $params[':search']  = '%' . $_GET['search'] . '%';
        $params[':search2'] = '%' . $_GET['search'] . '%';
        $params[':search3'] = '%' . $_GET['search'] . '%';
    }

    $sql = 'SELECT * FROM stories';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    // المثبتة أولاً ثم الأحدث
    $sql .= ' ORDER BY is_pinned DESC, published_at DESC, created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map('storyToShape', $stmt->fetchAll());

    respond(200, ['data' => $rows, 'total' => count($rows)]);
}

function handleStoriesGetOne(string $id): void
{
    $pdo = getPDO();

    // البحث بالـ id أو بالـ slug
    $stmt = $pdo->prepare('SELECT * FROM stories WHERE id = :id OR slug = :slug LIMIT 1');
    $stmt->execute([':id' => $id, ':slug' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'الموضوع غير موجود.']);
    }

    // الزوار لا يرون المسودات
    if ($row['status'] !== 'published' && !isAuthenticated()) {
        respond(404, ['error' => 'الموضوع غير موجود.']);
    }

    respond(200, ['data' => storyToShape($row)]);
}

function handleStoriesPost(): void
{
    requireAuth();
    $pdo    = getPDO();
    $data   = bodyJson();
    $fields = validateStoryPayload($data, requireAll: true);

    $id = uid();
    $slug = ensureUniqueSlug(generateSlug($fields['title']));

    $now = date('Y-m-d H:i:s');
    $publishedAt = ($fields['status'] === 'published')
        ? ($fields['published_at'] ?? $now)
        : ($fields['published_at'] ?? null);

    $pdo->prepare(
        'INSERT INTO stories (id, title, slug, category, excerpt, content, cover_image, person_name, person_image, person_bio, person_status, author_name, status, is_pinned, published_at, created_at, updated_at)
         VALUES (:id, :title, :slug, :category, :excerpt, :content, :cover_image, :person_name, :person_image, :person_bio, :person_status, :author_name, :status, :is_pinned, :published_at, :created_at, :updated_at)'
    )->execute([
        ':id'            => $id,
        ':title'         => $fields['title'],
        ':slug'          => $slug,
        ':category'      => $fields['category'],
        ':excerpt'       => $fields['excerpt'],
        ':content'       => $fields['content'],
        ':cover_image'   => $fields['cover_image'],
        ':person_name'   => $fields['person_name'],
        ':person_image'  => $fields['person_image'],
        ':person_bio'    => $fields['person_bio'],
        ':person_status' => $fields['person_status'],
        ':author_name'   => $fields['author_name'],
        ':status'        => $fields['status'],
        ':is_pinned'     => $fields['is_pinned'] ?? 0,
        ':published_at'  => $publishedAt,
        ':created_at'    => $now,
        ':updated_at'    => $now,
    ]);

    $stmt = $pdo->prepare('SELECT * FROM stories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(201, ['data' => storyToShape($stmt->fetch())]);
}

function handleStoriesPut(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM stories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        respond(404, ['error' => 'الموضوع غير موجود.']);
    }

    $data   = bodyJson();
    $fields = validateStoryPayload($data, requireAll: false);

    if (empty($fields)) {
        respond(422, ['error' => 'لا توجد حقول للتحديث.']);
    }

    // إذا تغير العنوان، نحدث الـ slug
    if (isset($fields['title']) && $fields['title'] !== $existing['title']) {
        $fields['slug'] = ensureUniqueSlug(generateSlug($fields['title']), $id);
    }

    // إذا تم النشر الآن ولم يكن منشوراً
    if (isset($fields['status']) && $fields['status'] === 'published' && $existing['status'] !== 'published') {
        if (!isset($fields['published_at']) && empty($existing['published_at'])) {
            $fields['published_at'] = date('Y-m-d H:i:s');
        }
    }

    $fields['updated_at'] = date('Y-m-d H:i:s');

    $setClauses = [];
    $params     = [':id' => $id];
    foreach ($fields as $col => $val) {
        $setClauses[]      = "{$col} = :{$col}";
        $params[":{$col}"] = $val;
    }

    $pdo->prepare(
        'UPDATE stories SET ' . implode(', ', $setClauses) . ' WHERE id = :id'
    )->execute($params);

    $stmt = $pdo->prepare('SELECT * FROM stories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(200, ['data' => storyToShape($stmt->fetch())]);
}

function handleStoriesDelete(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT title FROM stories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'الموضوع غير موجود.']);
    }

    $pdo->prepare('DELETE FROM stories WHERE id = :id')->execute([':id' => $id]);

    respond(200, ['message' => "تم حذف الموضوع '{$row['title']}'."]);
}

function handleStoriesTogglePin(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id, is_pinned FROM stories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'الموضوع غير موجود.']);
    }

    $newVal = $row['is_pinned'] ? 0 : 1;
    $pdo->prepare('UPDATE stories SET is_pinned = :pinned, updated_at = :now WHERE id = :id')
        ->execute([':pinned' => $newVal, ':now' => date('Y-m-d H:i:s'), ':id' => $id]);

    respond(200, ['data' => ['id' => $id, 'is_pinned' => $newVal]]);
}

function handleStoriesToggleStatus(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id, status, published_at FROM stories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'الموضوع غير موجود.']);
    }

    $newStatus = $row['status'] === 'published' ? 'draft' : 'published';
    $now = date('Y-m-d H:i:s');
    $publishedAt = $newStatus === 'published' && empty($row['published_at']) ? $now : $row['published_at'];

    $pdo->prepare('UPDATE stories SET status = :status, published_at = :pub, updated_at = :now WHERE id = :id')
        ->execute([':status' => $newStatus, ':pub' => $publishedAt, ':now' => $now, ':id' => $id]);

    respond(200, ['data' => ['id' => $id, 'status' => $newStatus]]);
}

function handleImageUpload(): void
{
    requireAuth();

    // التحقق من CSRF عبر الهيدر أو الحقل
    // $_SESSION لا زالت في الذاكرة بعد session_write_close() في requireAuth()
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    $storedToken = $_SESSION['csrf_token'] ?? '';
    if (!$token || !hash_equals($storedToken, $token)) {
        respond(403, ['error' => 'CSRF token invalid.']);
    }

    if (empty($_FILES['image'])) {
        respond(400, ['error' => 'لم يتم رفع صورة.']);
    }

    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        respond(400, ['error' => 'حدث خطأ أثناء رفع الصورة.']);
    }

    // التحقق من نوع الملف
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes, true)) {
        respond(400, ['error' => 'نوع الملف غير مسموح. الأنواع المسموحة: JPG, PNG, GIF, WEBP']);
    }

    // التحقق من الحجم (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        respond(400, ['error' => 'حجم الصورة يتجاوز 5 ميجابايت.']);
    }

    // إنشاء مجلد الرفع
    $uploadDir = __DIR__ . '/../uploads/stories/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0750, true);
    }

    // تسمية فريدة
    $ext = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        respond(500, ['error' => 'فشل في حفظ الصورة.']);
    }

    $url = '/uploads/stories/' . $filename;
    respond(200, ['url' => $url]);
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();
$action = $_GET['action'] ?? null;

try {
    match (true) {
        // رفع صورة
        $method === 'POST' && !empty($_GET['upload'])   => handleImageUpload(),
        // تبديل التثبيت
        $method === 'PATCH' && $id !== null && $action === 'pin'    => handleStoriesTogglePin($id),
        // تبديل حالة النشر
        $method === 'PATCH' && $id !== null && $action === 'status' => handleStoriesToggleStatus($id),
        // عمليات CRUD
        $method === 'GET'    && $id === null  => handleStoriesGetAll(),
        $method === 'GET'    && $id !== null  => handleStoriesGetOne($id),
        $method === 'POST'                    => handleStoriesPost(),
        $method === 'PUT'    && $id !== null  => handleStoriesPut($id),
        $method === 'DELETE' && $id !== null  => handleStoriesDelete($id),
        $method === 'PUT'    && $id === null  => respond(400, ['error' => '?id= مطلوب.']),
        $method === 'DELETE' && $id === null  => respond(400, ['error' => '?id= مطلوب.']),
        default                               => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (PDOException $e) {
    error_log('PDOException in stories: ' . $e->getMessage());
    respond(500, ['error' => 'Database error.']);
}
