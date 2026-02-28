<?php
// ONE-TIME SETUP — creates all database tables
// Supports both MySQL (CranL) and SQLite (local)

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
requireAuth();

// حماية من إعادة التنفيذ في بيئة الإنتاج
if (getenv('SETUP_DISABLED') === 'true') {
    respond(403, ['error' => 'Setup disabled in production.']);
}

$pdo = getPDO();
$sqlite = isSQLite();

// ---- Table definitions ----

if ($sqlite) {
    $statements = [

"CREATE TABLE IF NOT EXISTS family_branches (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  head VARCHAR(200) DEFAULT NULL,
  count INTEGER NOT NULL DEFAULT 0,
  color VARCHAR(20) NOT NULL DEFAULT '#47915C',
  notes TEXT,
  members TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)",

"CREATE TABLE IF NOT EXISTS members (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  family VARCHAR(200) NOT NULL DEFAULT '',
  phone VARCHAR(20) DEFAULT NULL,
  id_num VARCHAR(20) DEFAULT NULL,
  join_date DATE DEFAULT NULL,
  status TEXT NOT NULL DEFAULT 'نشط' CHECK(status IN ('نشط','معفي','غير نشط')),
  notes TEXT,
  branch_id VARCHAR(36) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)",

"CREATE UNIQUE INDEX IF NOT EXISTS uq_id_num ON members(id_num)",

"CREATE TABLE IF NOT EXISTS periods (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)",

"CREATE TABLE IF NOT EXISTS payments (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  member_id VARCHAR(36) NOT NULL,
  period_id VARCHAR(36) NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  required DECIMAL(10,2) NOT NULL DEFAULT 0,
  pay_date DATE DEFAULT NULL,
  method VARCHAR(100) DEFAULT NULL,
  status TEXT NOT NULL DEFAULT 'لم يدفع' CHECK(status IN ('مدفوع','لم يدفع','معفي')),
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(member_id, period_id),
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  FOREIGN KEY (period_id) REFERENCES periods(id) ON DELETE CASCADE
)",

"CREATE TABLE IF NOT EXISTS transactions (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  type TEXT NOT NULL CHECK(type IN ('إيراد','مصروف')),
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  category VARCHAR(100) DEFAULT NULL,
  committee_id VARCHAR(36) DEFAULT NULL,
  description VARCHAR(500) NOT NULL DEFAULT '',
  tx_date DATE DEFAULT NULL,
  member_id VARCHAR(36) DEFAULT NULL,
  period_id VARCHAR(36) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)",

"CREATE TABLE IF NOT EXISTS events (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  committee_id VARCHAR(36) DEFAULT NULL,
  status TEXT NOT NULL DEFAULT 'قادم' CHECK(status IN ('قادم','جاري','مكتمل','ملغي')),
  event_date DATE DEFAULT NULL,
  budget DECIMAL(10,2) NOT NULL DEFAULT 0,
  participants INTEGER NOT NULL DEFAULT 0,
  lead VARCHAR(200) DEFAULT NULL,
  notes TEXT,
  icon VARCHAR(10) DEFAULT '🎉',
  images TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)",

"CREATE TABLE IF NOT EXISTS polls (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  title VARCHAR(500) NOT NULL,
  options TEXT DEFAULT NULL,
  committee_id VARCHAR(36) NOT NULL DEFAULT '',
  end_date DATE DEFAULT NULL,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_date DATE NOT NULL DEFAULT (date('now')),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)",

"CREATE TABLE IF NOT EXISTS poll_options (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  poll_id VARCHAR(36) NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  text VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
)",

"CREATE TABLE IF NOT EXISTS poll_votes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  poll_id VARCHAR(36) NOT NULL,
  option_id INTEGER NOT NULL,
  user_id VARCHAR(100) NOT NULL DEFAULT 'user_default',
  voted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(poll_id, user_id),
  FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
  FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE
)",

"CREATE TABLE IF NOT EXISTS committee_members (
  committee_id VARCHAR(36) NOT NULL,
  member_id VARCHAR(36) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (committee_id, member_id),
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
)",

"CREATE TABLE IF NOT EXISTS website_settings (
  id INTEGER NOT NULL DEFAULT 1 PRIMARY KEY,
  data TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)",

"CREATE TABLE IF NOT EXISTS next_meeting (
  id INTEGER NOT NULL DEFAULT 1 PRIMARY KEY,
  date DATETIME DEFAULT NULL,
  title VARCHAR(300) NOT NULL DEFAULT 'الجلسة العمومية للمجلس',
  visible INTEGER NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)",

"CREATE TABLE IF NOT EXISTS family_tree (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  parent_id VARCHAR(36) DEFAULT NULL,
  gender TEXT NOT NULL DEFAULT 'ذكر' CHECK(gender IN ('ذكر','أنثى')),
  is_alive INTEGER NOT NULL DEFAULT 1,
  spouse_name VARCHAR(200) DEFAULT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES family_tree(id) ON DELETE SET NULL
)",

