<?php
// config.php — الإعدادات المشتركة لجميع API endpoints
// يدعم MySQL (على CranL) و SQLite (للتطوير المحلي)

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

/**
 * اتصال MySQL مع إعادة المحاولة عند فشل DNS أو الشبكة
 */
function connectMySQL(string $dsn, string $user, string $pass, array $options, int $maxRetries = 3): PDO
{
    $attempt = 0;
    while ($attempt <= $maxRetries) {
        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            $isTransient = str_contains($msg, 'getaddrinfo')
                        || str_contains($msg, 'Connection refused')
                        || str_contains($msg, 'Network is unreachable')
                        || str_contains($msg, 'Connection timed out')
                        || str_contains($msg, 'Name or service not known');
            if (!$isTransient || $attempt >= $maxRetries) {
                throw $e;
            }
            $delay = (int) pow(2, $attempt); // 1s, 2s, 4s
            error_log("MySQL connection attempt " . ($attempt + 1) . " failed ({$msg}), retrying in {$delay}s...");
            sleep($delay);
            $attempt++;
        }
    }
    throw new \PDOException('فشل الاتصال بقاعدة البيانات');
}

/**
 * تحليل DATABASE_URL إلى مكوّناتها
 * يدعم صيغة: mysql://user:pass@host:port/dbname
 */
function parseDatabaseUrl(string $url): ?array
{
    $parts = parse_url($url);
    if (!$parts || !isset($parts['host'])) return null;
    return [
        'host' => $parts['host'],
        'port' => (string) ($parts['port'] ?? '3306'),
        'name' => ltrim($parts['path'] ?? '', '/'),
        'user' => $parts['user'] ?? '',
        'pass' => $parts['pass'] ?? '',
    ];
}

/**
 * جلب بيانات اتصال قاعدة البيانات من متغيرات البيئة
 * يدعم: DATABASE_URL, MYSQL_*, DB_*
 */
function getDbCredentials(): array
{
    // 1) DATABASE_URL (CranL internal connection URL)
    $dbUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? '');
    if ($dbUrl) {
        $parsed = parseDatabaseUrl($dbUrl);
        if ($parsed && $parsed['host'] && $parsed['name'] && $parsed['user']) {
            return $parsed;
        }
    }

    // 2) MYSQL_* variants (common in Docker/CranL)
    $host = getenv('MYSQL_HOST') ?: ($_ENV['MYSQL_HOST'] ?? '');
    $name = getenv('MYSQL_DATABASE') ?: ($_ENV['MYSQL_DATABASE'] ?? '');
    $user = getenv('MYSQL_USER') ?: ($_ENV['MYSQL_USER'] ?? '');
    $pass = getenv('MYSQL_PASSWORD') ?: ($_ENV['MYSQL_PASSWORD'] ?? '');
    $port = getenv('MYSQL_PORT') ?: ($_ENV['MYSQL_PORT'] ?? '');
    if ($host && $name && $user) {
        return ['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass, 'port' => $port ?: '3306'];
    }

    // 3) DB_* variants (our .env format)
    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
    $name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? '');
    $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? '');
    $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
    $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');

    return ['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass, 'port' => $port];
}

function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $creds = getDbCredentials();
    $host = $creds['host'];
    $name = $creds['name'];
    $user = $creds['user'];
    $pass = $creds['pass'];
    $port = $creds['port'];

    try {
        if ($host && $name && $user) {
            // MySQL mode (production — CranL)
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            $opts = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = connectMySQL($dsn, $user, $pass, $opts);
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } elseif (extension_loaded('pdo_sqlite')) {
            // SQLite mode (local development) — only if driver available
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
        } else {
            throw new \RuntimeException(
                'لم يتم العثور على بيانات اتصال MySQL (DB_HOST/DATABASE_URL) ولا يوجد pdo_sqlite كبديل.'
            );
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
