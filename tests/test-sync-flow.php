#!/usr/bin/env php
<?php
/**
 * Integration Test: Admin Save → Public Site Sync Flow
 *
 * Starts a PHP built-in HTTP server and exercises the full round-trip:
 *   Admin POST → database → Public GET → verify data matches
 *
 * Runs in SQLite mode (no external DB required) to work in any environment.
 * The same sync logic applies regardless of DB backend (MySQL or SQLite).
 *
 * Usage:
 *   php tests/test-sync-flow.php
 */

declare(strict_types=1);

// ── Configuration ─────────────────────────────────────────────────────────────
$ROOT    = dirname(__DIR__);
$PORT    = 9997;
$BASE    = "http://127.0.0.1:{$PORT}";
$DB_FILE = "$ROOT/data/awami-test.db";

// ── Clean up stale test DB ────────────────────────────────────────────────────
if (file_exists($DB_FILE)) {
    unlink($DB_FILE);
}
if (!is_dir("$ROOT/data")) {
    mkdir("$ROOT/data", 0755, true);
}

// ── Start PHP built-in server (SQLite mode — no DB_HOST) ─────────────────────
$serverLog = tempnam(sys_get_temp_dir(), 'awami_srv_');
$envLine   = "DB_SQLITE_PATH=$DB_FILE";          // custom var for test isolation
$proc      = proc_open(
    "php -S 127.0.0.1:{$PORT} -t " . escapeshellarg($ROOT),
    [
        0 => ['pipe', 'r'],
        1 => ['file', $serverLog, 'w'],
        2 => ['file', $serverLog, 'a'],
    ],
    $pipes,
    $ROOT,
    // Pass EMPTY DB env so config.php uses SQLite
    ['PATH' => getenv('PATH') ?: '/usr/bin:/bin']
);

if (!is_resource($proc)) {
    echo "ERROR: Could not start PHP built-in server\n";
    exit(1);
}

// Wait for server to become ready
$ready   = false;
$timeout = 8; // seconds
$start   = time();
while (!$ready && (time() - $start) < $timeout) {
    usleep(200_000);
    $ch = curl_init("$BASE/api/setup.php");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_TIMEOUT        => 2,
        CURLOPT_NOBODY         => true,
    ]);
    curl_exec($ch);
    $ready = curl_getinfo($ch, CURLINFO_HTTP_CODE) > 0;
    curl_close($ch);
}

if (!$ready) {
    echo "ERROR: Server did not start on port $PORT within {$timeout}s\n";
    proc_terminate($proc);
    proc_close($proc);
    exit(1);
}

// ── Minimal test runner ───────────────────────────────────────────────────────
$passed = 0;
$failed = 0;

function pass(string $name): void {
    global $passed;
    $passed++;
    echo "  PASS  $name\n";
}

function fail(string $name, string $reason = ''): void {
    global $failed;
    $failed++;
    echo "  FAIL  $name" . ($reason ? "\n        $reason" : '') . "\n";
}

function section(string $title): void {
    echo "\n── $title ──\n";
}

// ── HTTP helpers ──────────────────────────────────────────────────────────────
function httpGet(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string)$body, true);
    return ['code' => $code, 'body' => $data];
}

function httpPost(string $url, array $payload): array {
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $ch   = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string)$body, true);
    return ['code' => $code, 'body' => $data];
}

function httpPut(string $url, array $payload): array {
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $ch   = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string)$body, true);
    return ['code' => $code, 'body' => $data];
}

function httpDelete(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string)$body, true);
    return ['code' => $code, 'body' => $data];
}

// ── Run Tests ─────────────────────────────────────────────────────────────────
echo "=== Admin → Public Sync Flow Tests ===\n";
echo "Server : $BASE\n";
echo "DB mode: SQLite (awami-test.db)\n";

// 1. Database/Server setup
section("1. Server & Database Setup");

$r = httpGet("$BASE/api/setup.php");
if ($r['code'] === 200) {
    $driver = $r['body']['driver'] ?? 'unknown';
    $tables = $r['body']['tables'] ?? [];
    $errors = $r['body']['errors'] ?? [];
    if (empty($errors)) {
        pass("Database setup complete (driver=$driver, " . count($tables) . " tables created)");
    } else {
        fail("Database setup had errors", implode('; ', $errors));
    }
} else {
    fail("Setup endpoint returned HTTP " . $r['code']);
}

// 2. Website Settings: admin save → public read
section("2. Website Settings (admin save → public read)");

