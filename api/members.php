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

function sanitizeString(mixed $value, string $field): string
{
    if (!is_string($value)) {
        respond(422, ['error' => "Field '{$field}' must be a string."]);
    }
    return trim($value);
}

function parseId(): ?string
{
    // Accept /api/members.php?id=abc or /api/members/abc via rewrite
    $id = $_GET['id'] ?? null;
    if ($id !== null && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id)) {
        respond(400, ['error' => 'Invalid ID format.']);
    }
    return $id;
}

// ──────────────────────────────────────────────────────────────
// VALIDATION
// ──────────────────────────────────────────────────────────────

const VALID_STATUSES = ['نشط', 'معفي', 'غير نشط'];

function validateMemberPayload(array $data, bool $requireAll = true): array
{
    $fields = [];

    if ($requireAll || array_key_exists('name', $data)) {
        $name = sanitizeString($data['name'] ?? '', 'name');
        if ($requireAll && $name === '') {
            respond(422, ['error' => 'Field "name" is required.']);
        }
        $fields['name'] = $name;
    }

    if ($requireAll || array_key_exists('family', $data)) {
        $fields['family'] = sanitizeString($data['family'] ?? '', 'family');
    }

    if ($requireAll || array_key_exists('phone', $data)) {
        $fields['phone'] = sanitizeString($data['phone'] ?? '', 'phone');
    }

    if ($requireAll || array_key_exists('id_num', $data)) {
        $fields['id_num'] = sanitizeString($data['id_num'] ?? '', 'id_num');
    }

    if ($requireAll || array_key_exists('join_date', $data)) {
        $raw = sanitizeString($data['join_date'] ?? '', 'join_date');
        if ($raw !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            respond(422, ['error' => 'Field "join_date" must be YYYY-MM-DD.']);
        }
        $fields['join_date'] = $raw === '' ? null : $raw;
    }

    if ($requireAll || array_key_exists('status', $data)) {
        $status = sanitizeString($data['status'] ?? 'نشط', 'status');
        if (!in_array($status, VALID_STATUSES, true)) {
            respond(422, ['error' => 'Field "status" must be one of: ' . implode(', ', VALID_STATUSES)]);
        }
        $fields['status'] = $status;
    }

    if ($requireAll || array_key_exists('notes', $data)) {
        $fields['notes'] = sanitizeString($data['notes'] ?? '', 'notes');
    }

    if (array_key_exists('branch_id', $data)) {
        $bid = $data['branch_id'];
        if ($bid !== null && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', (string) $bid)) {
            respond(422, ['error' => 'Invalid "branch_id" format.']);
        }
        $fields['branch_id'] = $bid === '' ? null : $bid;
    }

    return $fields;
}

// ──────────────────────────────────────────────────────────────
// HANDLERS
// ──────────────────────────────────────────────────────────────

