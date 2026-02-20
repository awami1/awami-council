<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

function defaultSettings(): array {
    return [
        'header' => [
            'title'    => 'مجلس عائلة العوامي',
            'subtitle' => 'AL AWAMI • 1413 - 1992',
        ],
        'hero' => [
            'title'       => 'مرحباً بكم في مجلس عائلة العوامي',
            'description' => 'منذ عام 1992م - 1413هـ، نعمل على تعزيز الترابط الأسري وخدمة أفراد العائلة من خلال الأنشطة والفعاليات المتنوعة التي تُنظَّم بروح الألفة والتعاون والمسؤولية',
        ],
        'stats' => [
            'years'      => 32,
            'committees' => 11,
            'members'    => '+100',
        ],
        'about' => [
            'mission' => '',
            'vision'  => '',
        ],
        'councilPositions' => [],
        'values'           => [],
        'logo'             => null,
        'media'            => [],
    ];
}

if ($method === 'GET') {
    $row = $pdo->query("SELECT data FROM website_settings WHERE id = 1 LIMIT 1")->fetch();
    if (!$row) {
        respond(200, ['settings' => defaultSettings()]);
    }
    $settings = json_decode($row['data'], true) ?: defaultSettings();
    $settings = array_replace_recursive(defaultSettings(), $settings);
    respond(200, ['settings' => $settings]);
}

if ($method === 'POST') {
    $data    = bodyJson();
    $row     = $pdo->query("SELECT data FROM website_settings WHERE id = 1 LIMIT 1")->fetch();
    $current = $row ? (json_decode($row['data'], true) ?: defaultSettings()) : defaultSettings();

    // Merge section or full
    if (isset($data['section']) && isset($data['data'])) {
        $current[$data['section']] = $data['data'];
    } else {
        $current = array_replace_recursive($current, $data);
    }

    $json = json_encode($current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($row) {
        $pdo->prepare("UPDATE website_settings SET data=:d WHERE id=1")->execute([':d' => $json]);
    } else {
        $pdo->prepare("INSERT INTO website_settings (id, data) VALUES (1, :d)")->execute([':d' => $json]);
    }

    respond(200, ['ok' => true, 'settings' => $current]);
}

respond(405, ['error' => 'Method not allowed']);
