<?php

declare(strict_types=1);

function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
    $name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? '');
    $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? '');
    $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
    $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');

    if (!$host || !$name || !$user) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Database configuration missing.']);
        exit;
    }

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user, $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Database connection failed.']);
        exit;
    }

    return $pdo;
}

// ──────────────────────────────────────────────────────────────
// CORS
// ──────────────────────────────────────────────────────────────

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ──────────────────────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────────────────────

function respond(int $status, mixed $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bodyJson(): array
{
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        respond(400, ['error' => 'Request body is empty.']);
    }
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        respond(400, ['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    }
    return $data;
}

function uid(): string
{
    return bin2hex(random_bytes(8));
}

function parseId(): ?string
{
    $id = $_GET['id'] ?? null;
    if ($id !== null && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id)) {
        respond(400, ['error' => 'Invalid ID format.']);
    }
    return $id;
}

function sanitizeString(mixed $value, string $field): string
{
    if (!is_string($value) && !is_numeric($value)) {
        respond(422, ['error' => "Field '{$field}' must be a string."]);
    }
    return trim((string) $value);
}

// ──────────────────────────────────────────────────────────────
// VALIDATION
// ──────────────────────────────────────────────────────────────

const VALID_STATUSES = ['مدفوع', 'لم يدفع', 'معفي'];

function validatePayload(array $data, bool $requireAll = true): array
{
    $fields = [];

    if ($requireAll || array_key_exists('member_id', $data)) {
        $v = sanitizeString($data['member_id'] ?? '', 'member_id');
        if ($requireAll && $v === '') {
            respond(422, ['error' => '"member_id" is required.']);
        }
        $fields['member_id'] = $v;
    }

    if ($requireAll || array_key_exists('period_id', $data)) {
        $v = sanitizeString($data['period_id'] ?? '', 'period_id');
        if ($requireAll && $v === '') {
            respond(422, ['error' => '"period_id" is required.']);
        }
        $fields['period_id'] = $v;
    }

    if ($requireAll || array_key_exists('amount', $data)) {
        $v = $data['amount'] ?? 0;
        if (!is_numeric($v)) {
            respond(422, ['error' => '"amount" must be numeric.']);
        }
        $fields['amount'] = (float) $v;
    }

    if ($requireAll || array_key_exists('required', $data)) {
        $v = $data['required'] ?? 0;
        if (!is_numeric($v)) {
            respond(422, ['error' => '"required" must be numeric.']);
        }
        $fields['required'] = (float) $v;
    }

    if ($requireAll || array_key_exists('pay_date', $data)) {
        $v = sanitizeString($data['pay_date'] ?? '', 'pay_date');
        if ($v !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            respond(422, ['error' => '"pay_date" must be YYYY-MM-DD or empty.']);
        }
        $fields['pay_date'] = $v === '' ? null : $v;
    }

    if ($requireAll || array_key_exists('method', $data)) {
        $fields['method'] = sanitizeString($data['method'] ?? '', 'method');
    }

    if ($requireAll || array_key_exists('status', $data)) {
        $v = sanitizeString($data['status'] ?? 'لم يدفع', 'status');
        if (!in_array($v, VALID_STATUSES, true)) {
            respond(422, ['error' => '"status" must be one of: ' . implode(', ', VALID_STATUSES)]);
        }
        $fields['status'] = $v;
    }

    if ($requireAll || array_key_exists('notes', $data)) {
        $fields['notes'] = sanitizeString($data['notes'] ?? '', 'notes');
    }

    return $fields;
}

// ──────────────────────────────────────────────────────────────
// HANDLERS
// ──────────────────────────────────────────────────────────────

function handleGetAll(): void
{
    $pdo    = getPDO();
    $where  = [];
    $params = [];

    if (!empty($_GET['member_id'])) {
        $where[]             = 'member_id = :member_id';
        $params[':member_id'] = $_GET['member_id'];
    }

    if (!empty($_GET['period_id'])) {
        $where[]             = 'period_id = :period_id';
        $params[':period_id'] = $_GET['period_id'];
    }

    if (!empty($_GET['status'])) {
        $where[]          = 'status = :status';
        $params[':status'] = $_GET['status'];
    }

    $sql = 'SELECT * FROM payments';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Cast numeric fields for JS
    foreach ($rows as &$row) {
        $row['amount']   = (float) $row['amount'];
        $row['required'] = (float) $row['required'];
    }

    respond(200, ['data' => $rows, 'total' => count($rows)]);
}

function handleGetOne(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'Payment not found.']);
    }

    $row['amount']   = (float) $row['amount'];
    $row['required'] = (float) $row['required'];

    respond(200, ['data' => $row]);
}

