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

// ──────────────────────────────────────────────────────────────
// ASSEMBLE POLL — core read function
//
// Builds the exact JS State shape from three tables:
//   polls + poll_options + poll_votes
//
// Returns:
//   {
//     id, title, committee, end, active, created,
//     options: [{ text, votes: [userId, ...] }]
//   }
//
// renderVoting() reads poll.options[i].votes.includes('user_default')
// and poll.options[i].votes.length — this shape satisfies both.
// ──────────────────────────────────────────────────────────────

function assemblePolls(PDO $pdo, array $pollRows): array
{
    if (empty($pollRows)) {
        return [];
    }

    $pollIds     = array_column($pollRows, 'id');
    $placeholders = implode(',', array_fill(0, count($pollIds), '?'));

    // Fetch all options for these polls in sort_order
    $optStmt = $pdo->prepare(
        "SELECT id, poll_id, sort_order, text
         FROM poll_options
         WHERE poll_id IN ({$placeholders})
         ORDER BY poll_id, sort_order ASC"
    );
    $optStmt->execute($pollIds);
    $allOptions = $optStmt->fetchAll();

    // Fetch all votes for these polls
    $voteStmt = $pdo->prepare(
        "SELECT pv.option_id, pv.user_id
         FROM poll_votes pv
         INNER JOIN poll_options po ON po.id = pv.option_id
         WHERE po.poll_id IN ({$placeholders})"
    );
    $voteStmt->execute($pollIds);
    $allVotes = $voteStmt->fetchAll();

    // Index votes by option_id → [userId, ...]
    $votesByOption = [];
    foreach ($allVotes as $v) {
        $votesByOption[$v['option_id']][] = $v['user_id'];
    }

    // Index options by poll_id
    $optionsByPoll = [];
    foreach ($allOptions as $opt) {
        $optionsByPoll[$opt['poll_id']][] = [
            'text'  => $opt['text'],
            'votes' => $votesByOption[$opt['id']] ?? [],
            '_id'   => (int) $opt['id'],   // internal, used by vote endpoint
        ];
    }

    // Assemble final shape
    $result = [];
    foreach ($pollRows as $row) {
        $result[] = [
            'id'        => $row['id'],
            'title'     => $row['title'],
            'committee' => $row['committee_id'] ?? '',     // State uses 'committee'
            'end'       => $row['end_date']     ?? '',     // State uses 'end'
            'active'    => (bool) $row['is_active'],
            'created'   => $row['created_date'] ?? '',
            'options'   => $optionsByPoll[$row['id']] ?? [],
        ];
    }

    return $result;
}

// ──────────────────────────────────────────────────────────────
// HANDLERS
// ──────────────────────────────────────────────────────────────

function handleGetAll(): void
{
    $pdo    = getPDO();
    $where  = [];
    $params = [];

    // Filter: active only
    if (isset($_GET['active'])) {
        $where[]           = 'is_active = :is_active';
        $params[':is_active'] = (int) ($_GET['active'] === '1' || $_GET['active'] === 'true');
    }

    // Filter: committee_id
    if (isset($_GET['committee_id']) && $_GET['committee_id'] !== '') {
        $where[]                 = 'committee_id = :committee_id';
        $params[':committee_id'] = $_GET['committee_id'];
    }

    $sql = 'SELECT * FROM polls';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $polls = assemblePolls($pdo, $rows);

    respond(200, ['data' => $polls, 'total' => count($polls)]);
}

function handleGetOne(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM polls WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(404, ['error' => 'Poll not found.']);
    }

    $polls = assemblePolls($pdo, [$row]);
    respond(200, ['data' => $polls[0]]);
}

