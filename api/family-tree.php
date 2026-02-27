<?php
// api/family-tree.php — CRUD لشجرة العائلة الهرمية
// GET عام (بدون auth) — POST/PUT/DELETE تتطلب auth

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';

$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

function treeRow(array $r): array {
    return [
        'id'          => $r['id'],
        'name'        => $r['name'],
        'parent_id'   => $r['parent_id'],
        'gender'      => $r['gender'],
        'is_alive'    => (bool)(int)$r['is_alive'],
        'spouse_name' => $r['spouse_name'] ?? '',
        'sort_order'  => (int)$r['sort_order'],
        'created_at'  => $r['created_at'] ?? '',
    ];
}

// ── GET (عام — لا يحتاج مصادقة) ──
if ($method === 'GET') {
    $rows = $pdo->query("SELECT * FROM family_tree ORDER BY sort_order ASC, name ASC")->fetchAll();
    respond(200, ['members' => array_map('treeRow', $rows)]);
}

// ── الباقي يحتاج مصادقة ──
requireAuth();

// ── POST — إضافة فرد ──
if ($method === 'POST') {
    $d    = bodyJson();
    $id   = uid();
    $name = trim($d['name'] ?? '');
    if ($name === '') respond(422, ['error' => 'الاسم مطلوب']);

    $parentId = $d['parent_id'] ?? null;
    if ($parentId === '') $parentId = null;

    // تحقق أن الأب موجود
    if ($parentId !== null) {
        $chk = $pdo->prepare("SELECT id FROM family_tree WHERE id = :id");
        $chk->execute([':id' => $parentId]);
        if (!$chk->fetch()) respond(422, ['error' => 'الأب المحدد غير موجود']);
    }

    $gender    = in_array($d['gender'] ?? '', ['ذكر', 'أنثى']) ? $d['gender'] : 'ذكر';
    $isAlive   = isset($d['is_alive']) ? (int)(bool)$d['is_alive'] : 1;
    $spouse    = trim($d['spouse_name'] ?? '');
    $sortOrder = (int)($d['sort_order'] ?? 0);

    $pdo->prepare(
        "INSERT INTO family_tree (id, name, parent_id, gender, is_alive, spouse_name, sort_order)
         VALUES (:id, :name, :pid, :gender, :alive, :spouse, :sort)"
    )->execute([
        ':id'     => $id,
        ':name'   => $name,
        ':pid'    => $parentId,
        ':gender' => $gender,
        ':alive'  => $isAlive,
        ':spouse' => $spouse,
        ':sort'   => $sortOrder,
    ]);

    $stmt = $pdo->prepare("SELECT * FROM family_tree WHERE id = :id");
    $stmt->execute([':id' => $id]);
    respond(201, ['member' => treeRow($stmt->fetch())]);
}

// ── PUT — تعديل فرد ──
if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    if (!$id) respond(400, ['error' => 'id مطلوب']);

    $stmt = $pdo->prepare("SELECT * FROM family_tree WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $existing = $stmt->fetch();
    if (!$existing) respond(404, ['error' => 'الفرد غير موجود']);

    $d = bodyJson();
    $sets   = [];
    $params = [':id' => $id];

    if (array_key_exists('name', $d)) {
        $v = trim($d['name']);
        if ($v === '') respond(422, ['error' => 'الاسم مطلوب']);
        $sets[] = 'name = :name';
        $params[':name'] = $v;
    }
    if (array_key_exists('parent_id', $d)) {
        $pid = $d['parent_id'] ?: null;
        if ($pid === $id) respond(422, ['error' => 'لا يمكن أن يكون الشخص أباً لنفسه']);
        if ($pid !== null) {
            $chk = $pdo->prepare("SELECT id FROM family_tree WHERE id = :pid");
            $chk->execute([':pid' => $pid]);
            if (!$chk->fetch()) respond(422, ['error' => 'الأب المحدد غير موجود']);
        }
        $sets[] = 'parent_id = :pid';
        $params[':pid'] = $pid;
    }
    if (array_key_exists('gender', $d) && in_array($d['gender'], ['ذكر', 'أنثى'])) {
        $sets[] = 'gender = :gender';
        $params[':gender'] = $d['gender'];
    }
    if (array_key_exists('is_alive', $d)) {
        $sets[] = 'is_alive = :alive';
        $params[':alive'] = (int)(bool)$d['is_alive'];
    }
    if (array_key_exists('spouse_name', $d)) {
        $sets[] = 'spouse_name = :spouse';
        $params[':spouse'] = trim($d['spouse_name']);
    }
    if (array_key_exists('sort_order', $d)) {
        $sets[] = 'sort_order = :sort';
        $params[':sort'] = (int)$d['sort_order'];
    }

    if (empty($sets)) respond(422, ['error' => 'لم يتم إرسال بيانات للتحديث']);

    $pdo->prepare("UPDATE family_tree SET " . implode(', ', $sets) . " WHERE id = :id")->execute($params);

    $stmt = $pdo->prepare("SELECT * FROM family_tree WHERE id = :id");
    $stmt->execute([':id' => $id]);
    respond(200, ['member' => treeRow($stmt->fetch())]);
}

// ── DELETE — حذف فرد (نقل أبنائه للجد) ──
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) respond(400, ['error' => 'id مطلوب']);

    $stmt = $pdo->prepare("SELECT parent_id, name FROM family_tree WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) respond(404, ['error' => 'الفرد غير موجود']);

    // نقل الأبناء لجد المحذوف
    $pdo->prepare("UPDATE family_tree SET parent_id = :pid WHERE parent_id = :id")
        ->execute([':pid' => $row['parent_id'], ':id' => $id]);

    $pdo->prepare("DELETE FROM family_tree WHERE id = :id")->execute([':id' => $id]);
    respond(200, ['ok' => true, 'message' => "تم حذف {$row['name']}"]);
}

respond(405, ['error' => 'Method not allowed']);