function handleGetAll(): void
{
    $pdo = getPDO();

    // Optional filters
    $where  = [];
    $params = [];

    if (!empty($_GET['status'])) {
        $where[]          = 'status = :status';
        $params[':status'] = $_GET['status'];
    }

    if (!empty($_GET['search'])) {
        $where[]          = '(name LIKE :search OR phone LIKE :search OR id_num LIKE :search)';
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    $sql = 'SELECT * FROM members';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY name ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll();

    respond(200, [
        'data'  => $members,
        'total' => count($members),
    ]);
}

function handleGetOne(string $id): void
{
    $pdo  = getPDO();
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
    $data   = bodyJson();
    $fields = validateMemberPayload($data, requireAll: true);

    // Default status if omitted
    if (!isset($fields['status'])) {
        $fields['status'] = 'نشط';
    }

    $pdo = getPDO();

    // Check unique id_num if provided
    if (!empty($fields['id_num'])) {
        $chk = $pdo->prepare('SELECT id FROM members WHERE id_num = :id_num LIMIT 1');
        $chk->execute([':id_num' => $fields['id_num']]);
        if ($chk->fetch()) {
            respond(409, ['error' => 'A member with this ID number already exists.']);
        }
    }

    $id = uid();

    $stmt = $pdo->prepare(
        'INSERT INTO members
            (id, name, family, phone, id_num, join_date, status, notes, branch_id)
         VALUES
            (:id, :name, :family, :phone, :id_num, :join_date, :status, :notes, :branch_id)'
    );

    $stmt->execute([
        ':id'        => $id,
        ':name'      => $fields['name'],
        ':family'    => $fields['family']   ?? '',
        ':phone'     => $fields['phone']    ?? '',
        ':id_num'    => $fields['id_num']   ?? '',
        ':join_date' => $fields['join_date'] ?? null,
        ':status'    => $fields['status'],
        ':notes'     => $fields['notes']    ?? '',
        ':branch_id' => $fields['branch_id'] ?? null,
    ]);

    $fetch = $pdo->prepare('SELECT * FROM members WHERE id = :id LIMIT 1');
    $fetch->execute([':id' => $id]);
    $member = $fetch->fetch();

    respond(201, ['data' => $member]);
}

function handlePut(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM members WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        respond(404, ['error' => 'Member not found.']);
    }

    $data   = bodyJson();
    $fields = validateMemberPayload($data, requireAll: false);

    if (empty($fields)) {
        respond(422, ['error' => 'No valid fields provided for update.']);
    }

    // Check id_num uniqueness if being changed
    if (!empty($fields['id_num'])) {
        $chk = $pdo->prepare('SELECT id FROM members WHERE id_num = :id_num AND id != :id LIMIT 1');
        $chk->execute([':id_num' => $fields['id_num'], ':id' => $id]);
        if ($chk->fetch()) {
            respond(409, ['error' => 'Another member already has this ID number.']);
        }
    }

    $setClauses = [];
    $params     = [':id' => $id];

    foreach ($fields as $col => $val) {
        $setClauses[]     = "{$col} = :{$col}";
        $params[":{$col}"] = $val;
    }

    $sql = 'UPDATE members SET ' . implode(', ', $setClauses) . ' WHERE id = :id';
    $pdo->prepare($sql)->execute($params);

    $fetch = $pdo->prepare('SELECT * FROM members WHERE id = :id LIMIT 1');
    $fetch->execute([':id' => $id]);
    $member = $fetch->fetch();

    respond(200, ['data' => $member]);
}

