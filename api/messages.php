<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
// CSRF verified only for auth-protected routes (PUT/DELETE call requireAuth)
// Public POST uses rate limiting + honeypot instead

// ──────────────────────────────────────────────────────────────
// Rate limiting for public message submission
// ──────────────────────────────────────────────────────────────

function checkMessageRateLimit(): void
{
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file = sys_get_temp_dir() . '/awami_msg_' . md5($ip) . '.json';
    $max  = 5;
    $window = 3600; // 1 hour

    $data = ['attempts' => []];
    if (file_exists($file)) {
        $data = json_decode((string) file_get_contents($file), true) ?: $data;
    }

    $now = time();
    $data['attempts'] = array_values(array_filter(
        $data['attempts'] ?? [],
        fn($t) => ($now - $t) < $window
    ));

    if (count($data['attempts']) >= $max) {
        respond(429, ['error' => 'لقد تجاوزت الحد المسموح من الرسائل. حاول مرة أخرى لاحقاً.']);
    }

    $data['attempts'][] = $now;
    file_put_contents($file, json_encode($data), LOCK_EX);
}

// ──────────────────────────────────────────────────────────────
// إنشاء جدول الرسائل إن لم يكن موجوداً
// ──────────────────────────────────────────────────────────────

function ensureMessagesTable(): void
{
    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            email TEXT NOT NULL DEFAULT '',
            phone TEXT NOT NULL DEFAULT '',
            subject TEXT NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id VARCHAR(64) PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            email VARCHAR(300) NOT NULL DEFAULT '',
            phone VARCHAR(50) NOT NULL DEFAULT '',
            subject VARCHAR(500) NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

ensureMessagesTable();

// ──────────────────────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────────────────────

function parseMsgId(): ?string
{
    $id = $_GET['id'] ?? null;
    if ($id !== null && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id)) {
        respond(400, ['error' => 'Invalid ID format.']);
    }
    return $id;
}

function msgToShape(array $row): array
{
    return [
        'id'         => $row['id'],
        'name'       => $row['name'],
        'email'      => $row['email']      ?? '',
        'phone'      => $row['phone']      ?? '',
        'subject'    => $row['subject']    ?? '',
        'message'    => $row['message'],
        'is_read'    => (bool) ($row['is_read'] ?? 0),
        'created_at' => $row['created_at'] ?? '',
    ];
}

// ──────────────────────────────────────────────────────────────
// HANDLERS
// ──────────────────────────────────────────────────────────────

function handleMsgGetAll(): void
{
    requireAuth();
    $pdo    = getPDO();
    $where  = [];
    $params = [];

    if (isset($_GET['is_read'])) {
        $where[]            = 'is_read = :is_read';
        $params[':is_read'] = (int) $_GET['is_read'];
    }

    $sql = 'SELECT * FROM messages';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map('msgToShape', $stmt->fetchAll());

    // عدد الرسائل غير المقروءة
    $unreadStmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0");
    $unread = (int) $unreadStmt->fetchColumn();

    respond(200, ['data' => $rows, 'total' => count($rows), 'unread' => $unread]);
}

function handleMsgPost(): void
{
    // لا يحتاج auth — الزوار يمكنهم الإرسال
    checkMessageRateLimit();

    $data = bodyJson();

    // Honeypot: if the hidden field is filled, it's a bot
    if (!empty($data['website'])) {
        // Silently accept (don't reveal the trap)
        respond(201, ['message' => 'تم إرسال رسالتك بنجاح. شكراً لتواصلك معنا.']);
    }

    $name    = trim((string) ($data['name']    ?? ''));
    $email   = trim((string) ($data['email']   ?? ''));
    $phone   = trim((string) ($data['phone']   ?? ''));
    $subject = trim((string) ($data['subject'] ?? ''));
    $message = trim((string) ($data['message'] ?? ''));

    if ($name === '') {
        respond(422, ['error' => 'الاسم مطلوب.']);
    }
    if ($message === '') {
        respond(422, ['error' => 'الرسالة مطلوبة.']);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(422, ['error' => 'البريد الإلكتروني غير صالح.']);
    }

    $pdo = getPDO();
    $id  = uid();
    $now = date('Y-m-d H:i:s');

    $pdo->prepare(
        'INSERT INTO messages (id, name, email, phone, subject, message, is_read, created_at)
         VALUES (:id, :name, :email, :phone, :subject, :message, 0, :created_at)'
    )->execute([
        ':id'         => $id,
        ':name'       => $name,
        ':email'      => $email,
        ':phone'      => $phone,
        ':subject'    => $subject,
        ':message'    => $message,
        ':created_at' => $now,
    ]);

    respond(201, ['message' => 'تم إرسال رسالتك بنجاح. شكراً لتواصلك معنا.']);
}

function handleMsgPut(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM messages WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        respond(404, ['error' => 'الرسالة غير موجودة.']);
    }

    $data = bodyJson();

    // فقط تحديث is_read
    if (array_key_exists('is_read', $data)) {
        $pdo->prepare('UPDATE messages SET is_read = :is_read WHERE id = :id')
            ->execute([':is_read' => (int) $data['is_read'], ':id' => $id]);
    }

    $stmt = $pdo->prepare('SELECT * FROM messages WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    respond(200, ['data' => msgToShape($stmt->fetch())]);
}

function handleMsgDelete(string $id): void
{
    requireAuth();
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT name FROM messages WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'الرسالة غير موجودة.']);
    }

    $pdo->prepare('DELETE FROM messages WHERE id = :id')->execute([':id' => $id]);

    respond(200, ['message' => "تم حذف رسالة '{$row['name']}'."]);
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseMsgId();

try {
    match (true) {
        $method === 'GET'    && $id === null  => handleMsgGetAll(),
        $method === 'POST'                    => handleMsgPost(),
        $method === 'PUT'    && $id !== null  => handleMsgPut($id),
        $method === 'DELETE' && $id !== null  => handleMsgDelete($id),
        $method === 'PUT'    && $id === null  => respond(400, ['error' => '?id= required for PUT.']),
        $method === 'DELETE' && $id === null  => respond(400, ['error' => '?id= required for DELETE.']),
        default                               => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (PDOException $e) {
    error_log('PDOException in messages: ' . $e->getMessage());
    respond(500, ['error' => 'خطأ في قاعدة البيانات.']);
}
