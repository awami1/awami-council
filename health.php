<?php
/**
 * health.php — صفحة تشخيص شاملة
 * تفحص: PHP, DB connection, environment variables
 * ضعها في root المشروع: /health.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$result = [
    'status'    => 'ok',
    'php'       => PHP_VERSION,
    'sapi'      => php_sapi_name(),
    'time'      => date('Y-m-d H:i:s'),
    'port'      => getenv('PORT') ?: '(not set)',
    'db_host'   => getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: '(not set)',
    'db_port'   => getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: '(not set)',
    'db_name'   => getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: '(not set)',
    'db_user'   => getenv('DB_USER') ?: getenv('MYSQL_USER') ?: '(not set)',
    'has_db_url'=> (bool)(getenv('DATABASE_URL') ?: ''),
    'extensions'=> [
        'pdo'       => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'pdo_sqlite'=> extension_loaded('pdo_sqlite'),
        'mbstring'  => extension_loaded('mbstring'),
    ],
];

// فحص اتصال الداتابيز فعلياً
try {
    // تحميل الإعدادات من .env إن وُجد
    $envFile = __DIR__ . '/.env';
    if (is_file($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                if (!getenv($key)) putenv("{$key}={$val}");
            }
        }
    }

    // محاولة اتصال MySQL
    $host = getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: '';
    $port = getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: '';
    $user = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: '';
    $pass = getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: '';

    // فحص DATABASE_URL أولاً
    $dbUrl = getenv('DATABASE_URL') ?: '';
    if ($dbUrl) {
        $parts = parse_url($dbUrl);
        if ($parts && isset($parts['host'])) {
            $host = $parts['host'];
            $port = (string) ($parts['port'] ?? '3306');
            $name = ltrim($parts['path'] ?? '', '/');
            $user = $parts['user'] ?? '';
            $pass = $parts['pass'] ?? '';
        }
    }

    if ($host && $name && $user && extension_loaded('pdo_mysql')) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        if (defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')) {
            // Note: MYSQL_ATTR_CONNECT_TIMEOUT must be set before connection
        }
        $result['db'] = 'connected';
        $result['db_version'] = $pdo->query('SELECT VERSION()')->fetchColumn();
    } elseif (extension_loaded('pdo_sqlite')) {
        $result['db'] = 'sqlite_fallback';
        $result['db_note'] = 'No MySQL credentials found, would use SQLite';
    } else {
        $result['db'] = 'no_driver';
        $result['db_note'] = 'Neither MySQL credentials nor pdo_sqlite available';
    }
} catch (Exception $e) {
    $result['db'] = 'error';
    $result['db_error'] = $e->getMessage();
    $result['status'] = 'degraded';
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
