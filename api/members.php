<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
requireAuth();
verifyCsrf();

/*
|--------------------------------------------------------------------------
| SETUP ROUTE (MUST RUN BEFORE ANY SELECT)
|--------------------------------------------------------------------------
*/

if (isset($_GET['setup'])) {

    $pdo = getPDO();

    if (isSQLite()) {
        $tables = [
            "CREATE TABLE IF NOT EXISTS members (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                name VARCHAR(200) NOT NULL,
                family VARCHAR(200) NOT NULL DEFAULT '',
                phone VARCHAR(20) DEFAULT NULL,
                id_num VARCHAR(20) DEFAULT NULL,
                join_date DATE DEFAULT NULL,
                status TEXT NOT NULL DEFAULT 'نشط' CHECK(status IN ('نشط','معفي','غير نشط')),
                notes TEXT,
                branch_id VARCHAR(36) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE UNIQUE INDEX IF NOT EXISTS uq_id_num ON members(id_num)",
        ];
    } else {
        $tables = [
            "CREATE TABLE IF NOT EXISTS `members` (
                `id` VARCHAR(36) NOT NULL,
                `name` VARCHAR(200) NOT NULL,
                `family` VARCHAR(200) NOT NULL DEFAULT '',
                `phone` VARCHAR(20) DEFAULT NULL,
                `id_num` VARCHAR(20) DEFAULT NULL,
                `join_date` DATE DEFAULT NULL,
                `status` ENUM('نشط','معفي','غير نشط') NOT NULL DEFAULT 'نشط',
                `notes` TEXT,
                `branch_id` VARCHAR(36) DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_id_num` (`id_num`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
    }

    $errors = [];
    $created = [];

    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
            preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/', $sql, $m);
            $created[] = $m[1] ?? '?';
        } catch (PDOException $e) {
            $errors[] = $e->getMessage();
        }
    }

    respond(
        empty($errors) ? 200 : 500,
        [
            'status'  => empty($errors)
                ? 'SUCCESS — remove ?setup=1 from URL'
                : 'PARTIAL',
            'created' => $created,
            'errors'  => $errors,
        ]
    );
}

/*
|--------------------------------------------------------------------------
| HANDLERS
|--------------------------------------------------------------------------
*/

function handleGetAll(): void
{
    $pdo = getPDO();

    $stmt = $pdo->query('SELECT * FROM members ORDER BY name ASC');
    $members = $stmt->fetchAll();

    respond(200, [
        'data'  => $members,
        'total' => count($members),
    ]);
}

function handleGetOne(string $id): void
{
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    $member = $stmt->fetch();

    if (!$member) {
        respond(404, ['error' => 'Member not found.']);
    }

    respond(200, ['data' => $member]);
}

function handlePost(): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    $id = uid();

    $stmt = $pdo->prepare(
        "INSERT INTO members
        (id, name, family, phone, id_num, join_date, status, notes, branch_id)
        VALUES
        (:id, :name, :family, :phone, :id_num, :join_date, :status, :notes, :branch_id)"
    );

    $stmt->execute([
        ':id'        => $id,
        ':name'      => $data['name'] ?? '',
        ':family'    => $data['family'] ?? '',
        ':phone'     => $data['phone'] ?? '',
        ':id_num'    => $data['id_num'] ?? '',
        ':join_date' => $data['join_date'] ?? null,
        ':status'    => $data['status'] ?? 'نشط',
        ':notes'     => $data['notes'] ?? '',
        ':branch_id' => $data['branch_id'] ?? null,
    ]);

    logAudit('إضافة', 'عضو', $id, $data['name'] ?? '');
    handleGetOne($id);
}

function handlePut(string $id): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    // السماح فقط بالحقول المعروفة لمنع حقن أسماء أعمدة عشوائية
    $allowed = ['name', 'family', 'phone', 'id_num', 'join_date', 'status', 'notes', 'branch_id'];
    $fields = [];
    $params = [':id' => $id];

    foreach ($data as $key => $value) {
        if (!in_array($key, $allowed, true)) continue;
        $fields[] = "{$key} = :{$key}";
        $params[":{$key}"] = $value;
    }

    if (empty($fields)) {
        respond(422, ['error' => 'No fields provided for update.']);
    }

    $sql = "UPDATE members SET " . implode(', ', $fields) . " WHERE id = :id";
    $pdo->prepare($sql)->execute($params);

    logAudit('تعديل', 'عضو', $id, $data['name'] ?? '', $data);
    handleGetOne($id);
}

function handleDelete(string $id): void
{
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT name FROM members WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    $pdo->prepare('DELETE FROM members WHERE id = :id')->execute([':id' => $id]);

    logAudit('حذف', 'عضو', $id, $row['name'] ?? '');
    respond(200, ['message' => 'Member deleted successfully.']);
}

function handleAddToCommittee(): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    $committeeId = $data['committeeId'] ?? ($data['committee_id'] ?? '');
    $memberId    = $data['memberId']    ?? ($data['member_id'] ?? '');

    if (!$committeeId || !$memberId) {
        respond(422, ['error' => 'committeeId and memberId are required.']);
    }

    // Ensure committee_members table exists
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS committee_members (
            committee_id VARCHAR(36) NOT NULL,
            member_id VARCHAR(36) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (committee_id, member_id),
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
        )");
    }

    // Upsert (ignore if already exists)
    if (isSQLite()) {
        $pdo->prepare(
            'INSERT OR IGNORE INTO committee_members (committee_id, member_id) VALUES (:cid, :mid)'
        )->execute([':cid' => $committeeId, ':mid' => $memberId]);
    } else {
        $pdo->prepare(
            'INSERT IGNORE INTO committee_members (committee_id, member_id) VALUES (:cid, :mid)'
        )->execute([':cid' => $committeeId, ':mid' => $memberId]);
    }

    respond(200, ['ok' => true]);
}

function handleRemoveFromCommittee(): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    $committeeId = $data['committeeId'] ?? ($data['committee_id'] ?? '');
    $memberId    = $data['memberId']    ?? ($data['member_id'] ?? '');

    if (!$committeeId || !$memberId) {
        respond(422, ['error' => 'committeeId and memberId are required.']);
    }

    $pdo->prepare(
        'DELETE FROM committee_members WHERE committee_id = :cid AND member_id = :mid'
    )->execute([':cid' => $committeeId, ':mid' => $memberId]);

    respond(200, ['ok' => true]);
}

/*
|--------------------------------------------------------------------------
| ROUTER
|--------------------------------------------------------------------------
*/

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$action = $_GET['action'] ?? '';

match (true) {

    $method === 'POST' && $action === 'add_committee'    => handleAddToCommittee(),
    $method === 'POST' && $action === 'remove_committee' => handleRemoveFromCommittee(),
    $method === 'GET'    && $id === null => handleGetAll(),
    $method === 'GET'    && $id !== null => handleGetOne($id),
    $method === 'POST'                   => handlePost(),
    $method === 'PUT'   && $id !== null  => handlePut($id),
    $method === 'DELETE' && $id !== null => handleDelete($id),

    default => respond(405, ['error' => 'Method not allowed.']),

};
