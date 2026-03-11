<?php
/**
 * positions.php — CRUD API for council positions (المناصب الإدارية)
 * GET    /api/positions.php            → List all positions with members & tasks
 * GET    /api/positions.php?id=X       → Single position with members & tasks
 * POST   /api/positions.php            → Create position (auth required)
 * PUT    /api/positions.php?id=X       → Update position (auth required)
 * DELETE /api/positions.php?id=X       → Delete position (auth required, fails if is_core)
 * POST   /api/positions.php?id=X&action=members → Replace position members
 * POST   /api/positions.php?id=X&action=tasks   → Replace position tasks
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
require_once __DIR__ . '/validation.php';

$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();
$action = $_GET['action'] ?? '';

// Ensure tables exist and seed core data
ensurePositionsTables($pdo);

// Auth required only for write operations
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    requireAuth();
    verifyCsrf();
}

// ── Router ──
match (true) {
    $method === 'POST'   && $id !== null && $action === 'members' => handleUpdateMembers($id),
    $method === 'POST'   && $id !== null && $action === 'tasks'   => handleUpdateTasks($id),
    $method === 'GET'    && $id === null  => handleGetAll(),
    $method === 'GET'    && $id !== null  => handleGetOne($id),
    $method === 'POST'   && $id === null  => handlePost(),
    $method === 'PUT'    && $id !== null  => handlePut($id),
    $method === 'DELETE' && $id !== null  => handleDelete($id),
    default => respond(405, ['error' => 'Method not allowed.']),
};

// ── GET All ──
function handleGetAll(): void
{
    $pdo = getPDO();

    $positions = $pdo->query('SELECT * FROM positions ORDER BY sort_order ASC, created_at ASC')->fetchAll();

    // Attach members for each position
    $members = [];
    try {
        $rows = $pdo->query(
            'SELECT pm.position_id, pm.member_id, pm.sort_order, m.name
             FROM position_members pm
             JOIN members m ON m.id = pm.member_id
             ORDER BY pm.sort_order ASC'
        )->fetchAll();
        foreach ($rows as $r) {
            $members[$r['position_id']][] = [
                'member_id'  => $r['member_id'],
                'name'       => $r['name'],
                'sort_order' => (int) $r['sort_order'],
            ];
        }
    } catch (PDOException $e) {
        // members table may not exist yet
    }

    // Attach tasks for each position
    $tasks = [];
    try {
        $rows = $pdo->query(
            'SELECT id, position_id, task_text, sort_order FROM position_tasks ORDER BY sort_order ASC'
        )->fetchAll();
        foreach ($rows as $r) {
            $tasks[$r['position_id']][] = [
                'id'         => (int) $r['id'],
                'task_text'  => $r['task_text'],
                'sort_order' => (int) $r['sort_order'],
            ];
        }
    } catch (PDOException $e) {}

    foreach ($positions as &$p) {
        $p['sort_order'] = (int) $p['sort_order'];
        $p['is_core']    = (bool) ($p['is_core'] ?? false);
        $p['members']    = $members[$p['id']] ?? [];
        $p['tasks']      = $tasks[$p['id']] ?? [];
    }
    unset($p);

    respond(200, ['data' => $positions, 'total' => count($positions)]);
}

// ── GET One ──
function handleGetOne(string $id): void
{
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT * FROM positions WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $pos = $stmt->fetch();

    if (!$pos) {
        respond(404, ['error' => 'المنصب غير موجود']);
    }

    $pos['sort_order'] = (int) $pos['sort_order'];
    $pos['is_core']    = (bool) ($pos['is_core'] ?? false);

    // Attach members
    $stmt = $pdo->prepare(
        'SELECT pm.member_id, pm.sort_order, m.name
         FROM position_members pm
         JOIN members m ON m.id = pm.member_id
         WHERE pm.position_id = :pid
         ORDER BY pm.sort_order ASC'
    );
    $stmt->execute([':pid' => $id]);
    $pos['members'] = array_map(function ($r) {
        return [
            'member_id'  => $r['member_id'],
            'name'       => $r['name'],
            'sort_order' => (int) $r['sort_order'],
        ];
    }, $stmt->fetchAll());

    // Attach tasks
    $stmt = $pdo->prepare(
        'SELECT id, task_text, sort_order FROM position_tasks WHERE position_id = :pid ORDER BY sort_order ASC'
    );
    $stmt->execute([':pid' => $id]);
    $pos['tasks'] = array_map(function ($r) {
        return [
            'id'         => (int) $r['id'],
            'task_text'  => $r['task_text'],
            'sort_order' => (int) $r['sort_order'],
        ];
    }, $stmt->fetchAll());

    respond(200, ['data' => $pos]);
}

// ── POST: Create ──
function handlePost(): void
{
    $pdo  = getPDO();
    $body = bodyJson();

    $title = trim($body['title'] ?? '');
    if ($title === '') {
        respond(422, ['error' => 'اسم المنصب مطلوب']);
    }
    if (mb_strlen($title) > 255) {
        respond(422, ['error' => 'اسم المنصب يجب أن لا يتجاوز 255 حرف']);
    }

    $icon = trim($body['icon'] ?? '📌');
    if (mb_strlen($icon) > 10) {
        respond(422, ['error' => 'الأيقونة يجب أن لا تتجاوز 10 أحرف']);
    }

    $sortOrder = (int) ($body['sort_order'] ?? 0);
    if ($sortOrder < 0) {
        respond(422, ['error' => 'الترتيب يجب أن يكون رقماً موجباً']);
    }

    $committeeId = $body['committee_id'] ?? null;
    if ($committeeId !== null && $committeeId !== '') {
        // Verify committee exists
        $stmt = $pdo->prepare('SELECT id FROM committees WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $committeeId]);
        if (!$stmt->fetch()) {
            respond(400, ['error' => 'اللجنة المحددة غير موجودة']);
        }
    } else {
        $committeeId = null;
    }

    $id = uid();
    $pdo->prepare(
        'INSERT INTO positions (id, title, icon, sort_order, is_core, committee_id)
         VALUES (:id, :title, :icon, :sort, :core, :cid)'
    )->execute([
        ':id'    => $id,
        ':title' => $title,
        ':icon'  => $icon,
        ':sort'  => $sortOrder,
        ':core'  => 0,
        ':cid'   => $committeeId,
    ]);

    logAudit('إضافة', 'منصب', $id, $title);
    handleGetOne($id);
}

// ── PUT: Update ──
function handlePut(string $id): void
{
    $pdo  = getPDO();
    $body = bodyJson();

    // Verify position exists
    $stmt = $pdo->prepare('SELECT * FROM positions WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        respond(404, ['error' => 'المنصب غير موجود']);
    }

    $fields = [];
    $params = [':id' => $id];

    if (array_key_exists('title', $body)) {
        $title = trim($body['title']);
        if ($title === '') respond(422, ['error' => 'اسم المنصب مطلوب']);
        if (mb_strlen($title) > 255) respond(422, ['error' => 'اسم المنصب يجب أن لا يتجاوز 255 حرف']);
        $fields[] = 'title = :title';
        $params[':title'] = $title;
    }

    if (array_key_exists('icon', $body)) {
        $icon = trim($body['icon']);
        if (mb_strlen($icon) > 10) respond(422, ['error' => 'الأيقونة يجب أن لا تتجاوز 10 أحرف']);
        $fields[] = 'icon = :icon';
        $params[':icon'] = $icon;
    }

    if (array_key_exists('sort_order', $body)) {
        $sort = (int) $body['sort_order'];
        if ($sort < 0) respond(422, ['error' => 'الترتيب يجب أن يكون رقماً موجباً']);
        $fields[] = 'sort_order = :sort';
        $params[':sort'] = $sort;
    }

    if (array_key_exists('committee_id', $body)) {
        $cid = $body['committee_id'];
        if ($cid !== null && $cid !== '') {
            $cStmt = $pdo->prepare('SELECT id FROM committees WHERE id = :cid LIMIT 1');
            $cStmt->execute([':cid' => $cid]);
            if (!$cStmt->fetch()) {
                respond(400, ['error' => 'اللجنة المحددة غير موجودة']);
            }
            $fields[] = 'committee_id = :cid';
            $params[':cid'] = $cid;
        } else {
            $fields[] = 'committee_id = :cid';
            $params[':cid'] = null;
        }
    }

    if (empty($fields)) {
        respond(422, ['error' => 'لا توجد حقول للتحديث']);
    }

    // Manual updated_at for SQLite compatibility
    if (isSQLite()) {
        $fields[] = "updated_at = datetime('now')";
    }

    $sql = 'UPDATE positions SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $pdo->prepare($sql)->execute($params);

    logAudit('تعديل', 'منصب', $id, $body['title'] ?? $existing['title'] ?? '', $body);
    handleGetOne($id);
}

// ── DELETE ──
function handleDelete(string $id): void
{
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT * FROM positions WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $pos = $stmt->fetch();

    if (!$pos) {
        respond(404, ['error' => 'المنصب غير موجود']);
    }

    if ((int) ($pos['is_core'] ?? 0) === 1) {
        respond(403, ['error' => 'لا يمكن حذف منصب أساسي']);
    }

    $pdo->prepare('DELETE FROM positions WHERE id = :id')->execute([':id' => $id]);

    logAudit('حذف', 'منصب', $id, $pos['title'] ?? '');
    respond(200, ['message' => 'تم حذف المنصب']);
}

// ── POST action=members: Replace position members ──
function handleUpdateMembers(string $id): void
{
    $pdo  = getPDO();
    $body = bodyJson();

    // Verify position exists
    $stmt = $pdo->prepare('SELECT id, title FROM positions WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $pos = $stmt->fetch();
    if (!$pos) {
        respond(404, ['error' => 'المنصب غير موجود']);
    }

    $memberIds = $body['member_ids'] ?? [];
    if (!is_array($memberIds)) {
        respond(422, ['error' => 'member_ids يجب أن تكون مصفوفة']);
    }

    // Verify all member IDs exist in members table
    if (!empty($memberIds)) {
        $placeholders = [];
        $checkParams  = [];
        foreach ($memberIds as $i => $mid) {
            if (!is_string($mid) || trim($mid) === '') {
                respond(422, ['error' => 'معرّف عضو غير صالح في الموقع ' . ($i + 1)]);
            }
            $key = ":m{$i}";
            $placeholders[] = $key;
            $checkParams[$key] = $mid;
        }
        $sql = 'SELECT id FROM members WHERE id IN (' . implode(',', $placeholders) . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($checkParams);
        $found = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $missing = array_diff($memberIds, $found);
        if (!empty($missing)) {
            respond(400, ['error' => 'بعض معرّفات الأعضاء غير موجودة: ' . implode(', ', $missing)]);
        }
    }

    // Delete existing members and insert new ones atomically
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM position_members WHERE position_id = :pid')->execute([':pid' => $id]);

        if (!empty($memberIds)) {
            $insertStmt = $pdo->prepare(
                'INSERT INTO position_members (position_id, member_id, sort_order)
                 VALUES (:pid, :mid, :sort)'
            );
            foreach ($memberIds as $i => $mid) {
                $insertStmt->execute([
                    ':pid'  => $id,
                    ':mid'  => $mid,
                    ':sort' => $i + 1,
                ]);
            }
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        respond(500, ['error' => 'فشل تحديث أعضاء المنصب']);
    }

    logAudit('تعديل أعضاء', 'منصب', $id, $pos['title'] ?? '', ['member_ids' => $memberIds]);
    handleGetOne($id);
}

// ── POST action=tasks: Replace position tasks ──
function handleUpdateTasks(string $id): void
{
    $pdo  = getPDO();
    $body = bodyJson();

    // Verify position exists
    $stmt = $pdo->prepare('SELECT id, title FROM positions WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $pos = $stmt->fetch();
    if (!$pos) {
        respond(404, ['error' => 'المنصب غير موجود']);
    }

    $tasks = $body['tasks'] ?? [];
    if (!is_array($tasks)) {
        respond(422, ['error' => 'tasks يجب أن تكون مصفوفة']);
    }

    // Validate each task
    foreach ($tasks as $i => $task) {
        if (!is_string($task) || trim($task) === '') {
            respond(422, ['error' => 'نص المهمة مطلوب في الموقع ' . ($i + 1)]);
        }
        if (mb_strlen($task) > 500) {
            respond(422, ['error' => 'نص المهمة يجب أن لا يتجاوز 500 حرف']);
        }
    }

    // Delete existing tasks and insert new ones atomically
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM position_tasks WHERE position_id = :pid')->execute([':pid' => $id]);

        if (!empty($tasks)) {
            $insertStmt = $pdo->prepare(
                'INSERT INTO position_tasks (position_id, task_text, sort_order)
                 VALUES (:pid, :txt, :sort)'
            );
            foreach ($tasks as $i => $task) {
                $insertStmt->execute([
                    ':pid'  => $id,
                    ':txt'  => trim($task),
                    ':sort' => $i + 1,
                ]);
            }
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        respond(500, ['error' => 'فشل تحديث مهام المنصب']);
    }

    logAudit('تعديل مهام', 'منصب', $id, $pos['title'] ?? '', ['tasks_count' => count($tasks)]);
    handleGetOne($id);
}

// ══════════════════════════════════════════════════════════════
// Table creation & seed
// ══════════════════════════════════════════════════════════════

/**
 * Ensure committees table exists (minimal version for FK dependency).
 * If the full ensureCommitteesTable from committees.php is already loaded, use that instead.
 */
