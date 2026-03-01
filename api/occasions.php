<?php
/**
 * occasions.php — إدارة صفحة المناسبات / التهنئة
 * GET  ?action=public       → إعدادات عامة (للزوار) — يرجع 404 إذا مخفية
 * GET  (admin)              → إعدادات كاملة للوحة التحكم
 * PUT  (admin)              → تحديث الإعدادات
 * POST ?action=upload       → رفع صورة
 * PUT  ?action=primary&id=X → تعيين صورة رئيسية
 * PUT  ?action=reorder      → إعادة ترتيب الصور
 * DELETE ?id=X              → حذف صورة
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
require_once __DIR__ . '/validation.php';
verifyCsrf();

// ──────────────────────────────────────────────────────────────
// إنشاء الجداول
// ──────────────────────────────────────────────────────────────

function ensureOccasionTables(): void
{
    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS occasion_settings (
            id INTEGER PRIMARY KEY DEFAULT 1,
            page_title TEXT NOT NULL DEFAULT 'تهنئة عيد الفطر المبارك',
            greeting_message TEXT DEFAULT '',
            is_active INTEGER DEFAULT 1,
            name_input_enabled INTEGER DEFAULT 1,
            name_position_x REAL DEFAULT 50.00,
            name_position_y REAL DEFAULT 80.00,
            name_font_color TEXT DEFAULT '#FFFFFF',
            name_font_size TEXT DEFAULT 'large',
            name_font_family TEXT DEFAULT 'Cairo',
            name_text_shadow INTEGER DEFAULT 1,
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS occasion_images (
            id TEXT PRIMARY KEY,
            image_path TEXT NOT NULL,
            is_primary INTEGER DEFAULT 0,
            sort_order INTEGER DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `occasion_settings` (
            `id` INT PRIMARY KEY DEFAULT 1,
            `page_title` VARCHAR(255) NOT NULL DEFAULT 'تهنئة عيد الفطر المبارك',
            `greeting_message` TEXT,
            `is_active` TINYINT(1) DEFAULT 1,
            `name_input_enabled` TINYINT(1) DEFAULT 1,
            `name_position_x` DECIMAL(5,2) DEFAULT 50.00,
            `name_position_y` DECIMAL(5,2) DEFAULT 80.00,
            `name_font_color` VARCHAR(7) DEFAULT '#FFFFFF',
            `name_font_size` ENUM('small','medium','large','xlarge') DEFAULT 'large',
            `name_font_family` VARCHAR(100) DEFAULT 'Cairo',
            `name_text_shadow` TINYINT(1) DEFAULT 1,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `occasion_images` (
            `id` VARCHAR(36) NOT NULL,
            `image_path` VARCHAR(500) NOT NULL,
            `is_primary` TINYINT(1) DEFAULT 0,
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // إدراج الصف الافتراضي إن لم يكن موجوداً
    $stmt = $pdo->query('SELECT COUNT(*) as cnt FROM occasion_settings');
    if ((int) $stmt->fetch()['cnt'] === 0) {
        $pdo->exec("INSERT INTO occasion_settings (id) VALUES (1)");
    }
}

ensureOccasionTables();

// ──────────────────────────────────────────────────────────────
// مساعدات
// ──────────────────────────────────────────────────────────────

function getSettings(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM occasion_settings WHERE id = 1');
    $row = $stmt->fetch();
    if (!$row) {
        $pdo->exec("INSERT INTO occasion_settings (id) VALUES (1)");
        $stmt = $pdo->query('SELECT * FROM occasion_settings WHERE id = 1');
        $row = $stmt->fetch();
    }
    return [
        'page_title'         => $row['page_title'] ?? 'تهنئة عيد الفطر المبارك',
        'greeting_message'   => $row['greeting_message'] ?? '',
        'is_active'          => (int) ($row['is_active'] ?? 1),
        'name_input_enabled' => (int) ($row['name_input_enabled'] ?? 1),
        'name_position_x'    => (float) ($row['name_position_x'] ?? 50),
        'name_position_y'    => (float) ($row['name_position_y'] ?? 80),
        'name_font_color'    => $row['name_font_color'] ?? '#FFFFFF',
        'name_font_size'     => $row['name_font_size'] ?? 'large',
        'name_font_family'   => $row['name_font_family'] ?? 'Cairo',
        'name_text_shadow'   => (int) ($row['name_text_shadow'] ?? 1),
        'updated_at'         => $row['updated_at'] ?? '',
    ];
}

function getImages(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM occasion_images ORDER BY sort_order ASC, created_at ASC');
    $rows = $stmt->fetchAll();
    return array_map(function ($row) {
        return [
            'id'         => $row['id'],
            'image_path' => $row['image_path'],
            'is_primary' => (int) $row['is_primary'],
            'sort_order' => (int) $row['sort_order'],
            'created_at' => $row['created_at'] ?? '',
        ];
    }, $rows);
}

const VALID_FONT_SIZES    = ['small', 'medium', 'large', 'xlarge'];
const VALID_FONT_FAMILIES = ['Cairo', 'Amiri', 'Saudi', 'Tajawal'];
const MAX_IMAGE_SIZE      = 5 * 1024 * 1024; // 5MB
const ALLOWED_EXTENSIONS  = ['jpg', 'jpeg', 'png', 'webp'];

// ──────────────────────────────────────────────────────────────
// معالجات
// ──────────────────────────────────────────────────────────────

/** GET عام (للزوار) */
function handlePublicGet(): void
{
    $settings = getSettings();
    if (!$settings['is_active']) {
        respond(200, [
            'active'  => false,
            'message' => 'لا توجد مناسبة حالياً',
        ]);
    }
    $images = getImages();
    respond(200, [
        'active'   => true,
        'settings' => $settings,
        'images'   => $images,
    ]);
}

