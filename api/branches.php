<?php
require_once __DIR__ . '/config.php';

$pdo = getPDO();

$tables = $pdo->query("SHOW TABLES LIKE 'branches'")->fetchAll();

if (empty($tables)) {
    respond(200, ['branches' => []]);
}

$branches = $pdo->query("SELECT * FROM branches ORDER BY name ASC")->fetchAll();

respond(200, ['branches' => $branches]);