$stamp        = time();
$uniqueTitle  = "مجلس العوامي — اختبار $stamp";
$uniqueSub    = "TEST-SUBTITLE-$stamp";

// Admin: save header section
$r = httpPost("$BASE/api/settings.php", [
    'section' => 'header',
    'data'    => ['title' => $uniqueTitle, 'subtitle' => $uniqueSub],
]);
if ($r['code'] === 200 && ($r['body']['ok'] ?? false)) {
    pass("Admin POST /api/settings.php (header section) → HTTP 200");
} else {
    fail("Admin save settings returned HTTP " . $r['code'], json_encode($r['body']));
}

// Public: read settings
$r = httpGet("$BASE/api/settings.php");
if ($r['code'] === 200) {
    pass("Public GET /api/settings.php → HTTP 200");
    $s = $r['body']['settings'] ?? [];

    ($s['header']['title'] ?? '') === $uniqueTitle
        ? pass("Saved header title visible to public")
        : fail("Header title mismatch", "expected: $uniqueTitle, got: " . ($s['header']['title'] ?? 'null'));

    ($s['header']['subtitle'] ?? '') === $uniqueSub
        ? pass("Saved header subtitle visible to public")
        : fail("Header subtitle mismatch");
} else {
    fail("Public read settings returned HTTP " . $r['code']);
}

// Admin: save hero section — verify both sections coexist
$heroTitle = "عنوان بطولي $stamp";
httpPost("$BASE/api/settings.php", [
    'section' => 'hero',
    'data'    => ['title' => $heroTitle, 'description' => 'وصف اختباري'],
]);

$r = httpGet("$BASE/api/settings.php");
$s = $r['body']['settings'] ?? [];
($s['header']['title'] ?? '') === $uniqueTitle
    ? pass("Header section preserved after hero section update")
    : fail("Header section overwritten by hero update");

($s['hero']['title'] ?? '') === $heroTitle
    ? pass("Hero title visible after save")
    : fail("Hero title not reflected", "got: " . ($s['hero']['title'] ?? 'null'));

// Admin: update stats
httpPost("$BASE/api/settings.php", [
    'section' => 'stats',
    'data'    => ['years' => 33, 'committees' => 11, 'members' => '+100'],
]);
$r = httpGet("$BASE/api/settings.php");
$s = $r['body']['settings'] ?? [];
($s['stats']['years'] ?? 0) === 33
    ? pass("Stats.years=33 saved by admin and readable by public")
    : fail("Stats years mismatch", "got: " . ($s['stats']['years'] ?? 'null'));

// 3. Next Meeting: admin save → public read
section("3. Next Meeting (admin save → public read)");

$meetDate  = '2026-03-15 19:00:00';
$meetTitle = "اجتماع الاختبار $stamp";

$r = httpPost("$BASE/api/meeting.php", [
    'date'    => $meetDate,
    'title'   => $meetTitle,
    'visible' => true,
]);
if ($r['code'] === 200 && ($r['body']['ok'] ?? false)) {
    pass("Admin POST /api/meeting.php → HTTP 200");
} else {
    fail("Admin save meeting returned HTTP " . $r['code'], json_encode($r['body']));
}

$r = httpGet("$BASE/api/meeting.php");
if ($r['code'] === 200) {
    pass("Public GET /api/meeting.php → HTTP 200");
    $m = $r['body']['nextMeeting'] ?? null;
    if ($m === null) {
        fail("nextMeeting is null after save");
    } else {
        $m['date'] === $meetDate
            ? pass("Meeting date visible to public")
            : fail("Meeting date mismatch", "expected: $meetDate, got: " . ($m['date'] ?? 'null'));

        $m['title'] === $meetTitle
            ? pass("Meeting title visible to public")
            : fail("Meeting title mismatch");

        $m['visible'] === true
            ? pass("Meeting visible=true reflected correctly")
            : fail("Meeting visible flag wrong", var_export($m['visible'] ?? null, true));
    }
} else {
    fail("Public read meeting returned HTTP " . $r['code']);
}

// Update meeting
$updTitle = "اجتماع محدَّث $stamp";
httpPost("$BASE/api/meeting.php", ['date' => '2026-04-20 18:30:00', 'title' => $updTitle, 'visible' => false]);
$r = httpGet("$BASE/api/meeting.php");
$m = $r['body']['nextMeeting'] ?? null;
($m['title'] ?? '') === $updTitle
    ? pass("Updated meeting title immediately visible to public")
    : fail("Meeting update not reflected", "got: " . ($m['title'] ?? 'null'));
