<?php

declare(strict_types=1);

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? null;
    $name = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? null;
    $user = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? null;
    $pass = getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?? null;
    $port = getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? '3306';

    if (!$host || !$name || !$user) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Database configuration missing. Set DB_HOST, DB_NAME, DB_USER, DB_PASS environment variables.']);
        exit;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        (int) $port,
        $name
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, $user, (string) $pass, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Database connection failed.']);
        exit;
    }

    return $pdo;
}
