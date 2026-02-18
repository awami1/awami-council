<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/*
|--------------------------------------------------------------------------
| SETUP ROUTE (MUST RUN BEFORE ANY SELECT)
|--------------------------------------------------------------------------
*/

if (isset($_GET['setup'])) {

    $pdo = getPDO();

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

    handleGetOne($id);
}

function handlePut(string $id): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    $fields = [];
    $params = [':id' => $id];

    foreach ($data as $key => $value) {
        $fields[] = "{$key} = :{$key}";
        $params[":{$key}"] = $value;
    }

    if (empty($fields)) {
        respond(422, ['error' => 'No fields provided for update.']);
    }

    $sql = "UPDATE members SET " . implode(', ', $fields) . " WHERE id = :id";
    $pdo->prepare($sql)->execute($params);

    handleGetOne($id);
}

function handleDelete(string $id): void
{
    $pdo = getPDO();

    $stmt = $pdo->prepare('DELETE FROM members WHERE id = :id');
    $stmt->execute([':id' => $id]);

    respond(200, ['message' => 'Member deleted successfully.']);
}

/*
|--------------------------------------------------------------------------
| ROUTER
|--------------------------------------------------------------------------
*/

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

match (true) {

    $method === 'GET'    && $id === null => handleGetAll(),
    $method === 'GET'    && $id !== null => handleGetOne($id),
    $method === 'POST'                   => handlePost(),
    $method === 'PUT'   && $id !== null  => handlePut($id),
    $method === 'DELETE' && $id !== null => handleDelete($id),

    default => respond(405, ['error' => 'Method not allowed.']),

};
