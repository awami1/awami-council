<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
requireAuth();
verifyCsrf();

$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

// Ensure website_settings table exists
if (isSQLite()) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS website_settings (
      id INTEGER NOT NULL DEFAULT 1 PRIMARY KEY,
      data TEXT NOT NULL,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
} else {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `website_settings` (
      `id` INT NOT NULL DEFAULT 1,
      `data` MEDIUMTEXT NOT NULL,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function getSettings(PDO $pdo): array {
    $row = $pdo->query("SELECT data FROM website_settings WHERE id=1 LIMIT 1")->fetch();
    return $row ? (json_decode($row['data'], true) ?: []) : [];
}

function saveSettings(PDO $pdo, array $s): void {
    $json = json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $row  = $pdo->query("SELECT id FROM website_settings WHERE id=1 LIMIT 1")->fetch();
    if ($row) {
        $pdo->prepare("UPDATE website_settings SET data=:d WHERE id=1")->execute([':d' => $json]);
    } else {
        $pdo->prepare("INSERT INTO website_settings (id,data) VALUES (1,:d)")->execute([':d' => $json]);
    }
}

if ($method === 'GET') {
    $s = getSettings($pdo);
    respond(200, ['media' => $s['media'] ?? []]);
}

if ($method === 'POST') {
    $d     = bodyJson();
    $title = trim($d['title'] ?? '');
    $url   = trim($d['url']   ?? '');
    if (!$title || !$url) respond(422, ['error' => 'title and url required']);

    $item = [
        'id'    => uid(),
        'title' => $title,
        'type'  => $d['type']  ?? 'images',
        'url'   => $url,
        'date'  => $d['date']  ?? date('Y-m-d'),
        'tags'  => $d['tags']  ?? [],
    ];

    $s = getSettings($pdo);
    if (!isset($s['media'])) $s['media'] = [];
    $s['media'][] = $item;
    saveSettings($pdo, $s);

    respond(201, ['media' => $item]);
}

if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    if (!$id) respond(400, ['error' => 'id required']);

    $d = bodyJson();
    $s = getSettings($pdo);
    $found = false;
    foreach ($s['media'] as &$item) {
        if ($item['id'] === $id) {
            $item['title'] = trim($d['title'] ?? $item['title']);
            $item['type']  = $d['type']  ?? $item['type'];
            $item['url']   = trim($d['url']   ?? $item['url']);
            $item['date']  = $d['date']  ?? $item['date'];
            $item['tags']  = $d['tags']  ?? $item['tags'];
            $found = true;
            break;
        }
    }
    unset($item);
    if (!$found) respond(404, ['error' => 'not found']);
    saveSettings($pdo, $s);
    respond(200, ['ok' => true]);
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) respond(400, ['error' => 'id required']);

    $s = getSettings($pdo);
    $s['media'] = array_values(array_filter($s['media'] ?? [], fn($m) => $m['id'] !== $id));
    saveSettings($pdo, $s);

    respond(200, ['ok' => true]);
}

respond(405, ['error' => 'Method not allowed']);
