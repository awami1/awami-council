<?php
// نقطة عامة لعرض الفعاليات القادمة — لا تتطلب مصادقة
require_once __DIR__ . '/config.php';

try {
    $pdo  = getPDO();
    $rows = $pdo->query("
        SELECT id, name, icon, event_date, lead
        FROM events
        WHERE status = 'قادم'
          AND (event_date IS NULL OR event_date >= DATE('now'))
        ORDER BY event_date ASC
        LIMIT 6
    ")->fetchAll();
    respond(200, ['events' => $rows]);
} catch (Throwable $e) {
    respond(200, ['events' => []]);
}
