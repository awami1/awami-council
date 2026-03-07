<?php
// config.php — الإعدادات المشتركة لجميع API endpoints
// يدعم MySQL (على DigitalOcean) و SQLite (للتطوير المحلي)

declare(strict_types=1);

// Suppress PHP errors/warnings from polluting JSON API responses
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Global exception handler — returns JSON instead of HTML for uncaught exceptions
set_exception_handler(function (\Throwable $e): void {
    error_log('Uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        setCorsHeaders();
    }
    echo json_encode(
        ['error' => 'حدث خطأ داخلي في الخادم.'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
});

// ---- CORS helper (restrict to same-origin) ----
function setCorsHeaders(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin) {
        $host   = $_SERVER['HTTP_HOST'] ?? '';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $allowed = $scheme . '://' . $host;
        if ($origin === $allowed) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
        }
    }
}

// Load .env file if it exists (PHP doesn't read .env automatically)
(function () {
    $envFile = __DIR__ . '/../.env';
    if (!is_file($envFile)) return;
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if (!getenv($key)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
            }
        }
    }
})();

function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    // .env already loaded by the closure above
    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
    $name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? '');
    $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? '');
    $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
    $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '25060');

    try {
        if ($host && $name && $user) {
            // MySQL mode (production — DigitalOcean)
            $sslCa = getenv('DB_SSL_CA') ?: ($_ENV['DB_SSL_CA'] ?? '');

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            if ($sslCa && is_file($sslCa)) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
            } else {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            $pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                $user, $pass,
                $options
            );
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } else {
            // SQLite mode (local development)
            $dbPath = __DIR__ . '/../data/awami.db';
            $dbDir  = dirname($dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            $pdo = new PDO(
                "sqlite:{$dbPath}",
                null, null,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            $pdo->exec("PRAGMA journal_mode=WAL");
            $pdo->exec("PRAGMA foreign_keys=ON");
        }
    } catch (PDOException $e) {
        throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
    }

    return $pdo;
}

/** Check if running on SQLite */
function isSQLite(): bool
{
    return getPDO()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
}

// ---- Security Headers ----
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ---- JSON response ----
function respond(int $code, array $body): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    setCorsHeaders();
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ---- Handle CORS preflight ----
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    setCorsHeaders();
    http_response_code(204);
    exit;
}

// ---- Read JSON body (with size limit) ----
function bodyJson(int $maxBytes = 65536): array
{
    $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if (!$raw) return [];
    if (strlen($raw) > $maxBytes) {
        respond(413, ['error' => 'حجم الطلب يتجاوز الحد المسموح.']);
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// ---- Generate unique ID ----
function uid(): string
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// ---- Today's date ----
function today(): string
{
    return date('Y-m-d');
}
