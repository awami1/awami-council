<?php
// diagnostics.php — صفحة تشخيصية لفحص بيئة PHP وربط قاعدة البيانات
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// ---- تحميل .env (نفس منطق config.php) ----
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

// ---- قراءة متغيرات البيئة ----
$dbHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
$dbName = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? '');
$dbUser = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? '');
$dbPass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
$dbPort = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');

// ---- 1. معلومات PHP ----
$info = [
    'php_version'        => PHP_VERSION,
    'php_sapi'           => PHP_SAPI,
    'os'                 => PHP_OS,
    'server_software'    => $_SERVER['SERVER_SOFTWARE'] ?? 'غير معروف',
    'memory_limit'       => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
];

// ---- 2. PHP Extensions ----
$info['extensions'] = [
    'pdo'        => class_exists('PDO') ? 'مثبّت ✓' : 'غير موجود ✗',
    'pdo_mysql'  => extension_loaded('pdo_mysql') ? 'مثبّت ✓' : 'غير موجود ✗',
    'pdo_sqlite' => extension_loaded('pdo_sqlite') ? 'مثبّت ✓' : 'غير موجود ✗',
    'mysqli'     => extension_loaded('mysqli') ? 'مثبّت ✓' : 'غير موجود ✗',
    'mysqlnd'    => extension_loaded('mysqlnd') ? 'مثبّت ✓' : 'غير موجود ✗',
    'mbstring'   => extension_loaded('mbstring') ? 'مثبّت ✓' : 'غير موجود ✗',
    'openssl'    => extension_loaded('openssl') ? 'مثبّت ✓' : 'غير موجود ✗',
    'json'       => extension_loaded('json') ? 'مثبّت ✓' : 'غير موجود ✗',
];
$info['pdo_drivers'] = class_exists('PDO') ? PDO::getAvailableDrivers() : [];

// ---- 3. متغيرات البيئة (بدون كشف القيم) ----
$info['db_env'] = [
    'DB_HOST' => $dbHost !== '' ? 'معرّف ✓' : 'فارغ ✗',
    'DB_NAME' => $dbName !== '' ? 'معرّف ✓' : 'فارغ ✗',
    'DB_USER' => $dbUser !== '' ? 'معرّف ✓' : 'فارغ ✗',
    'DB_PASS' => $dbPass !== '' ? 'معرّف ✓' : 'فارغ ✗',
    'DB_PORT' => $dbPort,
];

// ---- 4. فحص الشبكة (الوصول لسيرفر MySQL) ----
if ($dbHost !== '') {
    $errno = 0;
    $errstr = '';
    $conn = @fsockopen($dbHost, (int) $dbPort, $errno, $errstr, 3);
    if ($conn) {
        $info['db_network'] = 'يمكن الوصول ✓ (' . $dbHost . ':' . $dbPort . ')';
        fclose($conn);
    } else {
        $info['db_network'] = 'لا يمكن الوصول ✗: ' . ($errstr ?: "errno={$errno}");
    }
} else {
    $info['db_network'] = 'تخطّي — DB_HOST غير معرّف';
}

// ---- 5. محاولة اتصال فعلي بقاعدة البيانات ----
if (extension_loaded('pdo_mysql') && $dbHost !== '' && $dbName !== '' && $dbUser !== '') {
    try {
        $pdo = new PDO(
            "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
            $dbUser, $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        $info['db_connection'] = 'متصل بنجاح ✓';
        $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
        $info['mysql_version'] = $ver ?: 'غير معروف';
        $pdo = null;
    } catch (\Throwable $e) {
        $info['db_connection'] = 'فشل الاتصال ✗: ' . $e->getMessage();
    }
} elseif (!extension_loaded('pdo_mysql')) {
    $info['db_connection'] = 'تخطّي — pdo_mysql غير مثبّت';
} else {
    $info['db_connection'] = 'تخطّي — متغيرات البيئة ناقصة';
}

// ---- 6. ملخص التشخيص ----
$issues = [];
if (!extension_loaded('pdo_mysql')) {
    $issues[] = 'pdo_mysql غير مثبّت — لا يمكن الاتصال بـ MySQL';
}
if ($dbHost === '') {
    $issues[] = 'DB_HOST غير معرّف';
}
if ($dbName === '') {
    $issues[] = 'DB_NAME غير معرّف';
}
if ($dbUser === '') {
    $issues[] = 'DB_USER غير معرّف';
}
if (isset($info['db_network']) && str_contains($info['db_network'], '✗')) {
    $issues[] = 'لا يمكن الوصول لسيرفر MySQL عبر الشبكة';
}
if (isset($info['db_connection']) && str_contains($info['db_connection'], '✗')) {
    $issues[] = 'فشل الاتصال بقاعدة البيانات';
}

$info['diagnosis'] = empty($issues) ? 'كل شيء يعمل ✓' : $issues;

echo json_encode($info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
