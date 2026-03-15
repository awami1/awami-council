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
  status TEXT NOT NULL DEFAULT 'مشترك' CHECK(status IN ('مشترك','منقطع','غير مشترك')),
  status_override INTEGER NOT NULL DEFAULT 0,
  status_override_note VARCHAR(300) DEFAULT NULL,
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
  status TEXT NOT NULL DEFAULT 'لم يدفع' CHECK(status IN ('مدفوع','لم يدفع')),
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
  role VARCHAR(100) DEFAULT NULL,
  start_year INTEGER DEFAULT NULL,
  end_year INTEGER DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (committee_id, member_id),
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
)",

"CREATE TABLE IF NOT EXISTS website_settings (
  id INTEGER NOT NULL DEFAULT 1 PRIMARY KEY,
  data TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)",

"CREATE TABLE IF NOT EXISTS albums (
  id TEXT PRIMARY KEY,
  title TEXT NOT NULL,
  description TEXT NOT NULL DEFAULT '',
  cover_url TEXT NOT NULL DEFAULT '',
  date TEXT DEFAULT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
)",

"CREATE TABLE IF NOT EXISTS media (
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

"CREATE TABLE IF NOT EXISTS saved_reports (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  title VARCHAR(300) NOT NULL,
  description TEXT,
  report_type VARCHAR(50) NOT NULL DEFAULT 'smart_analysis',
  report_data TEXT NOT NULL,
  summary TEXT,
  status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK(status IN ('active','archived')),
  file_name VARCHAR(200),
  total_transactions INTEGER DEFAULT 0,
  total_income DECIMAL(12,2) DEFAULT 0,
  total_expense DECIMAL(12,2) DEFAULT 0,
  net_profit DECIMAL(12,2) DEFAULT 0,
  created_by VARCHAR(36),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)",

"CREATE INDEX IF NOT EXISTS idx_reports_status ON saved_reports(status)",
"CREATE INDEX IF NOT EXISTS idx_reports_created ON saved_reports(created_at)",

"CREATE TABLE IF NOT EXISTS gallery_stories (
  id TEXT PRIMARY KEY,
  title TEXT NOT NULL,
  subtitle TEXT NOT NULL DEFAULT '',
  type TEXT NOT NULL DEFAULT 'سيرة ذاتية',
  year_range TEXT NOT NULL DEFAULT '',
  quote TEXT NOT NULL DEFAULT '',
  full_text TEXT NOT NULL DEFAULT '',
  author_name TEXT NOT NULL DEFAULT '',
  read_time INTEGER NOT NULL DEFAULT 5,
  color_primary TEXT NOT NULL DEFAULT '#0B3D2E',
  color_secondary TEXT NOT NULL DEFAULT '#1A6B4A',
  color_accent TEXT NOT NULL DEFAULT '#D4AF37',
  display_order INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
)",
"CREATE INDEX IF NOT EXISTS idx_gs_active_order ON gallery_stories(is_active, display_order)",

"CREATE TABLE IF NOT EXISTS stories (
  id TEXT PRIMARY KEY,
  title TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  category TEXT NOT NULL DEFAULT 'biography',
  excerpt TEXT NOT NULL DEFAULT '',
  content TEXT NOT NULL DEFAULT '',
  cover_image TEXT NOT NULL DEFAULT '',
  person_name TEXT NOT NULL DEFAULT '',
  person_image TEXT NOT NULL DEFAULT '',
  person_bio TEXT NOT NULL DEFAULT '',
  person_status TEXT NOT NULL DEFAULT 'alive',
  author_name TEXT NOT NULL DEFAULT '',
  status TEXT NOT NULL DEFAULT 'draft',
  is_pinned INTEGER NOT NULL DEFAULT 0,
  published_at TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
)",

"CREATE TABLE IF NOT EXISTS member_users (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  member_id VARCHAR(36) NOT NULL,
  awm_id VARCHAR(10) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  first_login INTEGER NOT NULL DEFAULT 1,
  temp_token VARCHAR(100) DEFAULT NULL,
  token_expiry DATETIME DEFAULT NULL,
  last_login DATETIME DEFAULT NULL,
  is_active INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
)",
"CREATE UNIQUE INDEX IF NOT EXISTS uq_member_users_member ON member_users(member_id)",
"CREATE UNIQUE INDEX IF NOT EXISTS uq_member_users_awm_id ON member_users(awm_id)",

