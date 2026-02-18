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

// Normalise a DB row → JS-friendly shape (matches State field names)
function toStateShape(array $row): array
{
    return [
        'id'          => $row['id'],
        'type'        => $row['type'],
        'amount'      => (float) $row['amount'],
        'category'    => $row['category']     ?? '',
        'committee'   => $row['committee_id'] ?? '',   // State uses 'committee'
        'desc'        => $row['description']  ?? '',   // State uses 'desc'
        'date'        => $row['tx_date']      ?? '',   // State uses 'date'
        'memberId'    => $row['member_id']    ?? '',   // State uses camelCase
        'periodId'    => $row['period_id']    ?? '',
        'created_at'  => $row['created_at']   ?? '',
    ];
}

// ──────────────────────────────────────────────────────────────
// VALIDATION
// ──────────────────────────────────────────────────────────────

const VALID_TYPES = ['إيراد', 'مصروف'];

function validatePayload(array $data, bool $requireAll = true): array
{
    $fields = [];

    if ($requireAll || array_key_exists('type', $data)) {
        $v = sanitizeString($data['type'] ?? '', 'type');
        if ($requireAll && !in_array($v, VALID_TYPES, true)) {
            respond(422, ['error' => '"type" must be one of: ' . implode(', ', VALID_TYPES)]);
        }
        if ($v !== '' && !in_array($v, VALID_TYPES, true)) {
            respond(422, ['error' => '"type" must be one of: ' . implode(', ', VALID_TYPES)]);
        }
        if ($v !== '') $fields['type'] = $v;
    }

    if ($requireAll || array_key_exists('amount', $data)) {
        $v = $data['amount'] ?? null;
        if ($requireAll && ($v === null || !is_numeric($v))) {
            respond(422, ['error' => '"amount" is required and must be numeric.']);
        }
        if ($v !== null && !is_numeric($v)) {
            respond(422, ['error' => '"amount" must be numeric.']);
        }
        if ($v !== null) $fields['amount'] = (float) $v;
    }

    if ($requireAll || array_key_exists('description', $data)) {
        // Accept both 'description' (API) and 'desc' (State alias)
        $v = sanitizeString(
            $data['description'] ?? $data['desc'] ?? '',
            'description'
        );
        if ($requireAll && $v === '') {
            respond(422, ['error' => '"description" is required.']);
        }
        if ($v !== '') $fields['description'] = $v;
    }

    if ($requireAll || array_key_exists('tx_date', $data) || array_key_exists('date', $data)) {
        // Accept both 'tx_date' (API) and 'date' (State alias)
        $v = sanitizeString(
            $data['tx_date'] ?? $data['date'] ?? '',
            'tx_date'
        );
        if ($v !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            respond(422, ['error' => '"tx_date" must be YYYY-MM-DD or empty.']);
        }
        $fields['tx_date'] = $v === '' ? date('Y-m-d') : $v;
    }

    if (array_key_exists('category', $data)) {
        $fields['category'] = sanitizeString($data['category'], 'category');
    } elseif ($requireAll) {
        $fields['category'] = '';
    }

    if (array_key_exists('committee_id', $data) || array_key_exists('committee', $data)) {
        // Accept both 'committee_id' (API) and 'committee' (State alias)
        $v = sanitizeString(
            $data['committee_id'] ?? $data['committee'] ?? '',
            'committee_id'
        );
        $fields['committee_id'] = $v;
    } elseif ($requireAll) {
        $fields['committee_id'] = '';
    }

    // Optional FK fields — null if empty
    foreach (['member_id' => 'memberId', 'period_id' => 'periodId'] as $apiKey => $stateKey) {
        if (array_key_exists($apiKey, $data) || array_key_exists($stateKey, $data)) {
            $v = sanitizeString($data[$apiKey] ?? $data[$stateKey] ?? '', $apiKey);
            $fields[$apiKey] = $v === '' ? null : $v;
        } elseif ($requireAll) {
            $fields[$apiKey] = null;
        }
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

    // Filter: type (إيراد / مصروف)
    if (!empty($_GET['type'])) {
        $t = $_GET['type'];
        if (!in_array($t, VALID_TYPES, true)) {
            respond(422, ['error' => 'Invalid type filter.']);
        }
        $where[]        = 'type = :type';
        $params[':type'] = $t;
    }

    // Filter: date_from
    if (!empty($_GET['date_from'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_from'])) {
            respond(422, ['error' => 'date_from must be YYYY-MM-DD.']);
        }
        $where[]             = 'tx_date >= :date_from';
        $params[':date_from'] = $_GET['date_from'];
    }

    // Filter: date_to
    if (!empty($_GET['date_to'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_to'])) {
            respond(422, ['error' => 'date_to must be YYYY-MM-DD.']);
        }
        $where[]           = 'tx_date <= :date_to';
        $params[':date_to'] = $_GET['date_to'];
    }

    // Filter: committee_id
    if (isset($_GET['committee_id']) && $_GET['committee_id'] !== '') {
        $where[]                  = 'committee_id = :committee_id';
        $params[':committee_id']   = $_GET['committee_id'];
    }

    // Filter: member_id
    if (!empty($_GET['member_id'])) {
        $where[]              = 'member_id = :member_id';
        $params[':member_id'] = $_GET['member_id'];
    }

    // Filter: period_id
    if (!empty($_GET['period_id'])) {
        $where[]              = 'period_id = :period_id';
        $params[':period_id'] = $_GET['period_id'];
    }

    $sql = 'SELECT * FROM transactions';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY tx_date DESC, created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map('toStateShape', $stmt->fetchAll());

    respond(200, ['data' => $rows, 'total' => count($rows)]);
}

function handleGetOne(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'Transaction not found.']);
    }

    respond(200, ['data' => toStateShape($row)]);
}