function handleCreatePoll(): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    // title
    $title = sanitizeString($data['title'] ?? '', 'title');
    if ($title === '') {
        respond(422, ['error' => '"title" is required.']);
    }

    // options — array of strings
    $optionsRaw = $data['options'] ?? [];
    if (!is_array($optionsRaw) || count($optionsRaw) < 2) {
        respond(422, ['error' => '"options" must be an array of at least 2 strings.']);
    }
    $options = [];
    foreach ($optionsRaw as $o) {
        $t = sanitizeString($o, 'option');
        if ($t !== '') $options[] = $t;
    }
    if (count($options) < 2) {
        respond(422, ['error' => 'At least 2 non-empty options are required.']);
    }

    // committee (soft ref — no FK)
    $committee = sanitizeString($data['committee'] ?? '', 'committee');

    // end_date
    $end = sanitizeString($data['end'] ?? '', 'end');
    if ($end !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
        respond(422, ['error' => '"end" must be YYYY-MM-DD.']);
    }

    // Accept explicit id for sample-data seeding
    $id = isset($data['id']) && preg_match('/^[a-zA-Z0-9_-]{1,64}$/', (string) $data['id'])
        ? $data['id']
        : uid();

    $created = $data['created'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $created)) {
        $created = date('Y-m-d');
    }

    $pdo->beginTransaction();

    try {
        // Insert poll
        $pdo->prepare(
            'INSERT INTO polls (id, title, committee_id, end_date, is_active, created_date)
             VALUES (:id, :title, :committee_id, :end_date, 1, :created_date)'
        )->execute([
            ':id'           => $id,
            ':title'        => $title,
            ':committee_id' => $committee,
            ':end_date'     => $end === '' ? null : $end,
            ':created_date' => $created,
        ]);

        // Insert options
        $optStmt = $pdo->prepare(
            'INSERT INTO poll_options (poll_id, sort_order, text) VALUES (:poll_id, :sort_order, :text)'
        );
        foreach ($options as $i => $text) {
            $optStmt->execute([':poll_id' => $id, ':sort_order' => $i, ':text' => $text]);
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Return assembled poll
    $stmt = $pdo->prepare('SELECT * FROM polls WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $polls = assemblePolls($pdo, [$stmt->fetch()]);

    respond(201, ['data' => $polls[0]]);
}

function handleVote(): void
{
    // POST /api/polls.php?action=vote
    // Body: { poll_id, option_index, user_id? }
    $pdo  = getPDO();
    $data = bodyJson();

    $pollId    = sanitizeString($data['poll_id']      ?? '', 'poll_id');
    $optIdx    = $data['option_index'] ?? null;
    $userId    = sanitizeString($data['user_id']      ?? 'user_default', 'user_id');

    if ($pollId === '') respond(422, ['error' => '"poll_id" is required.']);
    if (!is_numeric($optIdx)) respond(422, ['error' => '"option_index" must be numeric.']);
    $optIdx = (int) $optIdx;

    // Verify poll exists and is active
    $pollStmt = $pdo->prepare('SELECT is_active FROM polls WHERE id = :id LIMIT 1');
    $pollStmt->execute([':id' => $pollId]);
    $poll = $pollStmt->fetch();

    if (!$poll) respond(404, ['error' => 'Poll not found.']);
    if (!$poll['is_active']) respond(409, ['error' => 'Poll is closed.']);

    // Get the target option (by sort_order index)
    $optStmt = $pdo->prepare(
        'SELECT id FROM poll_options WHERE poll_id = :pid ORDER BY sort_order ASC LIMIT 1 OFFSET :offset'
    );
    $optStmt->bindValue(':pid',    $pollId, PDO::PARAM_STR);
    $optStmt->bindValue(':offset', $optIdx, PDO::PARAM_INT);
    $optStmt->execute();
    $option = $optStmt->fetch();

    if (!$option) respond(404, ['error' => "Option index {$optIdx} does not exist on this poll."]);

    // Upsert vote: one vote per user per poll (UNIQUE KEY on poll_id + user_id)
    $pdo->prepare(
        'INSERT INTO poll_votes (poll_id, option_id, user_id)
         VALUES (:poll_id, :option_id, :user_id)
         ON DUPLICATE KEY UPDATE option_id = VALUES(option_id), voted_at = CURRENT_TIMESTAMP'
    )->execute([
        ':poll_id'   => $pollId,
        ':option_id' => $option['id'],
        ':user_id'   => $userId,
    ]);

    // Return updated poll
    $stmt = $pdo->prepare('SELECT * FROM polls WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $pollId]);
    $polls = assemblePolls($pdo, [$stmt->fetch()]);

    respond(200, ['data' => $polls[0]]);
}

function handleClosePoll(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id, is_active FROM polls WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $poll = $stmt->fetch();

    if (!$poll) respond(404, ['error' => 'Poll not found.']);
    if (!$poll['is_active']) respond(409, ['error' => 'Poll is already closed.']);

    $pdo->prepare('UPDATE polls SET is_active = 0 WHERE id = :id')->execute([':id' => $id]);

    $stmt = $pdo->prepare('SELECT * FROM polls WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $polls = assemblePolls($pdo, [$stmt->fetch()]);

    respond(200, ['data' => $polls[0]]);
}

function handleDelete(string $id): void
{
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT title FROM polls WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $poll = $stmt->fetch();

    if (!$poll) respond(404, ['error' => 'Poll not found.']);

    // CASCADE in schema removes poll_options and poll_votes automatically
    $pdo->prepare('DELETE FROM polls WHERE id = :id')->execute([':id' => $id]);

    respond(200, ['message' => "Poll '{$poll['title']}' deleted."]);
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();
$action = $_GET['action'] ?? '';

try {
    match (true) {
        // Vote action (POST /api/polls.php?action=vote)
        $method === 'POST'   && $action === 'vote'    => handleVote(),
        // Close action (PUT /api/polls.php?action=close&id=x)
        $method === 'PUT'    && $action === 'close' && $id !== null => handleClosePoll($id),
        // Standard CRUD
        $method === 'GET'    && $id === null          => handleGetAll(),
        $method === 'GET'    && $id !== null          => handleGetOne($id),
        $method === 'POST'                            => handleCreatePoll(),
        $method === 'DELETE' && $id !== null          => handleDelete($id),
        $method === 'PUT'    && $id === null          => respond(400, ['error' => '?id= required for PUT.']),
        $method === 'DELETE' && $id === null          => respond(400, ['error' => '?id= required for DELETE.']),
        default                                       => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (PDOException $e) {
    respond(500, ['error' => 'Database error.', 'detail' => $e->getMessage()]);
}