"CREATE INDEX IF NOT EXISTS idx_ft_parent ON family_tree(parent_id)",

    ]; // end SQLite
} else {
    $statements = [

"CREATE TABLE IF NOT EXISTS `family_branches` (
  `id` VARCHAR(36) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `head` VARCHAR(200) DEFAULT NULL,
  `count` INT NOT NULL DEFAULT 0,
  `color` VARCHAR(20) NOT NULL DEFAULT '#47915C',
  `notes` TEXT,
  `members` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `members` (
  `id` VARCHAR(36) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `family` VARCHAR(200) NOT NULL DEFAULT '',
  `phone` VARCHAR(20) DEFAULT NULL,
  `id_num` VARCHAR(20) DEFAULT NULL,
  `join_date` DATE DEFAULT NULL,
  `status` ENUM('نشط','معفي','غير نشط') NOT NULL DEFAULT 'نشط',
  `notes` TEXT,
  `branch_id` VARCHAR(36) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_id_num` (`id_num`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `periods` (
  `id` VARCHAR(36) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `payments` (
  `id` VARCHAR(36) NOT NULL,
  `member_id` VARCHAR(36) NOT NULL,
  `period_id` VARCHAR(36) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `required` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `pay_date` DATE DEFAULT NULL,
  `method` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('مدفوع','لم يدفع','معفي') NOT NULL DEFAULT 'لم يدفع',
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_member_period` (`member_id`, `period_id`),
  CONSTRAINT `fk_pay_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_period` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `transactions` (
  `id` VARCHAR(36) NOT NULL,
  `type` ENUM('إيراد','مصروف') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `category` VARCHAR(100) DEFAULT NULL,
  `committee_id` VARCHAR(36) DEFAULT NULL,
  `description` VARCHAR(500) NOT NULL DEFAULT '',
  `tx_date` DATE DEFAULT NULL,
  `member_id` VARCHAR(36) DEFAULT NULL,
  `period_id` VARCHAR(36) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_tx_date` (`tx_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `events` (
  `id` VARCHAR(36) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `committee_id` VARCHAR(36) DEFAULT NULL,
  `status` ENUM('قادم','جاري','مكتمل','ملغي') NOT NULL DEFAULT 'قادم',
  `event_date` DATE DEFAULT NULL,
  `budget` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `participants` INT NOT NULL DEFAULT 0,
  `lead` VARCHAR(200) DEFAULT NULL,
  `notes` TEXT,
  `icon` VARCHAR(10) DEFAULT '🎉',
  `images` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `polls` (
  `id` VARCHAR(36) NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `options` JSON DEFAULT NULL,
  `committee_id` VARCHAR(36) NOT NULL DEFAULT '',
  `end_date` DATE DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_date` DATE NOT NULL DEFAULT (CURDATE()),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `poll_options` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `poll_id` VARCHAR(36) NOT NULL,
  `sort_order` TINYINT NOT NULL DEFAULT 0,
  `text` VARCHAR(500) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_po_poll_id` (`poll_id`),
  CONSTRAINT `fk_po_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `poll_votes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `poll_id` VARCHAR(36) NOT NULL,
  `option_id` BIGINT UNSIGNED NOT NULL,
  `user_id` VARCHAR(100) NOT NULL DEFAULT 'user_default',
  `voted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pv_poll_user` (`poll_id`, `user_id`),
  CONSTRAINT `fk_pv_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pv_option` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `committee_members` (
  `committee_id` VARCHAR(36) NOT NULL,
  `member_id` VARCHAR(36) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`committee_id`, `member_id`),
  CONSTRAINT `fk_cm_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `website_settings` (
  `id` INT NOT NULL DEFAULT 1,
  `data` JSON NOT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `next_meeting` (
  `id` INT NOT NULL DEFAULT 1,
  `date` DATETIME DEFAULT NULL,
  `title` VARCHAR(300) NOT NULL DEFAULT 'الجلسة العمومية للمجلس',
  `visible` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `family_tree` (
  `id` VARCHAR(36) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `parent_id` VARCHAR(36) DEFAULT NULL,
  `gender` ENUM('ذكر','أنثى') NOT NULL DEFAULT 'ذكر',
  `is_alive` TINYINT(1) NOT NULL DEFAULT 1,
  `spouse_name` VARCHAR(200) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ft_parent` (`parent_id`),
  CONSTRAINT `fk_ft_parent` FOREIGN KEY (`parent_id`) REFERENCES `family_tree` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    ]; // end MySQL
}

// ---- Execute ----
$errors  = [];
$created = [];

foreach ($statements as $sql) {
    try {
        $pdo->exec($sql);
        if (preg_match('/CREATE TABLE IF NOT EXISTS [`"]?(\w+)[`"]?/', $sql, $m)) {
            $created[] = $m[1];
        }
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}

// Migrations (MySQL only)
if (!$sqlite) {
    $migrations = [
        "ALTER TABLE `family_branches` ADD COLUMN IF NOT EXISTS `members` JSON DEFAULT NULL",
    ];
    foreach ($migrations as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) { /* ignore */ }
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status'  => empty($errors) ? 'SUCCESS' : 'PARTIAL — check errors',
    'driver'  => $sqlite ? 'SQLite' : 'MySQL',
    'tables'  => array_unique($created),
    'errors'  => $errors,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
