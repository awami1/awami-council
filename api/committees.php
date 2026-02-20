<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$pdo = getPDO();

// Committees are stored in website_settings JSON (they come from static data in admin)
// We return them from settings, supplemented with DB member counts

$settingsRow = $pdo->query("SELECT data FROM website_settings WHERE id=1 LIMIT 1")->fetch();
$settings    = $settingsRow ? json_decode($settingsRow['data'], true) : [];

// Get committee member counts from DB
$counts = [];
try {
    $rows = $pdo->query("SELECT committee_id, COUNT(*) as cnt FROM committee_members GROUP BY committee_id")->fetchAll();
    foreach ($rows as $r) $counts[$r['committee_id']] = (int)$r['cnt'];
} catch (PDOException $e) {}

$committees = $settings['committees'] ?? [];

// Add DB member count to each committee
foreach ($committees as &$c) {
    $c['dbMemberCount'] = $counts[$c['id']] ?? 0;
}

respond(200, ['committees' => $committees]);