function ensureCommitteesTableMinimal(PDO $pdo): void
{
    if (function_exists('ensureCommitteesTable')) {
        ensureCommitteesTable($pdo);
        return;
    }

    static $checked = false;
    if ($checked) return;

    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS committees (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            icon VARCHAR(10) NOT NULL DEFAULT '🏛️',
            color VARCHAR(200) NOT NULL DEFAULT 'linear-gradient(135deg,#47915C,#2d6b40)',
            description TEXT DEFAULT '',
            advisory INTEGER NOT NULL DEFAULT 0,
            members_count INTEGER NOT NULL DEFAULT 0,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `committees` (
            `id` VARCHAR(36) NOT NULL,
            `name` VARCHAR(200) NOT NULL,
            `icon` VARCHAR(10) NOT NULL DEFAULT '🏛️',
            `color` VARCHAR(200) NOT NULL DEFAULT 'linear-gradient(135deg,#47915C,#2d6b40)',
            `description` TEXT DEFAULT NULL,
            `advisory` TINYINT(1) NOT NULL DEFAULT 0,
            `members_count` INT NOT NULL DEFAULT 0,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $checked = true;
}

function ensurePositionsTables(PDO $pdo): void
{
    static $checked = false;
    if ($checked) return;

    // Ensure committees table exists (FK dependency for positions.committee_id)
    ensureCommitteesTableMinimal($pdo);

    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS positions (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            icon VARCHAR(10) NOT NULL DEFAULT '📌',
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_core INTEGER NOT NULL DEFAULT 0,
            committee_id VARCHAR(36) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (committee_id) REFERENCES committees(id) ON DELETE SET NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS position_members (
            id INTEGER PRIMARY KEY,
            position_id VARCHAR(36) NOT NULL,
            member_id VARCHAR(36) NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (position_id, member_id),
            FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE,
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS position_tasks (
            id INTEGER PRIMARY KEY,
            position_id VARCHAR(36) NOT NULL,
            task_text VARCHAR(500) NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `positions` (
            `id` VARCHAR(36) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `icon` VARCHAR(10) NOT NULL DEFAULT '📌',
            `sort_order` SMALLINT NOT NULL DEFAULT 0,
            `is_core` TINYINT(1) NOT NULL DEFAULT 0,
            `committee_id` VARCHAR(36) DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`committee_id`) REFERENCES `committees`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `position_members` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT,
            `position_id` VARCHAR(36) NOT NULL,
            `member_id` VARCHAR(36) NOT NULL,
            `sort_order` SMALLINT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_pos_member` (`position_id`, `member_id`),
            FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `position_tasks` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT,
            `position_id` VARCHAR(36) NOT NULL,
            `task_text` VARCHAR(500) NOT NULL,
            `sort_order` SMALLINT NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // Seed core positions if table is empty
    $count = (int) $pdo->query('SELECT COUNT(*) FROM positions')->fetchColumn();
    if ($count === 0) {
        seedCorePositions($pdo);
    }

    $checked = true;
}

function seedCorePositions(PDO $pdo): void
{
    // Look up committee IDs for linked positions
    $financialCommitteeId = null;
    $advisoryCommitteeId  = null;
    try {
        $stmt = $pdo->query("SELECT id, name, advisory FROM committees ORDER BY sort_order ASC");
        $committees = $stmt->fetchAll();
        foreach ($committees as $c) {
            $name = $c['name'] ?? '';
            if (str_contains($name, 'مالية') || str_contains($name, 'الاستثمار')) {
                $financialCommitteeId = $financialCommitteeId ?? $c['id'];
            }
            if ((int)($c['advisory'] ?? 0) === 1 || str_contains($name, 'استشارية')) {
                $advisoryCommitteeId = $advisoryCommitteeId ?? $c['id'];
            }
        }
    } catch (PDOException $e) {
        // committees table may not exist yet
    }

    $corePositions = [
        [uid(), 'الرئيس',            '👑', 1, null],
        [uid(), 'نائب الرئيس',       '🤝', 2, null],
        [uid(), 'أمين الصندوق',      '💰', 3, $financialCommitteeId],
        [uid(), 'المنسق العام',      '📋', 4, null],
        [uid(), 'أمين السر',         '📝', 5, null],
        [uid(), 'اللجنة الاستشارية', '🛡️', 6, $advisoryCommitteeId],
    ];

    $posStmt = $pdo->prepare(
        'INSERT INTO positions (id, title, icon, sort_order, is_core, committee_id)
         VALUES (:id, :title, :icon, :sort, 1, :cid)'
    );

    $taskStmt = $pdo->prepare(
        'INSERT INTO position_tasks (position_id, task_text, sort_order)
         VALUES (:pid, :txt, :sort)'
    );

    // Tasks for each position (by sort_order index)
    $allTasks = [
        // 1: الرئيس
        [
            'الإشراف العام على أعمال المجلس',
            'إدارة الاجتماعات وتمثيل المجلس',
            'تمثيل المجلس أمام الجهات الرسمية',
            'اتخاذ القرارات النهائية بالتشاور مع الأعضاء',
        ],
        // 2: نائب الرئيس
        [
            'مساعدة الرئيس في مهامه',
            'إدارة المجلس في غياب الرئيس',
            'متابعة تنفيذ القرارات',
            'التنسيق بين اللجان المختلفة',
        ],
        // 3: أمين الصندوق
        [
            'إدارة الحسابات المالية للمجلس',
            'تحصيل الاشتراكات ومتابعة المتأخرين',
            'إعداد التقارير المالية الدورية',
            'صرف المبالغ المعتمدة حسب الميزانية',
        ],
        // 4: المنسق العام
        [
            'تنسيق الأنشطة والفعاليات',
            'التواصل مع الأعضاء وإبلاغهم بالمستجدات',
            'متابعة تنفيذ خطط العمل',
            'إعداد جدول أعمال الاجتماعات',
        ],
        // 5: أمين السر
        [
            'تدوين محاضر الاجتماعات',
            'حفظ الوثائق والمستندات الرسمية',
            'إرسال الدعوات والإشعارات',
            'توثيق القرارات والمتابعة',
        ],
        // 6: اللجنة الاستشارية
        [
            'تقديم المشورة والتوجيه لأعضاء المجلس',
            'المشاركة في اتخاذ القرارات المصيرية',
            'حل النزاعات والخلافات بين الأعضاء',
            'مراجعة أداء المجلس وتقديم التوصيات',
        ],
    ];

    foreach ($corePositions as $i => [$posId, $title, $icon, $sort, $cid]) {
        try {
            $posStmt->execute([
                ':id'    => $posId,
                ':title' => $title,
                ':icon'  => $icon,
                ':sort'  => $sort,
                ':cid'   => $cid,
            ]);

            // Insert tasks for this position
            foreach ($allTasks[$i] as $ti => $taskText) {
                $taskStmt->execute([
                    ':pid'  => $posId,
                    ':txt'  => $taskText,
                    ':sort' => $ti + 1,
                ]);
            }
        } catch (PDOException $e) {
            // Skip if already exists
            error_log('Seed position error: ' . $e->getMessage());
        }
    }
}
