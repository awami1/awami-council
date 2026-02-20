<?php
require_once __DIR__ . '/config.php';

$pdo = getPDO();

// Check if settings table exists
$tables = $pdo->query("SHOW TABLES LIKE 'settings'")->fetchAll();

if (empty($tables)) {
    // Return default settings if table doesn't exist
    respond(200, [
        'settings' => null
    ]);
}

$rows = $pdo->query("SELECT * FROM settings LIMIT 1")->fetchAll();

respond(200, [
    'settings' => $rows[0] ?? null
]);
