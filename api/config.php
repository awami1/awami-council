<?php

function getPDO()
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : (isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : '');
    $name = getenv('DB_NAME') !== false ? getenv('DB_NAME') : (isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : '');
    $user = getenv('DB_USER') !== false ? getenv('DB_USER') : (isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : '');
    $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : '');
    $port = getenv('DB_PORT') !== false ? getenv('DB_PORT') : (isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '3306');

    if (empty($host) || empty($name) || empty($user)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'Database configuration missing. Set DB_HOST, DB_NAME, DB_USER, DB_PASS environment variables.'));
        exit;
    }

    $dsn = 'mysql:host=' . $host . ';port=' . intval($port) . ';dbname=' . $name . ';charset=utf8mb4';

    $options = array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    );

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'Database connection failed.'));
        exit;
    }

    return $pdo;
}