"CREATE TABLE IF NOT EXISTS member_objections (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  member_id VARCHAR(36) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  related_to TEXT NOT NULL DEFAULT 'أخرى' CHECK(related_to IN ('دفعة','لجنة','بيانات','أخرى')),
  related_id VARCHAR(36) DEFAULT NULL,
  status TEXT NOT NULL DEFAULT 'جديد' CHECK(status IN ('جديد','قيد المراجعة','تمت المعالجة','مرفوض')),
  admin_reply TEXT DEFAULT NULL,
  replied_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
)",
"CREATE INDEX IF NOT EXISTS idx_objections_member ON member_objections(member_id)",
"CREATE INDEX IF NOT EXISTS idx_objections_status ON member_objections(status)",

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
  `status` VARCHAR(20) NOT NULL DEFAULT 'مشترك',
  `status_override` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = حالة يدوية، 0 = تلقائية',
  `status_override_note` VARCHAR(300) DEFAULT NULL,
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
  `status` ENUM('مدفوع','لم يدفع') NOT NULL DEFAULT 'لم يدفع',
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
  `role` VARCHAR(100) DEFAULT NULL COMMENT 'مثال: رئيس، نائب، أمين صندوق، عضو',
  `start_year` YEAR DEFAULT NULL,
  `end_year` YEAR DEFAULT NULL COMMENT 'NULL = عضوية حالية',
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

"CREATE TABLE IF NOT EXISTS `albums` (
  `id` VARCHAR(64) NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT,
  `cover_url` VARCHAR(500) NOT NULL DEFAULT '',
  `date` DATE DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `media` (
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

"CREATE TABLE IF NOT EXISTS `saved_reports` (
  `id` VARCHAR(36) NOT NULL,
  `title` VARCHAR(300) NOT NULL,
  `description` TEXT,
  `report_type` VARCHAR(50) NOT NULL DEFAULT 'smart_analysis',
  `report_data` JSON NOT NULL,
  `summary` JSON DEFAULT NULL,
  `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
  `file_name` VARCHAR(200) DEFAULT NULL,
  `total_transactions` INT DEFAULT 0,
  `total_income` DECIMAL(12,2) DEFAULT 0,
  `total_expense` DECIMAL(12,2) DEFAULT 0,
  `net_profit` DECIMAL(12,2) DEFAULT 0,
  `created_by` VARCHAR(36) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_reports_status` (`status`),
  INDEX `idx_reports_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `stories` (
  `id` VARCHAR(64) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'biography',
  `excerpt` TEXT,
  `content` LONGTEXT,
  `cover_image` VARCHAR(500) NOT NULL DEFAULT '',
  `person_name` VARCHAR(255) NOT NULL DEFAULT '',
  `person_image` VARCHAR(500) NOT NULL DEFAULT '',
  `person_bio` TEXT,
  `person_status` ENUM('deceased','alive') NOT NULL DEFAULT 'alive',
  `author_name` VARCHAR(255) NOT NULL DEFAULT '',
  `status` ENUM('draft','published') NOT NULL DEFAULT 'draft',
  `is_pinned` TINYINT NOT NULL DEFAULT 0,
  `published_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  INDEX `idx_stories_status` (`status`),
  INDEX `idx_stories_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `gallery_stories` (
  `id` VARCHAR(64) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `subtitle` VARCHAR(500) NOT NULL DEFAULT '',
  `type` ENUM('سيرة ذاتية','رثاء','قصة نجاح','ذكريات','وصايا') NOT NULL DEFAULT 'سيرة ذاتية',
  `year_range` VARCHAR(100) NOT NULL DEFAULT '',
  `quote` TEXT,
  `full_text` LONGTEXT,
  `author_name` VARCHAR(255) NOT NULL DEFAULT '',
  `read_time` INT NOT NULL DEFAULT 5,
  `color_primary` VARCHAR(7) NOT NULL DEFAULT '#0B3D2E',
  `color_secondary` VARCHAR(7) NOT NULL DEFAULT '#1A6B4A',
  `color_accent` VARCHAR(7) NOT NULL DEFAULT '#D4AF37',
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_gs_active_order` (`is_active`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `member_users` (
  `id` VARCHAR(36) NOT NULL,
  `member_id` VARCHAR(36) NOT NULL,
  `awm_id` VARCHAR(10) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_login` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = يجب تغيير كلمة السر',
  `temp_token` VARCHAR(100) DEFAULT NULL COMMENT 'رمز التفعيل المؤقت',
  `token_expiry` DATETIME DEFAULT NULL COMMENT 'صلاحية 48 ساعة',
  `last_login` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = لم يُفعَّل بعد',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_member_users_member` (`member_id`),
  UNIQUE KEY `uq_member_users_awm_id` (`awm_id`),
  CONSTRAINT `fk_member_users_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `member_objections` (
  `id` VARCHAR(36) NOT NULL,
  `member_id` VARCHAR(36) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `body` TEXT NOT NULL,
  `related_to` ENUM('دفعة','لجنة','بيانات','أخرى') NOT NULL DEFAULT 'أخرى',
  `related_id` VARCHAR(36) DEFAULT NULL COMMENT 'معرف الكيان المعترض عليه',
  `status` ENUM('جديد','قيد المراجعة','تمت المعالجة','مرفوض') NOT NULL DEFAULT 'جديد',
  `admin_reply` TEXT DEFAULT NULL,
  `replied_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_objections_member` (`member_id`),
  INDEX `idx_objections_status` (`status`),
  CONSTRAINT `fk_objections_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
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
        // المرحلة الأولى — نظام هوية الأعضاء
        // تحويل status من ENUM إلى VARCHAR (ENUM مع نصوص عربية يسبب مشاكل ترميز)
        "ALTER TABLE `members` MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'مشترك'",
        "ALTER TABLE `members` ADD COLUMN IF NOT EXISTS `status_override` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = حالة يدوية، 0 = تلقائية'",
        "ALTER TABLE `members` ADD COLUMN IF NOT EXISTS `status_override_note` VARCHAR(300) DEFAULT NULL",
        "ALTER TABLE `committee_members` ADD COLUMN IF NOT EXISTS `role` VARCHAR(100) DEFAULT NULL COMMENT 'مثال: رئيس، نائب، أمين صندوق، عضو'",
        "ALTER TABLE `committee_members` ADD COLUMN IF NOT EXISTS `start_year` YEAR DEFAULT NULL",
        "ALTER TABLE `committee_members` ADD COLUMN IF NOT EXISTS `end_year` YEAR DEFAULT NULL COMMENT 'NULL = عضوية حالية'",
        // تنظيف id_num فارغ → NULL (لتجنب انتهاك UNIQUE constraint)
        "UPDATE `members` SET `id_num` = NULL WHERE `id_num` = ''",
    ];
    foreach ($migrations as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) { /* ignore */ }
    }
}

