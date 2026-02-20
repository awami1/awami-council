<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

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

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) respond(400, ['error' => 'id required']);

    $s = getSettings($pdo);
    $s['media'] = array_values(array_filter($s['media'] ?? [], fn($m) => $m['id'] !== $id));
    saveSettings($pdo, $s);

    respond(200, ['ok' => true]);
}

respond(405, ['error' => 'Method not allowed']);
