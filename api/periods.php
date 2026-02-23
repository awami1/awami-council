<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

// GET — list all periods
if ($method === 'GET') {
    $stmt = $pdo->query('SELECT * FROM periods ORDER BY created_at DESC');
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['fee_amount'] = (float) $r['fee_amount'];
    }
    respond(200, ['data' => $rows, 'total' => count($rows)]);
}

// POST — create period
if ($method === 'POST') {
    $data = bodyJson();
    $id   = uid();

    $pdo->prepare(
        'INSERT INTO periods (id, name, fee_amount, start_date, end_date)
         VALUES (:id, :name, :fee, :start, :end)'
    )->execute([
        ':id'    => $id,
        ':name'  => $data['name'] ?? '',
        ':fee'   => (float)($data['fee_amount'] ?? 0),
        ':start' => $data['start_date'] ?? null,
        ':end'   => $data['end_date'] ?? null,
    ]);

    $stmt = $pdo->prepare('SELECT * FROM periods WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    $row['fee_amount'] = (float) $row['fee_amount'];

    respond(201, ['data' => $row]);
}

// DELETE — delete period
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) respond(400, ['error' => '?id= required']);

    $pdo->prepare('DELETE FROM periods WHERE id = :id')->execute([':id' => $id]);
    respond(200, ['message' => 'Period deleted.']);
}

respond(405, ['error' => 'Method not allowed']);
