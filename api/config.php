<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION (Singleton PDO)
|--------------------------------------------------------------------------
*/

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
    $name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? '');
    $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? '');
    $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
    $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');

    if ($host === '' || $name === '' || $user === '') {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'Database configuration missing. Set DB_HOST, DB_NAME, DB_USER, DB_PASS.'
        ]);
        exit;
    }

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ]
        );

        // ضمان الترميز الصحيح
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');

        // لا نعرض تفاصيل الاتصال في الإنتاج
        echo json_encode([
            'error' => 'Database connection failed.'
        ]);
        exit;
    }

    return $pdo;
}

/*
|--------------------------------------------------------------------------
| CORS + JSON HEADERS
|--------------------------------------------------------------------------
*/

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/*
|--------------------------------------------------------------------------
| COMMON HELPERS
|--------------------------------------------------------------------------
*/

function respond(int $status, mixed $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bodyJson(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === false || trim($raw) === '') {
        respond(400, ['error' => 'Request body is empty.']);
    }

    $decoded = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        respond(400, ['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    }

    return $decoded;
}

function uid(): string
{
    return bin2hex(random_bytes(8));
}