($m['visible'] ?? true) === false
    ? pass("Meeting visible=false reflected correctly after update")
    : fail("Meeting visibility not updated");

// Delete meeting
httpDelete("$BASE/api/meeting.php");
$r = httpGet("$BASE/api/meeting.php");
// meeting.php returns {"nextMeeting": null} after DELETE — use array_key_exists to
// distinguish "key present with null value" from "key missing", since PHP's ?? treats null as absent.
(array_key_exists('nextMeeting', $r['body'] ?? []) && $r['body']['nextMeeting'] === null)
    ? pass("Deleted meeting returns null for public")
    : fail("Deleted meeting still returned", json_encode($r['body']['nextMeeting'] ?? 'KEY_MISSING'));

// 4. Members: admin create → public read
section("4. Members (admin create → public read)");

$idNum     = "TEST-$stamp";
$memberName = 'عضو اختبار';

// Create member
$r = httpPost("$BASE/api/members.php", [
    'name'      => $memberName,
    'family'    => 'العوامي',
    'phone'     => '0501234567',
    'id_num'    => $idNum,
    'join_date' => '2026-01-01',
    'status'    => 'نشط',
]);
if ($r['code'] === 200) {
    pass("Admin POST /api/members.php → HTTP 200");
} else {
    fail("Admin create member returned HTTP " . $r['code'], json_encode($r['body']));
}
$createdMemberId = $r['body']['data']['id'] ?? null;

// Public reads
$r = httpGet("$BASE/api/members.php");
if ($r['code'] === 200) {
    pass("Public GET /api/members.php → HTTP 200");
    $members = $r['body']['data'] ?? [];
    $found   = null;
    foreach ($members as $m) {
        if ($m['id'] === $createdMemberId) { $found = $m; break; }
    }

    $found !== null
        ? pass("Admin-created member found in public read")
        : fail("Member not found in public list", "id: $createdMemberId");

    ($found['name'] ?? '') === $memberName
        ? pass("Member name correctly stored")
        : fail("Member name mismatch");

    ($found['status'] ?? '') === 'نشط'
        ? pass("Member status 'نشط' stored correctly")
        : fail("Member status mismatch");
} else {
    fail("Public read members returned HTTP " . $r['code']);
}

// Update member status
if ($createdMemberId) {
    $r = httpPut("$BASE/api/members.php?id=$createdMemberId", ['status' => 'معفي']);
    $r2 = httpGet("$BASE/api/members.php");
    $members = $r2['body']['data'] ?? [];
    $updated = null;
    foreach ($members as $m) {
        if ($m['id'] === $createdMemberId) { $updated = $m; break; }
    }
    ($updated['status'] ?? '') === 'معفي'
        ? pass("Member status update 'معفي' immediately visible to public")
        : fail("Member status update not reflected", "got: " . ($updated['status'] ?? 'null'));
}

// Delete member
if ($createdMemberId) {
    httpDelete("$BASE/api/members.php?id=$createdMemberId");
    $r = httpGet("$BASE/api/members.php");
    $members = $r['body']['data'] ?? [];
    $found   = array_filter($members, fn($m) => $m['id'] === $createdMemberId);
    empty($found)
        ? pass("Deleted member no longer visible to public")
        : fail("Deleted member still in public list");
}

// 5. Family Branches: admin save → public read
section("5. Family Branches (admin save → public read)");

$branchName = "فرع الاختبار $stamp";
$r = httpPost("$BASE/api/branches.php", [
    'name'    => $branchName,
    'head'    => 'رئيس الفرع',
    'color'   => '#2E86AB',
    'notes'   => 'فرع تجريبي',
    'members' => ['عضو أول', 'عضو ثاني'],
]);
if ($r['code'] === 200) {
    pass("Admin POST /api/branches.php → HTTP 200");
} else {
    fail("Admin create branch returned HTTP " . $r['code'], json_encode($r['body']));
}
$branchId = $r['body']['branch']['id'] ?? null;

