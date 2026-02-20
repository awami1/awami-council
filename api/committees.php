<?php
require_once __DIR__ . '/config.php';

$pdo = getPDO();

$tables = $pdo->query("SHOW TABLES LIKE 'committees'")->fetchAll();

if (empty($tables)) {
    respond(200, ['committees' => []]);
}

$committees = $pdo->query("SELECT * FROM committees ORDER BY name ASC")->fetchAll();

respond(200, ['committees' => $committees]);
