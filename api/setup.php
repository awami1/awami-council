<?php
// ONE-TIME SETUP — DELETE THIS FILE AFTER RUNNING

$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
$name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? '');
$user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? '');
$pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
$port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

$sql = "
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `family_branches` (
  `id`         VARCHAR(36)  NOT NULL,
  `name`       VARCHAR(200) NOT NULL,
  `head`       VARCHAR(200) DEFAULT NULL,
  `count`      INT          NOT NULL DEFAULT 0,
  `color`      VARCHAR(20)  NOT NULL DEFAULT '#47915C',
  `notes`      TEXT,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `members` (
  `id`         VARCHAR(36)   NOT NULL,
  `name`       VARCHAR(200)  NOT NULL,
  `family`     VARCHAR(200)  NOT NULL DEFAULT '',
  `phone`      VARCHAR(20)   DEFAULT NULL,
  `id_num`     VARCHAR(20)   DEFAULT NULL,
  `join_date`  DATE          DEFAULT NULL,
  `status`     ENUM('نشط','معفي','غير نشط') NOT NULL DEFAULT 'نشط',
  `notes`      TEXT,
  `branch_id`  VARCHAR(36)   DEFAULT NULL,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_id_num` (`id_num`),
  INDEX `idx_status` (`status`),
  CONSTRAINT `fk_member_branch`
    FOREIGN KEY (`branch_id`) REFERENCES `family_branches` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `periods` (
  `id`          VARCHAR(36)   NOT NULL,
  `name`        VARCHAR(200)  NOT NULL,
  `fee_amount`  DECIMAL(10,2) NOT NULL DEFAULT 0,
  `start_date`  DATE          DEFAULT NULL,
  `end_date`    DATE          DEFAULT NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id`          VARCHAR(36)   NOT NULL,
  `member_id`   VARCHAR(36)   NOT NULL,
  `period_id`   VARCHAR(36)   NOT NULL,
  `amount`      DECIMAL(10,2) NOT NULL DEFAULT 0,
  `required`    DECIMAL(10,2) NOT NULL DEFAULT 0,
  `pay_date`    DATE          DEFAULT NULL,
  `method`      VARCHAR(100)  DEFAULT NULL,
  `status`      ENUM('مدفوع','لم يدفع','معفي') NOT NULL DEFAULT 'لم يدفع',
  `notes`       TEXT,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_member_period` (`member_id`, `period_id`),
  INDEX `idx_status` (`status`),
  CONSTRAINT `fk_pay_member`
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pay_period`
    FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transactions` (
  `id`           VARCHAR(36)   NOT NULL,
  `type`         ENUM('إيراد','مصروف') NOT NULL,
  `amount`       DECIMAL(10,2) NOT NULL DEFAULT 0,
  `category`     VARCHAR(100)  DEFAULT NULL,
  `committee_id` VARCHAR(36)   DEFAULT NULL,
  `description`  VARCHAR(500)  NOT NULL DEFAULT '',
  `tx_date`      DATE          DEFAULT NULL,
  `member_id`    VARCHAR(36)   DEFAULT NULL,
  `period_id`    VARCHAR(36)   DEFAULT NULL,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_type`    (`type`),
  INDEX `idx_tx_date` (`tx_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `events` (
  `id`           VARCHAR(36)   NOT NULL,
  `name`         VARCHAR(200)  NOT NULL,
  `committee_id` VARCHAR(36)   DEFAULT NULL,
  `status`       ENUM('قادم','جاري','مكتمل','ملغي') NOT NULL DEFAULT 'قادم',
  `event_date`   DATE          DEFAULT NULL,
  `budget`       DECIMAL(10,2) NOT NULL DEFAULT 0,
  `participants` INT           NOT NULL DEFAULT 0,
  `lead`         VARCHAR(200)  DEFAULT NULL,
  `notes`        TEXT,
  `icon`         VARCHAR(10)   DEFAULT '🎉',
  `images`       JSON          DEFAULT NULL,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status`     (`status`),
  INDEX `idx_event_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `polls` (
  `id`           VARCHAR(36)  NOT NULL,
  `title`        VARCHAR(500) NOT NULL,
  `options`      JSON         DEFAULT NULL,
  `committee_id` VARCHAR(36)  NOT NULL DEFAULT '',
  `end_date`     DATE         DEFAULT NULL,
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_date` DATE         NOT NULL DEFAULT (CURDATE()),
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `poll_options` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `poll_id`    VARCHAR(36)     NOT NULL,
  `sort_order` TINYINT         NOT NULL DEFAULT 0,
  `text`       VARCHAR(500)    NOT NULL,
  `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_po_poll_id` (`poll_id`),
  CONSTRAINT `fk_po_poll`
    FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `poll_votes` (
  `id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `poll_id`   VARCHAR(36)     NOT NULL,
  `option_id` BIGINT UNSIGNED NOT NULL,
  `user_id`   VARCHAR(100)    NOT NULL DEFAULT 'user_default',
  `voted_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pv_poll_user` (`poll_id`, `user_id`),
  INDEX `idx_pv_option_id` (`option_id`),
  CONSTRAINT `fk_pv_poll`
    FOREIGN KEY (`poll_id`)   REFERENCES `polls`        (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pv_option`
    FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `committee_members` (
  `committee_id` VARCHAR(36) NOT NULL,
  `member_id`    VARCHAR(36) NOT NULL,
  `created_at`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`committee_id`, `member_id`),
  CONSTRAINT `fk_cm_member`
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `website_settings` (
  `id`         INT          NOT NULL DEFAULT 1,
  `data`       JSON         NOT NULL,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
";

$errors = [];
$success = [];

foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
    if (empty($statement)) continue;
    try {
        $pdo->exec($statement);
        if (preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/', $statement, $m)) {
            $success[] = $m[1];
        }
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status'  => empty($errors) ? 'SUCCESS' : 'PARTIAL',
    'tables_created' => $success,
    'errors'  => $errors,
    'message' => empty($errors)
        ? 'All tables created. DELETE this file now.'
        : 'Some errors occurred. Check errors array.',
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