function handlePost(): void
{
    $pdo    = getPDO();
    $data   = bodyJson();
    $fields = validatePayload($data, requireAll: true);

    // Check for duplicate member+period
    $chk = $pdo->prepare(
        'SELECT id FROM payments WHERE member_id = :mid AND period_id = :pid LIMIT 1'
    );
    $chk->execute([':mid' => $fields['member_id'], ':pid' => $fields['period_id']]);
    if ($chk->fetch()) {
        respond(409, ['error' => 'A payment record for this member and period already exists. Use PUT to update.']);
    }

    $id = uid();

    $pdo->prepare(
        'INSERT INTO payments
            (id, member_id, period_id, amount, required, pay_date, method, status, notes)
         VALUES
            (:id, :member_id, :period_id, :amount, :required, :pay_date, :method, :status, :notes)'
    )->execute([
        ':id'        => $id,
        ':member_id' => $fields['member_id'],
        ':period_id' => $fields['period_id'],
        ':amount'    => $fields['amount'],
        ':required'  => $fields['required'],
        ':pay_date'  => $fields['pay_date'],
        ':method'    => $fields['method'],
        ':status'    => $fields['status'],
        ':notes'     => $fields['notes'],
    ]);

    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    $row['amount']   = (float) $row['amount'];
    $row['required'] = (float) $row['required'];

    respond(201, ['data' => $row]);
}

function handleUpsertByMemberPeriod(): void
{
    // POST /api/payments.php?upsert=1
    // Creates or updates by (member_id + period_id) — used by savePayment() in JS
    $pdo    = getPDO();
    $data   = bodyJson();
    $fields = validatePayload($data, requireAll: true);

    $chk = $pdo->prepare(
        'SELECT id FROM payments WHERE member_id = :mid AND period_id = :pid LIMIT 1'
    );
    $chk->execute([':mid' => $fields['member_id'], ':pid' => $fields['period_id']]);
    $existing = $chk->fetch();

    if ($existing) {
        $pdo->prepare(
            'UPDATE payments
             SET amount = :amount, required = :required, pay_date = :pay_date,
                 method = :method, status = :status, notes = :notes
             WHERE id = :id'
        )->execute([
            ':id'       => $existing['id'],
            ':amount'   => $fields['amount'],
            ':required' => $fields['required'],
            ':pay_date' => $fields['pay_date'],
            ':method'   => $fields['method'],
            ':status'   => $fields['status'],
            ':notes'    => $fields['notes'],
        ]);
        $id = $existing['id'];
    } else {
        $id = uid();
        $pdo->prepare(
            'INSERT INTO payments
                (id, member_id, period_id, amount, required, pay_date, method, status, notes)
             VALUES
                (:id, :member_id, :period_id, :amount, :required, :pay_date, :method, :status, :notes)'
        )->execute([
            ':id'        => $id,
            ':member_id' => $fields['member_id'],
            ':period_id' => $fields['period_id'],
            ':amount'    => $fields['amount'],
            ':required'  => $fields['required'],
            ':pay_date'  => $fields['pay_date'],
            ':method'    => $fields['method'],
            ':status'    => $fields['status'],
            ':notes'     => $fields['notes'],
        ]);
    }

    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    $row['amount']   = (float) $row['amount'];
    $row['required'] = (float) $row['required'];

    respond(200, ['data' => $row]);
}

function handlePut(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM payments WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        respond(404, ['error' => 'Payment not found.']);
    }

    $data   = bodyJson();
    $fields = validatePayload($data, requireAll: false);

    if (empty($fields)) {
        respond(422, ['error' => 'No valid fields provided for update.']);
    }

    $setClauses = [];
    $params     = [':id' => $id];

    foreach ($fields as $col => $val) {
        $setClauses[]     = "{$col} = :{$col}";
        $params[":{$col}"] = $val;
    }

    $pdo->prepare(
        'UPDATE payments SET ' . implode(', ', $setClauses) . ' WHERE id = :id'
    )->execute($params);

    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    $row['amount']   = (float) $row['amount'];
    $row['required'] = (float) $row['required'];

    respond(200, ['data' => $row]);
}

function handleDelete(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM payments WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        respond(404, ['error' => 'Payment not found.']);
    }

    $pdo->prepare('DELETE FROM payments WHERE id = :id')->execute([':id' => $id]);

    respond(200, ['message' => 'Payment deleted.']);
}

function handleDeleteByMemberPeriod(): void
{
    // DELETE /api/payments.php?member_id=x&period_id=y
    $mid = $_GET['member_id'] ?? '';
    $pid = $_GET['period_id'] ?? '';

    if (!$mid || !$pid) {
        respond(400, ['error' => 'member_id and period_id are required.']);
    }

    $pdo = getPDO();
    $pdo->prepare(
        'DELETE FROM payments WHERE member_id = :mid AND period_id = :pid'
    )->execute([':mid' => $mid, ':pid' => $pid]);

    respond(200, ['message' => 'Payments deleted.']);
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();

try {
    match (true) {
        // Upsert by member+period key
        $method === 'POST'   && isset($_GET['upsert'])                          => handleUpsertByMemberPeriod(),
        // Delete by member+period (no id, but has member_id + period_id)
        $method === 'DELETE' && $id === null && !empty($_GET['member_id'])      => handleDeleteByMemberPeriod(),
        // Standard CRUD
        $method === 'GET'    && $id === null                                    => handleGetAll(),
        $method === 'GET'    && $id !== null                                    => handleGetOne($id),
        $method === 'POST'                                                      => handlePost(),
        $method === 'PUT'    && $id !== null                                    => handlePut($id),
        $method === 'DELETE' && $id !== null                                    => handleDelete($id),
        $method === 'PUT'    && $id === null                                    => respond(400, ['error' => '?id= required for PUT.']),
        $method === 'DELETE' && $id === null                                    => respond(400, ['error' => '?id= or ?member_id=&period_id= required for DELETE.']),
        default                                                                 => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error.', 'detail' => $e->getMessage()]);
}
