<?php
// diagnostics.php — صفحة تشخيصية لفحص بيئة PHP على السيرفر
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$info = [
    'php_version'       => PHP_VERSION,
    'php_sapi'          => PHP_SAPI,
    'os'                => PHP_OS,
    'extensions_loaded'  => get_loaded_extensions(),
    'pdo_drivers'       => class_exists('PDO') ? PDO::getAvailableDrivers() : ['PDO غير متاح'],
    'pdo_mysql'         => extension_loaded('pdo_mysql') ? 'مثبّت ✓' : 'غير موجود ✗',
    'pdo_sqlite'        => extension_loaded('pdo_sqlite') ? 'مثبّت ✓' : 'غير موجود ✗',
    'mysqli'            => extension_loaded('mysqli') ? 'مثبّت ✓' : 'غير موجود ✗',
    'mbstring'          => extension_loaded('mbstring') ? 'مثبّت ✓' : 'غير موجود ✗',
    'openssl'           => extension_loaded('openssl') ? 'مثبّت ✓' : 'غير موجود ✗',
    'json'              => extension_loaded('json') ? 'مثبّت ✓' : 'غير موجود ✗',
    'memory_limit'      => ini_get('memory_limit'),
    'max_execution_time'=> ini_get('max_execution_time'),
    'server_software'   => $_SERVER['SERVER_SOFTWARE'] ?? 'غير معروف',
];

echo json_encode($info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
