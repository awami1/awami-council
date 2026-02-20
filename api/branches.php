<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

// Add members column if missing
try { $pdo->exec("ALTER TABLE family_branches ADD COLUMN `members` JSON DEFAULT NULL"); }
catch (PDOException $e) {}

function branchRow(array $r): array {
    return [
        'id'      => $r['id'],
        'name'    => $r['name'],
        'head'    => $r['head'] ?? '',
        'count'   => (int)$r['count'],
        'color'   => $r['color'],
        'notes'   => $r['notes'] ?? '',
        'members' => $r['members'] ? json_decode($r['members'], true) : [],
    ];
}

if ($method === 'GET') {
    $rows = $pdo->query("SELECT * FROM family_branches ORDER BY name ASC")->fetchAll();
    respond(200, ['branches' => array_map('branchRow', $rows)]);
}

if ($method === 'POST') {
    $d       = bodyJson();
    $id      = $d['id'] ?? uid();
    $name    = trim($d['name'] ?? '');
    if (!$name) respond(422, ['error' => 'name required']);
    $members = $d['members'] ?? [];
    $count   = count($members);
    $mJson   = json_encode($members, JSON_UNESCAPED_UNICODE);

    $chk = $pdo->prepare("SELECT id FROM family_branches WHERE id=:id");
    $chk->execute([':id' => $id]);

    if ($chk->fetch()) {
        $pdo->prepare("UPDATE family_branches SET name=:n,head=:h,count=:c,color=:col,notes=:no,members=:m WHERE id=:id")
            ->execute([':n'=>$name,':h'=>$d['head']??'',':c'=>$count,':col'=>$d['color']??'#47915C',':no'=>$d['notes']??'',':m'=>$mJson,':id'=>$id]);
    } else {
        $pdo->prepare("INSERT INTO family_branches (id,name,head,count,color,notes,members) VALUES (:id,:n,:h,:c,:col,:no,:m)")
            ->execute([':id'=>$id,':n'=>$name,':h'=>$d['head']??'',':c'=>$count,':col'=>$d['color']??'#47915C',':no'=>$d['notes']??'',':m'=>$mJson]);
    }

    $s = $pdo->prepare("SELECT * FROM family_branches WHERE id=:id");
    $s->execute([':id' => $id]);
    respond(200, ['branch' => branchRow($s->fetch())]);
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) respond(400, ['error' => 'id required']);
    $pdo->prepare("DELETE FROM family_branches WHERE id=:id")->execute([':id' => $id]);
    respond(200, ['ok' => true]);
}

respond(405, ['error' => 'Method not allowed']);