function handlePost(): void
{
    $pdo    = getPDO();
    $data   = bodyJson();
    $fields = validatePayload($data, requireAll: true);

    $id = $data['id'] ?? uid();   // Accept explicit id (from sample data seeding)
    if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', (string) $id)) {
        $id = uid();
    }

    $pdo->prepare(
        'INSERT INTO transactions
            (id, type, amount, category, committee_id, description, tx_date, member_id, period_id)
         VALUES
            (:id, :type, :amount, :category, :committee_id, :description, :tx_date, :member_id, :period_id)'
    )->execute([
        ':id'           => $id,
        ':type'         => $fields['type'],
        ':amount'       => $fields['amount'],
        ':category'     => $fields['category'],
        ':committee_id' => $fields['committee_id'],
        ':description'  => $fields['description'],
        ':tx_date'      => $fields['tx_date'],
        ':member_id'    => $fields['member_id'],
        ':period_id'    => $fields['period_id'],
    ]);

    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(201, ['data' => toStateShape($stmt->fetch())]);
}

function handleBulkPost(): void
{
    // POST /api/transactions.php?bulk=1
    // Body: { transactions: [...] }
    // Returns { inserted, duplicates_skipped }
    $pdo  = getPDO();
    $body = bodyJson();

    if (empty($body['transactions']) || !is_array($body['transactions'])) {
        respond(422, ['error' => '"transactions" array is required.']);
    }

    $inserted  = 0;
    $skipped   = 0;
    $inserted_rows = [];

    $checkStmt = $pdo->prepare(
        'SELECT id FROM transactions
         WHERE description = :desc AND ABS(amount - :amount) < 0.01 AND tx_date = :date
         LIMIT 1'
    );

    $insertStmt = $pdo->prepare(
        'INSERT INTO transactions
            (id, type, amount, category, committee_id, description, tx_date, member_id, period_id)
         VALUES
            (:id, :type, :amount, :category, :committee_id, :description, :tx_date, :member_id, :period_id)'
    );

    foreach ($body['transactions'] as $item) {
        $fields = validatePayload((array) $item, requireAll: true);

        // Duplicate check (description + amount + date)
        $checkStmt->execute([
            ':desc'   => $fields['description'],
            ':amount' => $fields['amount'],
            ':date'   => $fields['tx_date'],
        ]);

        if ($checkStmt->fetch()) {
            $skipped++;
            continue;
        }

        $id = $item['id'] ?? uid();
        if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', (string) $id)) {
            $id = uid();
        }

        $insertStmt->execute([
            ':id'           => $id,
            ':type'         => $fields['type'],
            ':amount'       => $fields['amount'],
            ':category'     => $fields['category'],
            ':committee_id' => $fields['committee_id'],
            ':description'  => $fields['description'],
            ':tx_date'      => $fields['tx_date'],
            ':member_id'    => $fields['member_id'],
            ':period_id'    => $fields['period_id'],
        ]);

        $fetchStmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id LIMIT 1');
        $fetchStmt->execute([':id' => $id]);
        $inserted_rows[] = toStateShape($fetchStmt->fetch());
        $inserted++;
    }

    respond(200, [
        'inserted'          => $inserted,
        'duplicates_skipped' => $skipped,
        'data'              => $inserted_rows,
    ]);
}

function handlePut(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM transactions WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        respond(404, ['error' => 'Transaction not found.']);
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
        'UPDATE transactions SET ' . implode(', ', $setClauses) . ' WHERE id = :id'
    )->execute($params);

    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(200, ['data' => toStateShape($stmt->fetch())]);
}

function handleDelete(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM transactions WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        respond(404, ['error' => 'Transaction not found.']);
    }

    $pdo->prepare('DELETE FROM transactions WHERE id = :id')->execute([':id' => $id]);

    respond(200, ['message' => 'Transaction deleted.']);
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();

try {
    match (true) {
        $method === 'POST'   && isset($_GET['bulk'])  => handleBulkPost(),
        $method === 'GET'    && $id === null          => handleGetAll(),
        $method === 'GET'    && $id !== null          => handleGetOne($id),
        $method === 'POST'                            => handlePost(),
        $method === 'PUT'   && $id !== null           => handlePut($id),
        $method === 'DELETE' && $id !== null          => handleDelete($id),
        $method === 'PUT'   && $id === null           => respond(400, ['error' => '?id= required for PUT.']),
        $method === 'DELETE' && $id === null          => respond(400, ['error' => '?id= required for DELETE.']),
        default                                       => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error.', 'detail' => $e->getMessage()]);
}