$r = httpGet("$BASE/api/branches.php");
if ($r['code'] === 200) {
    pass("Public GET /api/branches.php → HTTP 200");
    $branches = $r['body']['branches'] ?? [];
    $found    = null;
    foreach ($branches as $b) {
        if ($b['id'] === $branchId) { $found = $b; break; }
    }

    $found !== null
        ? pass("Admin-created branch found in public read")
        : fail("Branch not found", "id: $branchId");

    ($found['name'] ?? '') === $branchName
        ? pass("Branch name visible to public")
        : fail("Branch name mismatch");

    ($found['count'] ?? -1) === 2
        ? pass("Branch member count (2) auto-computed and reflected")
        : fail("Branch count wrong", "expected 2, got: " . ($found['count'] ?? 'null'));

    is_array($found['members'] ?? null) && count($found['members'] ?? []) === 2
        ? pass("Branch members JSON stored and readable")
        : fail("Branch members mismatch");
} else {
    fail("Public read branches returned HTTP " . $r['code']);
}

// Update branch
if ($branchId) {
    $updBranchName = "فرع محدَّث $stamp";
    httpPost("$BASE/api/branches.php", [
        'id'      => $branchId,
        'name'    => $updBranchName,
        'head'    => 'رئيس جديد',
        'color'   => '#FF6B35',
        'notes'   => 'تم التحديث',
        'members' => ['عضو أ', 'عضو ب', 'عضو ج'],
    ]);

    $r = httpGet("$BASE/api/branches.php");
    $branches = $r['body']['branches'] ?? [];
    $found    = null;
    foreach ($branches as $b) {
        if ($b['id'] === $branchId) { $found = $b; break; }
    }

    ($found['name'] ?? '') === $updBranchName
        ? pass("Updated branch name immediately visible to public")
        : fail("Branch update not reflected");

    ($found['count'] ?? -1) === 3
        ? pass("Updated branch member count (3) reflected")
        : fail("Updated count wrong", "expected 3, got: " . ($found['count'] ?? 'null'));
}

// Delete branch
if ($branchId) {
    httpDelete("$BASE/api/branches.php?id=$branchId");
    $r = httpGet("$BASE/api/branches.php");
    $branches = $r['body']['branches'] ?? [];
    $found = array_filter($branches, fn($b) => $b['id'] === $branchId);
    empty($found)
        ? pass("Deleted branch no longer visible to public")
        : fail("Deleted branch still visible");
}

// 6. Events: admin create → public read
section("6. Events (admin create → public read)");

$eventName = "فعالية اختبار $stamp";
$r = httpPost("$BASE/api/events.php", [
    'name'         => $eventName,
    'committee_id' => 'test-committee-1',
    'status'       => 'قادم',
    'event_date'   => '2026-06-01',
    'budget'       => 5000,
    'participants' => 50,
    'lead'         => 'قائد الفعالية',
    'icon'         => '🎉',
]);
// events.php returns HTTP 201 Created for new resources (correct REST semantics)
if (in_array($r['code'], [200, 201])) {
    pass("Admin POST /api/events.php → HTTP " . $r['code'] . " (Created)");
} else {
    fail("Admin create event returned HTTP " . $r['code'], json_encode($r['body']));
}
$eventId = $r['body']['data']['id'] ?? null;

$r = httpGet("$BASE/api/events.php");
if ($r['code'] === 200) {
    pass("Public GET /api/events.php → HTTP 200");
    $events = $r['body']['data'] ?? [];
    $found  = null;
    foreach ($events as $e) {
        if ($e['id'] === $eventId) { $found = $e; break; }
    }

    $found !== null
        ? pass("Admin-created event found in public read")
        : fail("Event not found", "id: $eventId");

    ($found['name'] ?? '') === $eventName
        ? pass("Event name visible to public")
        : fail("Event name mismatch");
} else {
    fail("Public read events returned HTTP " . $r['code']);
}

// Clean up event
if ($eventId) {
    httpDelete("$BASE/api/events.php?id=$eventId");
}

// ── Teardown ──────────────────────────────────────────────────────────────────
proc_terminate($proc);
proc_close($proc);
if (file_exists($DB_FILE)) {
    unlink($DB_FILE);
}
if (file_exists($serverLog)) {
    unlink($serverLog);
}

// ── Summary ───────────────────────────────────────────────────────────────────
echo "\n════════════════════════════════════════\n";
$total = $passed + $failed;
echo "Results: $passed/$total passed";
if ($failed > 0) {
    echo " ($failed failed)";
}
echo "\n";

if ($failed === 0) {
    echo "All tests passed — admin→public sync is working correctly.\n";
    exit(0);
} else {
    echo "Some tests FAILED — review output above.\n";
    exit(1);
}
