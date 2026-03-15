<?php
/**
 * migrate-media.php — نقل الميديا من website_settings إلى جدول media المستقل
 * يُشغَّل مرة واحدة ثم يُحذف
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/gallery_schema.php';
requireAuth();

header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();

ensureAlbumsTable();
ensureMediaTable();

// تحقق إذا جدول media يحتوي بيانات مسبقاً
$existingCount = (int) $pdo->query('SELECT COUNT(*) FROM media')->fetchColumn();
if ($existingCount > 0) {
    echo json_encode([
        'message' => 'الهجرة تمت مسبقاً.',
        'existing_count' => $existingCount,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// قراءة الميديا من website_settings
$row = $pdo->query("SELECT data FROM website_settings WHERE id = 1 LIMIT 1")->fetch();
if (!$row) {
    echo json_encode(['message' => 'لا توجد بيانات website_settings.', 'migrated' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

$settings = json_decode($row['data'], true) ?: [];
$mediaItems = $settings['media'] ?? [];

if (empty($mediaItems)) {
    echo json_encode(['message' => 'لا توجد ميديا للنقل.', 'migrated' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

$insertStmt = $pdo->prepare(
    'INSERT INTO media (id, album_id, title, type, url, date, tags, sort_order, created_at, updated_at)
     VALUES (:id, NULL, :title, :type, :url, :date, :tags, :sort_order, :created_at, :updated_at)'
);

$migrated = 0;
$now = date('Y-m-d H:i:s');

foreach ($mediaItems as $i => $item) {
    if (!is_array($item) || empty($item['id'])) continue;

    // تحويل type: events → images
    $type = $item['type'] ?? 'images';
    if ($type === 'events') $type = 'images';
    if (!in_array($type, ['images', 'videos', 'youtube'], true)) $type = 'images';

    // تحويل tags إلى JSON string
    $tags = $item['tags'] ?? [];
    if (is_array($tags)) {
        $tags = json_encode($tags, JSON_UNESCAPED_UNICODE);
    } elseif (!is_string($tags)) {
        $tags = '[]';
    }

    try {
        $insertStmt->execute([
            ':id'         => $item['id'],
            ':title'      => $item['title'] ?? 'بدون عنوان',
            ':type'       => $type,
            ':url'        => $item['url']   ?? '',
            ':date'       => $item['date']  ?? null,
            ':tags'       => $tags,
            ':sort_order' => $i,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
        $migrated++;
    } catch (PDOException $e) {
        error_log("Migration error for item {$item['id']}: " . $e->getMessage());
    }
}

echo json_encode([
    'message'  => "تم نقل {$migrated} عنصر بنجاح.",
    'migrated' => $migrated,
    'total'    => count($mediaItems),
], JSON_UNESCAPED_UNICODE);