// ---- Performance indexes (idempotent — safe to re-run) ----
$indexes = $sqlite ? [
    "CREATE INDEX IF NOT EXISTS idx_payments_member ON payments(member_id)",
    "CREATE INDEX IF NOT EXISTS idx_payments_period ON payments(period_id)",
    "CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date)",
    "CREATE INDEX IF NOT EXISTS idx_news_created ON news(created_at)",
    "CREATE INDEX IF NOT EXISTS idx_messages_created ON messages(created_at)",
    "CREATE INDEX IF NOT EXISTS idx_stories_status ON stories(status)",
    "CREATE INDEX IF NOT EXISTS idx_stories_published ON stories(published_at)",
    "CREATE INDEX IF NOT EXISTS idx_media_album_id ON media(album_id)",
] : [
    // MySQL: CREATE INDEX IF NOT EXISTS not supported pre-8.0, so use try/catch
    "CREATE INDEX idx_payments_member ON payments(member_id)",
    "CREATE INDEX idx_payments_period ON payments(period_id)",
    "CREATE INDEX idx_events_date ON events(event_date)",
    "CREATE INDEX idx_messages_created ON messages(created_at)",
    "CREATE INDEX idx_stories_status ON stories(status)",
    "CREATE INDEX idx_stories_published ON stories(published_at)",
    "CREATE INDEX idx_media_album_id ON media(album_id)",
];
foreach ($indexes as $sql) {
    try { $pdo->exec($sql); } catch (PDOException $e) { /* index may already exist */ }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status'  => empty($errors) ? 'SUCCESS' : 'PARTIAL — check errors',
    'driver'  => $sqlite ? 'SQLite' : 'MySQL',
    'tables'  => array_unique($created),
    'errors'  => $errors,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