function handleDelete(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id, name FROM members WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $member = $stmt->fetch();

    if (!$member) {
        respond(404, ['error' => 'Member not found.']);
    }

    // ON DELETE CASCADE in schema handles payments + committee_members
    $pdo->prepare('DELETE FROM members WHERE id = :id')->execute([':id' => $id]);

    respond(200, ['message' => "Member '{$member['name']}' deleted successfully."]);
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();

// Setup route — visit /api/members.php?setup=1 once to create all tables
if (isset($_GET['setup'])) {
    $pdo = getPDO();
    $tables = [
"CREATE TABLE IF NOT EXISTS `family_branches` (`id` VARCHAR(36) NOT NULL, `name` VARCHAR(200) NOT NULL, `head` VARCHAR(200) DEFAULT NULL, `count` INT NOT NULL DEFAULT 0, `color` VARCHAR(20) NOT NULL DEFAULT '#47915C', `notes` TEXT, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS `members` (`id` VARCHAR(36) NOT NULL, `name` VARCHAR(200) NOT NULL, `family` VARCHAR(200) NOT NULL DEFAULT '', `phone` VARCHAR(20) DEFAULT NULL, `id_num` VARCHAR(20) DEFAULT NULL, `join_date` DATE DEFAULT NULL, `status` ENUM('نشط','معفي','غير نشط') NOT NULL DEFAULT 'نشط', `notes` TEXT, `branch_id` VARCHAR(36) DEFAULT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `uq_id_num` (`id_num`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS `periods` (`id` VARCHAR(36) NOT NULL, `name` VARCHAR(200) NOT NULL, `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0, `start_date` DATE DEFAULT NULL, `end_date` DATE DEFAULT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS `payments` (`id` VARCHAR(36) NOT NULL, `member_id` VARCHAR(36) NOT NULL, `period_id` VARCHAR(36) NOT NULL, `amount` DECIMAL(10,2) NOT NULL DEFAULT 0, `required` DECIMAL(10,2) NOT NULL DEFAULT 0, `pay_date` DATE DEFAULT NULL, `method` VARCHAR(100) DEFAULT NULL, `status` ENUM('مدفوع','لم يدفع','معفي') NOT NULL DEFAULT 'لم يدفع', `notes` TEXT, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `uq_member_period` (`member_id`, `period_id`), CONSTRAINT `fk_pay_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE, CONSTRAINT `fk_pay_period` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS `transactions` (`id` VARCHAR(36) NOT NULL, `type` ENUM('إيراد','مصروف') NOT NULL, `amount` DECIMAL(10,2) NOT NULL DEFAULT 0, `category` VARCHAR(100) DEFAULT NULL, `committee_id` VARCHAR(36) DEFAULT NULL, `description` VARCHAR(500) NOT NULL DEFAULT '', `tx_date` DATE DEFAULT NULL, `member_id` VARCHAR(36) DEFAULT NULL, `period_id` VARCHAR(36) DEFAULT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS `events` (`id` VARCHAR(36) NOT NULL, `name` VARCHAR(200) NOT NULL, `committee_id` VARCHAR(36) DEFAULT NULL, `status` ENUM('قادم','جاري','مكتمل','ملغي') NOT NULL DEFAULT 'قادم', `event_date` DATE DEFAULT NULL, `budget` DECIMAL(10,2) NOT NULL DEFAULT 0, `participants` INT NOT NULL DEFAULT 0, `lead` VARCHAR(200) DEFAULT NULL, `notes` TEXT, `icon` VARCHAR(10) DEFAULT '🎉', `images` JSON DEFAULT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS `polls` (`id` VARCHAR(36) NOT NULL, `title` VARCHAR(500) NOT NULL, `options` JSON DEFAULT NULL, `committee_id` VARCHAR(36) NOT NULL DEFAULT '', `end_date` DATE DEFAULT NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1, `created_date` DATE NOT NULL DEFAULT (CURDATE()), `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS `poll_options` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `poll_id` VARCHAR(36) NOT NULL, `sort_order` TINYINT NOT NULL DEFAULT 0, `text` VARCHAR(500) NOT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), CONSTRAINT `fk_po_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS `poll_votes` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `poll_id` VARCHAR(36) NOT NULL, `option_id` BIGINT UNSIGNED NOT NULL, `user_id` VARCHAR(100) NOT NULL DEFAULT 'user_default', `voted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `uq_pv_poll_user` (`poll_id`, `user_id`), CONSTRAINT `fk_pv_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE, CONSTRAINT `fk_pv_option` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS `committee_members` (`committee_id` VARCHAR(36) NOT NULL, `member_id` VARCHAR(36) NOT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`committee_id`, `member_id`), CONSTRAINT `fk_cm_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS `website_settings` (`id` INT NOT NULL DEFAULT 1, `data` JSON NOT NULL, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
    $errors = []; $created = [];
    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
            preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/', $sql, $m);
            $created[] = $m[1] ?? '?';
        } catch (PDOException $e) {
            $errors[] = $e->getMessage();
        }
    }
    respond(empty($errors) ? 200 : 500, [
        'status'  => empty($errors) ? 'SUCCESS — remove ?setup=1 from URL when done' : 'PARTIAL',
        'created' => $created,
        'errors'  => $errors,
    ]);
}

try {
    match (true) {
        $method === 'GET'    && $id === null => handleGetAll(),
        $method === 'GET'    && $id !== null => handleGetOne($id),
        $method === 'POST'                   => handlePost(),
        $method === 'PUT'   && $id !== null  => handlePut($id),
        $method === 'DELETE' && $id !== null => handleDelete($id),
        $method === 'PUT'   && $id === null  => respond(400, ['error' => '?id= is required for PUT.']),
        $method === 'DELETE' && $id === null => respond(400, ['error' => '?id= is required for DELETE.']),
        default                              => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error.', 'detail' => $e->getMessage()]);
}
