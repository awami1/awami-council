<?php
// gallery_schema.php — دوال إنشاء جداول المعرض (albums + media)
// ملف مشترك بدون side effects — يُستخدم من albums.php, media.php, setup.php, migrate-media.php

declare(strict_types=1);
require_once __DIR__ . '/config.php';

function ensureAlbumsTable(): void
{
    static $done = false;
    if ($done) return;
    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS albums (
            id TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            cover_url TEXT NOT NULL DEFAULT '',
            date TEXT DEFAULT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `albums` (
            `id` VARCHAR(64) NOT NULL,
            `title` VARCHAR(500) NOT NULL,
            `description` TEXT,
            `cover_url` VARCHAR(500) NOT NULL DEFAULT '',
            `date` DATE DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $done = true;
}

function ensureMediaTable(): void
{
    static $done = false;
    if ($done) return;

    // albums يجب أن يُنشأ أولاً بسبب FK
    ensureAlbumsTable();

    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS media (
            id TEXT PRIMARY KEY,
            album_id TEXT DEFAULT NULL,
            title TEXT NOT NULL,
            type TEXT NOT NULL DEFAULT 'images',
            url TEXT NOT NULL,
            date TEXT DEFAULT NULL,
            tags TEXT NOT NULL DEFAULT '[]',
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE SET NULL
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `media` (
            `id` VARCHAR(64) NOT NULL,
            `album_id` VARCHAR(64) DEFAULT NULL,
            `title` VARCHAR(500) NOT NULL,
            `type` ENUM('images','videos','youtube') NOT NULL DEFAULT 'images',
            `url` VARCHAR(500) NOT NULL,
            `date` DATE DEFAULT NULL,
            `tags` JSON DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`album_id`) REFERENCES `albums`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // فهرس على album_id — MySQL ينشئه تلقائياً لـ FK، SQLite لا
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_media_album_id ON media(album_id)");

    $done = true;
}