/** GET إداري (للوحة التحكم) */
function handleAdminGet(): void
{
    requireAuth();
    respond(200, [
        'settings' => getSettings(),
        'images'   => getImages(),
    ]);
}

/** PUT — تحديث الإعدادات */
function handleUpdateSettings(): void
{
    requireAuth();
    $data = bodyJson();
    $pdo  = getPDO();

    $allowed = [
        'page_title', 'greeting_message', 'is_active', 'name_input_enabled',
        'name_position_x', 'name_position_y', 'name_font_color',
        'name_font_size', 'name_font_family', 'name_text_shadow',
    ];

    $setClauses = [];
    $params     = [];

    foreach ($allowed as $field) {
        if (!array_key_exists($field, $data)) continue;

        $val = $data[$field];

        switch ($field) {
            case 'page_title':
                $val = sanitizeString($val, 'page_title');
                if ($val === '') respond(422, ['error' => 'عنوان المناسبة مطلوب.']);
                break;
            case 'greeting_message':
                $val = is_string($val) ? trim($val) : '';
                break;
            case 'is_active':
            case 'name_input_enabled':
            case 'name_text_shadow':
                $val = $val ? 1 : 0;
                break;
            case 'name_position_x':
            case 'name_position_y':
                $val = max(0, min(100, (float) $val));
                break;
            case 'name_font_color':
                $val = sanitizeString($val, 'name_font_color');
                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $val)) {
                    respond(422, ['error' => 'لون غير صالح.']);
                }
                break;
            case 'name_font_size':
                $val = sanitizeString($val, 'name_font_size');
                if (!in_array($val, VALID_FONT_SIZES, true)) {
                    respond(422, ['error' => 'حجم خط غير صالح.']);
                }
                break;
            case 'name_font_family':
                $val = sanitizeString($val, 'name_font_family');
                if (!in_array($val, VALID_FONT_FAMILIES, true)) {
                    respond(422, ['error' => 'نوع خط غير صالح.']);
                }
                break;
        }

        $setClauses[]        = "{$field} = :{$field}";
        $params[":{$field}"] = $val;
    }

    if (empty($setClauses)) {
        respond(422, ['error' => 'لا توجد حقول للتحديث.']);
    }

    $setClauses[] = "updated_at = :updated_at";
    $params[':updated_at'] = date('Y-m-d H:i:s');

    $pdo->prepare(
        'UPDATE occasion_settings SET ' . implode(', ', $setClauses) . ' WHERE id = 1'
    )->execute($params);

    logAudit('تعديل', 'مناسبة', '1', $data['page_title'] ?? 'إعدادات المناسبة');

    respond(200, [
        'settings' => getSettings(),
        'images'   => getImages(),
    ]);
}

