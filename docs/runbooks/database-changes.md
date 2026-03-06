# دليل: تعديل قاعدة البيانات

## القاعدة الأساسية

كل تعديل SQL لازم يعمل مع **MySQL** و **SQLite** معاً.

## إضافة جدول جديد

### 1. في ملف الـ API (`api/{resource}.php`):

```php
function ensure{Resource}Table(): void
{
    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS {table} (
            id TEXT PRIMARY KEY,
            -- حقولك هنا: TEXT, INTEGER, REAL
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{table}` (
            `id` VARCHAR(64) NOT NULL,
            -- حقولك هنا: VARCHAR, INT, TEXT, ENUM, JSON
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
```

### 2. في `api/setup.php`:

أضف نفس SQL في الملف المركزي أيضاً.

## الفروقات بين MySQL و SQLite

| الخاصية | SQLite | MySQL |
|---------|--------|-------|
| أنواع النص | `TEXT` | `VARCHAR(n)`, `TEXT` |
| الأرقام | `INTEGER`, `REAL` | `INT`, `BIGINT`, `DECIMAL` |
| القيم المحددة | `TEXT` + تحقق PHP | `ENUM('a','b','c')` |
| JSON | `TEXT` + json PHP | `JSON` |
| التاريخ الافتراضي | `datetime('now')` | `CURRENT_TIMESTAMP` |
| التحديث التلقائي | لا يوجد (عدّل بـ PHP) | `ON UPDATE CURRENT_TIMESTAMP` |
| Backticks | لا تستخدم | مطلوبة |
| ENGINE | لا يوجد | `ENGINE=InnoDB` |
| Charset | لا يوجد | `CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci` |

## إضافة عمود لجدول موجود

```php
// في ensureTable أو في ملف migrate.php
try {
    if (isSQLite()) {
        $pdo->exec("ALTER TABLE {table} ADD COLUMN {col} TEXT NOT NULL DEFAULT ''");
    } else {
        $pdo->exec("ALTER TABLE `{table}` ADD COLUMN `{col}` VARCHAR(255) NOT NULL DEFAULT ''");
    }
} catch (PDOException $e) {
    // العمود موجود مسبقاً — تجاهل
}
```

## قواعد مهمة

- **دائماً** استخدم prepared statements مع named parameters
- **دائماً** استخدم `isSQLite()` للتفريق بين SQL dialects
- **دائماً** أنشئ IDs عبر `uid()` (UUID)
- **أبداً** لا تستخدم `AUTO_INCREMENT` (المشروع يعتمد على UUIDs)
- **أبداً** لا تحذف أعمدة — SQLite لا يدعم `DROP COLUMN` بسهولة
