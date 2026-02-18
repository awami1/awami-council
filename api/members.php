<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/*
|--------------------------------------------------------------------------
| ROUTER HELPERS
|--------------------------------------------------------------------------
*/

function parseId(): ?string
{
    $id = $_GET['id'] ?? null;
    if ($id !== null && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id)) {
        respond(400, ['error' => 'Invalid ID format.']);
    }
    return $id;
}

/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

const VALID_STATUSES = ['نشط', 'معفي', 'غير نشط'];

function validateMemberPayload(array $data, bool $requireAll = true): array
{
    $fields = [];

    if ($requireAll || array_key_exists('name', $data)) {
        $name = trim((string)($data['name'] ?? ''));
        if ($requireAll && $name === '') {
            respond(422, ['error' => 'Field "name" is required.']);
        }
        $fields['name'] = $name;
    }

    if ($requireAll || array_key_exists('family', $data)) {
        $fields['family'] = trim((string)($data['family'] ?? ''));
    }

    if ($requireAll || array_key_exists('phone', $data)) {
        $fields['phone'] = trim((string)($data['phone'] ?? ''));
    }

    if ($requireAll || array_key_exists('id_num', $data)) {
        $fields['id_num'] = trim((string)($data['id_num'] ?? ''));
    }

    if ($requireAll || array_key_exists('join_date', $data)) {
        $join = trim((string)($data['join_date'] ?? ''));
        if ($join !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $join)) {
            respond(422, ['error' => 'join_date must be YYYY-MM-DD']);
        }
        $fields['join_date'] = $join === '' ? null : $join;
    }

    if ($requireAll || array_key_exists('status', $data)) {
        $status = trim((string)($data['status'] ?? 'نشط'));
        if (!in_array($status, VALID_STATUSES, true)) {
            respond(422, ['error' => 'Invalid status value.']);
        }
        $fields['status'] = $status;
    }

    if ($requireAll || array_key_exists('notes', $data)) {
        $fields['notes'] = trim((string)($data['notes'] ?? ''));
    }

    if (array_key_exists('branch_id', $data)) {
        $fields['branch_id'] = $data['branch_id'] ?: null;
    }

    return $fields;
}

/*
|--------------------------------------------------------------------------
| HANDLERS
|--------------------------------------------------------------------------
*/

function handleGetAll(): void
{
    $pdo = getPDO();

    $stmt = $pdo->query("SELECT * FROM members ORDER BY name ASC");
    $members = $stmt->fetchAll();

    respond(200, [
        'data'  => $members,
        'total' => count($members),
    ]);
}

function handleGetOne(string $id): void
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);

    $member = $stmt->fetch();
    if (!$member) {
        respond(404, ['error' => 'Member not found.']);
    }

    respond(200, ['data' => $member]);
}

function handlePost(): void
{
    $pdo = getPDO();
    $data = bodyJson();
    $fields = validateMemberPayload($data, true);

    $id = bin2hex(random_bytes(8));

    $stmt = $pdo->prepare("
        INSERT INTO members
        (id, name, family, phone, id_num, join_date, status, notes, branch_id)
        VALUES
        (:id, :name, :family, :phone, :id_num, :join_date, :status, :notes, :branch_id)
    ");

    $stmt->execute([
        ':id'        => $id,
        ':name'      => $fields['name'],
        ':family'    => $fields['family'] ?? '',
        ':phone'     => $fields['phone'] ?? '',
        ':id_num'    => $fields['id_num'] ?? '',
        ':join_date' => $fields['join_date'] ?? null,
        ':status'    => $fields['status'],
        ':notes'     => $fields['notes'] ?? '',
        ':branch_id' => $fields['branch_id'] ?? null,
    ]);

    handleGetOne($id);
}

function handlePut(string $id): void
{
    $pdo = getPDO();

    $data = bodyJson();
    $fields = validateMemberPayload($data, false);

    if (!$fields) {
        respond(422, ['error' => 'No valid fields provided.']);
    }

    $set = [];
    foreach ($fields as $key => $val) {
        $set[] = "{$key} = :{$key}";
    }

    $sql = "UPDATE members SET " . implode(', ', $set) . " WHERE id = :id";
    $fields['id'] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($fields);

    handleGetOne($id);
}

function handleDelete(string $id): void
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("DELETE FROM members WHERE id = :id");
    $stmt->execute([':id' => $id]);

    respond(200, ['message' => 'Member deleted']);
}

/*
|--------------------------------------------------------------------------
| ROUTER
|--------------------------------------------------------------------------
*/

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();

match (true) {
    $method === 'GET'    && $id === null => handleGetAll(),
    $method === 'GET'    && $id !== null => handleGetOne($id),
    $method === 'POST'                   => handlePost(),
    $method === 'PUT'   && $id !== null  => handlePut($id),
    $method === 'DELETE' && $id !== null => handleDelete($id),
    default                              => respond(405, ['error' => 'Method not allowed']),
};
