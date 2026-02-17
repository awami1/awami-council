<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

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

// Normalise DB row → JS State shape
// Maps MySQL column names back to the camelCase names State uses
function toStateShape(array $row): array
{
    $images = [];
    if (!empty($row['images'])) {
        $decoded = json_decode($row['images'], true);
        // Store as plain URL strings; JS renders them via url('...')
        $images = is_array($decoded) ? $decoded : [];
    }

    return [
        'id'           => $row['id'],
        'name'         => $row['name'],
        'committeeId'  => $row['committee_id'] ?? '',   // State uses camelCase
        'status'       => $row['status'],
        'date'         => $row['event_date']   ?? '',   // State uses 'date'
        'budget'       => (float) ($row['budget']       ?? 0),
        'participants' => (int)   ($row['participants']  ?? 0),
        'lead'         => $row['lead']         ?? '',
        'notes'        => $row['notes']        ?? '',
        'icon'         => $row['icon']         ?? '🎉',
        'images'       => $images,
        'created_at'   => $row['created_at']   ?? '',
    ];
}

// ──────────────────────────────────────────────────────────────
// VALIDATION
// ──────────────────────────────────────────────────────────────

const VALID_STATUSES = ['قادم', 'جاري', 'مكتمل', 'ملغي'];

function validatePayload(array $data, bool $requireAll = true): array
{
    $fields = [];

    // name — required on create
    if ($requireAll || array_key_exists('name', $data)) {
        $v = sanitizeString($data['name'] ?? '', 'name');
        if ($requireAll && $v === '') {
            respond(422, ['error' => '"name" is required.']);
        }
        if ($v !== '') $fields['name'] = $v;
    }

    // committee_id — accepts both 'committee_id' (API) and 'committeeId' (State)
    if (array_key_exists('committee_id', $data) || array_key_exists('committeeId', $data)) {
        $fields['committee_id'] = sanitizeString(
            $data['committee_id'] ?? $data['committeeId'] ?? '',
            'committee_id'
        );
    } elseif ($requireAll) {
        $fields['committee_id'] = '';
    }

    // status
    if ($requireAll || array_key_exists('status', $data)) {
        $v = sanitizeString($data['status'] ?? 'قادم', 'status');
        if (!in_array($v, VALID_STATUSES, true)) {
            respond(422, ['error' => '"status" must be one of: ' . implode(', ', VALID_STATUSES)]);
        }
        $fields['status'] = $v;
    }

    // event_date — accepts both 'event_date' (API) and 'date' (State)
    if ($requireAll || array_key_exists('event_date', $data) || array_key_exists('date', $data)) {
        $v = sanitizeString($data['event_date'] ?? $data['date'] ?? '', 'event_date');
        if ($v !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            respond(422, ['error' => '"event_date" / "date" must be YYYY-MM-DD.']);
        }
        $fields['event_date'] = $v === '' ? null : $v;
    }

    // budget
    if ($requireAll || array_key_exists('budget', $data)) {
        $v = $data['budget'] ?? 0;
        if (!is_numeric($v)) respond(422, ['error' => '"budget" must be numeric.']);
        $fields['budget'] = (float) $v;
    }

    // participants
    if ($requireAll || array_key_exists('participants', $data)) {
        $v = $data['participants'] ?? 0;
        if (!is_numeric($v)) respond(422, ['error' => '"participants" must be numeric.']);
        $fields['participants'] = (int) $v;
    }

    // lead
    if ($requireAll || array_key_exists('lead', $data)) {
        $fields['lead'] = sanitizeString($data['lead'] ?? '', 'lead');
    }

    // notes
    if ($requireAll || array_key_exists('notes', $data)) {
        $fields['notes'] = sanitizeString($data['notes'] ?? '', 'notes');
    }

    // icon
    if ($requireAll || array_key_exists('icon', $data)) {
        $fields['icon'] = sanitizeString($data['icon'] ?? '🎉', 'icon');
    }

    // images — must be an array of URL path strings (no base64 accepted)
    if ($requireAll || array_key_exists('images', $data)) {
        $imgs = $data['images'] ?? [];
        if (!is_array($imgs)) {
            respond(422, ['error' => '"images" must be an array of URL strings.']);
        }
        // Filter: keep only strings, reject anything resembling a base64 data URI
        $clean = [];
        foreach ($imgs as $img) {
            if (!is_string($img)) continue;
            if (str_starts_with(trim($img), 'data:')) continue; // reject base64
            $clean[] = $img;
        }
        $fields['images'] = json_encode($clean, JSON_UNESCAPED_UNICODE);
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

    // Filter: status
    if (!empty($_GET['status'])) {
        $s = $_GET['status'];
        if (!in_array($s, VALID_STATUSES, true)) {
            respond(422, ['error' => 'Invalid status filter.']);
        }
        $where[]         = 'status = :status';
        $params[':status'] = $s;
    }

    // Filter: committee_id
    if (isset($_GET['committee_id']) && $_GET['committee_id'] !== '') {
        $where[]                 = 'committee_id = :committee_id';
        $params[':committee_id'] = $_GET['committee_id'];
    }

    // Filter: date_from
    if (!empty($_GET['date_from'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_from'])) {
            respond(422, ['error' => 'date_from must be YYYY-MM-DD.']);
        }
        $where[]              = 'event_date >= :date_from';
        $params[':date_from'] = $_GET['date_from'];
    }

    // Filter: date_to
    if (!empty($_GET['date_to'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_to'])) {
            respond(422, ['error' => 'date_to must be YYYY-MM-DD.']);
        }
        $where[]            = 'event_date <= :date_to';
        $params[':date_to'] = $_GET['date_to'];
    }

    $sql = 'SELECT * FROM events';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY event_date DESC, created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map('toStateShape', $stmt->fetchAll());

    respond(200, ['data' => $rows, 'total' => count($rows)]);
}

function handleGetOne(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'Event not found.']);
    }

    respond(200, ['data' => toStateShape($row)]);
}

