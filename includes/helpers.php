<?php
/**
 * helpers.php — دوال مساعدة مشتركة لجميع صفحات الموقع العام
 */

require_once __DIR__ . '/../api/config.php';

/** سنة تأسيس المجلس */
const FOUNDING_YEAR = 1992;

/**
 * جلب إعدادات الموقع من قاعدة البيانات (مع cache)
 */
function getWS(): array {
    static $ws = null;
    if ($ws !== null) return $ws;
    $defaults = [
        'header'           => ['title' => 'مجلس عائلة العوامي', 'subtitle' => 'AL AWAMI • ١٤١٣ - ١٩٩٢'],
        'hero'             => ['title' => 'مرحباً بكم في مجلس عائلة العوامي', 'description' => 'منذ عام ١٩٩٢م - ١٤١٣هـ، نعمل على تعزيز الترابط الأسري وخدمة أفراد العائلة من خلال الأنشطة والفعاليات المتنوعة التي تُنظّم بروح الأُلفة والتعاون والمسؤولية'],
        'stats'            => ['years' => 32, 'committees' => 11, 'members' => '+100'],
        'about'            => ['mission' => 'تعزيز الترابط الأسري والتواصل بين أفراد عائلة العوامي من خلال تنظيم الأنشطة والفعاليات الدينية والاجتماعية والترفيهية التي تحقق المصلحة العامة وتُرسّخ القيم الأصيلة.', 'vision' => 'أن نكون مجلساً عائلياً نموذجياً يُحتذى به في التنظيم والتطوير والخدمة، ونسعى لبناء جيل واعٍ ومتماسك يفخر بانتمائه لعائلة العوامي.'],
        'councilPositions' => [],
        'values'           => [],
        'logo'             => null,
        'media'            => [],
        'contact'          => ['whatsapp' => ''],
    ];
    try {
        $pdo = getPDO();
        $row = $pdo->query("SELECT data FROM website_settings WHERE id=1 LIMIT 1")->fetch();
        if ($row) {
            $saved = json_decode($row['data'], true) ?: [];
            $ws = array_replace_recursive($defaults, $saved);
        } else {
            $ws = $defaults;
        }
    } catch (Throwable $e) {
        error_log('getWS() failed: ' . $e->getMessage());
        $ws = $defaults;
    }
    return $ws;
}

/**
 * ترميز HTML لمنع XSS
 */
function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * جلب بيانات الاجتماع القادم
 */
function getMeeting(): ?array {
    try {
        $pdo = getPDO();
        $row = $pdo->query("SELECT * FROM next_meeting WHERE id=1 LIMIT 1")->fetch();
        if (!$row || !$row['visible'] || !$row['date']) return null;
        return ['date' => $row['date'], 'title' => $row['title'], 'visible' => true];
    } catch (Throwable $e) { error_log('getMeeting() failed: ' . $e->getMessage()); return null; }
}

/**
 * جلب فروع العائلة
 */
function getBranches(): array {
    try {
        $pdo = getPDO();
        return $pdo->query("SELECT * FROM family_branches ORDER BY name ASC")->fetchAll();
    } catch (Throwable $e) { error_log('getBranches() failed: ' . $e->getMessage()); return []; }
}

/**
 * جلب الفعاليات القادمة
 */
function getUpcomingEvents(): array {
    try {
        $pdo = getPDO();
        $dateFunc = isSQLite() ? "date('now')" : "CURDATE()";
        return $pdo->query("
            SELECT id, name, icon, event_date, `lead`
            FROM events
            WHERE status = 'قادم'
              AND (event_date IS NULL OR event_date >= {$dateFunc})
            ORDER BY event_date ASC
            LIMIT 6
        ")->fetchAll();
    } catch (Throwable $e) { error_log('getUpcomingEvents() failed: ' . $e->getMessage()); return []; }
}

/**
 * جلب آخر الأخبار المنشورة
 */
function getPublishedNews(int $limit = 3): array {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT id, title, excerpt, image, category, author, created_at
            FROM news
            WHERE status = 'published'
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Throwable $e) { error_log('getPublishedNews() failed: ' . $e->getMessage()); return []; }
}

/**
 * حساب عدد الأعضاء النشطين
 */
function getActiveMembersCount(): int {
    try {
        $pdo = getPDO();
        $row = $pdo->query("SELECT COUNT(*) as cnt FROM members WHERE status = 'نشط'")->fetch();
        return (int)($row['cnt'] ?? 0);
    } catch (Throwable $e) { error_log('getActiveMembersCount() failed: ' . $e->getMessage()); return 0; }
}

/**
 * حساب عدد اللجان
 */
function getCommitteesCount(): int {
    try {
        $pdo = getPDO();
        $row = $pdo->query("SELECT COUNT(*) as cnt FROM committees")->fetch();
        return (int)($row['cnt'] ?? 0);
    } catch (Throwable $e) { error_log('getCommitteesCount() failed: ' . $e->getMessage()); return 0; }
}

/**
 * جلب اللجان مع عدد الأعضاء
 */
function getCommittees(): array {
    try {
        $pdo = getPDO();
        $committees = $pdo->query("SELECT * FROM committees ORDER BY sort_order ASC, created_at ASC")->fetchAll();
        $counts = [];
        try {
            $rows = $pdo->query("SELECT committee_id, COUNT(*) as cnt FROM committee_members GROUP BY committee_id")->fetchAll();
            foreach ($rows as $r) $counts[$r['committee_id']] = (int)$r['cnt'];
        } catch (Throwable $e) { error_log('getCommittees() member counts failed: ' . $e->getMessage()); }
        foreach ($committees as &$c) {
            $linkedCount = $counts[$c['id']] ?? 0;
            $manualCount = (int) ($c['members_count'] ?? 0);
            $c['member_count'] = max($linkedCount, $manualCount);
        }
        unset($c);
        return $committees;
    } catch (Throwable $e) { error_log('getCommittees() failed: ' . $e->getMessage()); return []; }
}

/**
 * جلب أعضاء شجرة العائلة
 */
function getFamilyTree(): array {
    try {
        $pdo = getPDO();
        return $pdo->query("SELECT * FROM family_tree ORDER BY sort_order ASC, name ASC")->fetchAll();
    } catch (Throwable $e) { error_log('getFamilyTree() failed: ' . $e->getMessage()); return []; }
}

/**
 * جلب جميع الفعاليات
 */
function getAllEvents(int $limit = 20): array {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT id, name, icon, event_date, status, `lead`, participants, notes
            FROM events
            ORDER BY event_date DESC, created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Throwable $e) { error_log('getAllEvents() failed: ' . $e->getMessage()); return []; }
}

/**
 * إحصائيات ديناميكية حقيقية
 */
function getDynamicStats(): array {
    $ws = getWS();
    $members = getActiveMembersCount();
    $committees = getCommitteesCount();
    $years = (int)date('Y') - FOUNDING_YEAR;
    return [
        'years'      => $years > 0 ? $years : (int)$ws['stats']['years'],
        'committees' => $committees > 0 ? $committees : (int)$ws['stats']['committees'],
        'members'    => $members > 0 ? '+' . $members : $ws['stats']['members'],
    ];
}
