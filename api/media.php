<?php
require_once __DIR__ . '/config.php';

$pdo = getPDO();

$tables = $pdo->query("SHOW TABLES LIKE 'media'")->fetchAll();

if (empty($tables)) {
    respond(200, ['media' => []]);
}

$media = $pdo->query("SELECT * FROM media ORDER BY created_at DESC")->fetchAll();

respond(200, ['media' => $media]);