/** POST — رفع صورة */
function handleImageUpload(): void
{
    requireAuth();

    if (empty($_FILES['image'])) {
        respond(422, ['error' => 'لم يتم إرسال صورة.']);
    }

    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        respond(422, ['error' => 'خطأ في رفع الصورة.']);
    }

    if ($file['size'] > MAX_IMAGE_SIZE) {
        respond(422, ['error' => 'حجم الصورة يتجاوز الحد المسموح (5MB).']);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        respond(422, ['error' => 'صيغة الصورة غير مدعومة. الصيغ المسموحة: JPG, PNG, WEBP']);
    }

    // التحقق من نوع MIME
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMimes, true)) {
        respond(422, ['error' => 'نوع الملف غير مسموح.']);
    }

    $id       = uid();
    $filename = $id . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/occasions/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        respond(500, ['error' => 'فشل في حفظ الصورة.']);
    }

    $imagePath = '/uploads/occasions/' . $filename;
    $pdo = getPDO();

    // إذا لم توجد صور، اجعلها الرئيسية
    $stmt = $pdo->query('SELECT COUNT(*) as cnt FROM occasion_images');
    $isPrimary = ((int) $stmt->fetch()['cnt'] === 0) ? 1 : 0;

    $stmt = $pdo->query('SELECT COALESCE(MAX(sort_order), 0) as max_sort FROM occasion_images');
    $sortOrder = (int) $stmt->fetch()['max_sort'] + 1;

    $pdo->prepare(
        'INSERT INTO occasion_images (id, image_path, is_primary, sort_order) VALUES (:id, :path, :primary, :sort)'
    )->execute([
        ':id'      => $id,
        ':path'    => $imagePath,
        ':primary' => $isPrimary,
        ':sort'    => $sortOrder,
    ]);

    logAudit('إضافة', 'صورة مناسبة', $id, $filename);

    respond(201, [
        'image' => [
            'id'         => $id,
            'image_path' => $imagePath,
            'is_primary' => $isPrimary,
            'sort_order' => $sortOrder,
        ],
        'images' => getImages(),
    ]);
}

/** PUT — تعيين صورة كرئيسية */
function handleSetPrimary(string $id): void
{
    requireAuth();
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT id FROM occasion_images WHERE id = :id');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        respond(404, ['error' => 'الصورة غير موجودة.']);
    }

    $pdo->exec('UPDATE occasion_images SET is_primary = 0');
    $pdo->prepare('UPDATE occasion_images SET is_primary = 1 WHERE id = :id')
        ->execute([':id' => $id]);

    logAudit('تعديل', 'صورة مناسبة', $id, 'تعيين كصورة رئيسية');

    respond(200, ['images' => getImages()]);
}

/** PUT — إعادة ترتيب الصور */
function handleReorderImages(): void
{
    requireAuth();
    $data = bodyJson();

    if (empty($data['order']) || !is_array($data['order'])) {
        respond(422, ['error' => 'ترتيب غير صالح.']);
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE occasion_images SET sort_order = :sort WHERE id = :id');

    foreach ($data['order'] as $index => $imageId) {
        if (!is_string($imageId) && !is_numeric($imageId)) continue;
        $stmt->execute([':sort' => $index, ':id' => (string) $imageId]);
    }

    logAudit('تعديل', 'صورة مناسبة', '', 'إعادة ترتيب الصور');

    respond(200, ['images' => getImages()]);
}

/** DELETE — حذف صورة */
function handleDeleteImage(string $id): void
{
    requireAuth();
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT image_path, is_primary FROM occasion_images WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'الصورة غير موجودة.']);
    }

    // حذف الملف الفعلي
    $filePath = __DIR__ . '/..' . $row['image_path'];
    if (is_file($filePath)) {
        unlink($filePath);
    }

    $pdo->prepare('DELETE FROM occasion_images WHERE id = :id')
        ->execute([':id' => $id]);

    // إذا كانت الصورة المحذوفة رئيسية، اجعل أول صورة هي الرئيسية
    if ((int) $row['is_primary'] === 1) {
        $first = $pdo->query('SELECT id FROM occasion_images ORDER BY sort_order ASC LIMIT 1')->fetch();
        if ($first) {
            $pdo->prepare('UPDATE occasion_images SET is_primary = 1 WHERE id = :id')
                ->execute([':id' => $first['id']]);
        }
    }

    logAudit('حذف', 'صورة مناسبة', $id, basename($row['image_path']));

    respond(200, ['message' => 'تم حذف الصورة.', 'images' => getImages()]);
}

// ──────────────────────────────────────────────────────────────
// التوجيه
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = parseId();

try {
    match (true) {
        // GET عام للزوار
        $method === 'GET' && $action === 'public'
            => handlePublicGet(),

        // GET إداري
        $method === 'GET' && $action !== 'public'
            => handleAdminGet(),

        // PUT تحديث الإعدادات
        $method === 'PUT' && $action === ''
            => handleUpdateSettings(),

        // PUT تعيين صورة رئيسية
        $method === 'PUT' && $action === 'primary' && $id !== null
            => handleSetPrimary($id),

        // PUT إعادة ترتيب
        $method === 'PUT' && $action === 'reorder'
            => handleReorderImages(),

        // POST رفع صورة
        $method === 'POST' && $action === 'upload'
            => handleImageUpload(),

        // DELETE حذف صورة
        $method === 'DELETE' && $id !== null
            => handleDeleteImage($id),

        default => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (PDOException $e) {
    error_log('PDOException in occasions: ' . $e->getMessage());
    respond(500, ['error' => 'Database error.']);
}