function handlePost(): void
{
    $pdo    = getPDO();
    $data   = bodyJson();
    $fields = validatePayload($data, requireAll: true);

    // Accept explicit id for sample-data seeding
    $id = isset($data['id']) && preg_match('/^[a-zA-Z0-9_-]{1,64}$/', (string) $data['id'])
        ? $data['id']
        : uid();

    $pdo->prepare(
        'INSERT INTO events
            (id, name, committee_id, status, event_date, budget,
             participants, lead, notes, icon, images)
         VALUES
            (:id, :name, :committee_id, :status, :event_date, :budget,
             :participants, :lead, :notes, :icon, :images)'
    )->execute([
        ':id'           => $id,
        ':name'         => $fields['name'],
        ':committee_id' => $fields['committee_id'],
        ':status'       => $fields['status'],
        ':event_date'   => $fields['event_date'],
        ':budget'       => $fields['budget'],
        ':participants' => $fields['participants'],
        ':lead'         => $fields['lead'],
        ':notes'        => $fields['notes'],
        ':icon'         => $fields['icon'],
        ':images'       => $fields['images'],
    ]);

    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(201, ['data' => toStateShape($stmt->fetch())]);
}

function handlePut(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM events WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        respond(404, ['error' => 'Event not found.']);
    }

    $data   = bodyJson();
    $fields = validatePayload($data, requireAll: false);

    if (empty($fields)) {
        respond(422, ['error' => 'No valid fields provided for update.']);
    }

    $setClauses = [];
    $params     = [':id' => $id];

    foreach ($fields as $col => $val) {
        $setClauses[]      = "{$col} = :{$col}";
        $params[":{$col}"] = $val;
    }

    $pdo->prepare(
        'UPDATE events SET ' . implode(', ', $setClauses) . ' WHERE id = :id'
    )->execute($params);

    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(200, ['data' => toStateShape($stmt->fetch())]);
}

function handleDelete(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT name FROM events WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'Event not found.']);
    }

    $pdo->prepare('DELETE FROM events WHERE id = :id')->execute([':id' => $id]);

    respond(200, ['message' => "Event '{$row['name']}' deleted."]);
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();

try {
    match (true) {
        $method === 'GET'    && $id === null  => handleGetAll(),
        $method === 'GET'    && $id !== null  => handleGetOne($id),
        $method === 'POST'                    => handlePost(),
        $method === 'PUT'   && $id !== null   => handlePut($id),
        $method === 'DELETE' && $id !== null  => handleDelete($id),
        $method === 'PUT'   && $id === null   => respond(400, ['error' => '?id= required for PUT.']),
        $method === 'DELETE' && $id === null  => respond(400, ['error' => '?id= required for DELETE.']),
        default                               => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error.', 'detail' => $e->getMessage()]);
}
