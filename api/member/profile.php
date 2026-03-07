<?php
/**
 * profile.php — الصفحة الشخصية للعضو
 *
 * GET → جلب بيانات العضو الشاملة:
 *   - البيانات الأساسية + AWM-ID + حالة العضوية
 *   - اللجان الحالية والسابقة مع الأدوار
 *   - سجل الاشتراكات مع حالة كل فترة
 *   - ملخص مالي (مدفوع / مطلوب / نسبة الالتزام)
 *   - الاعتراضات السابقة مع حالتها ورد المدير
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/member-guard.php';
require_once __DIR__ . '/../audit_helper.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    respond(405, ['error' => 'طريقة الطلب غير مسموحة']);
}

$member = requireMemberAuth();
$pdo    = getPDO();
$memberId = $member['member_id'];

// ══════════════════════════════════════════
// 1. البيانات الأساسية للعضو
// ══════════════════════════════════════════
$stmt = $pdo->prepare(
    'SELECT m.id, m.name, m.family, m.phone, m.id_num, m.join_date,
            m.status, m.branch_id, m.notes,
            mu.awm_id, mu.last_login, mu.created_at AS account_created,
            b.name AS branch_name
     FROM members m
     JOIN member_users mu ON mu.member_id = m.id
     LEFT JOIN branches b ON b.id = m.branch_id
     WHERE m.id = :id
     LIMIT 1'
);
$stmt->execute([':id' => $memberId]);
$info = $stmt->fetch();

if (!$info) {
    respond(404, ['error' => 'لم يتم العثور على بيانات العضو']);
}

$profile = [
    'name'            => $info['name'],
    'family'          => $info['family'],
    'phone'           => $info['phone'],
    'id_num'          => $info['id_num'],
    'join_date'       => $info['join_date'],
    'status'          => $info['status'],
    'branch_name'     => $info['branch_name'],
    'awm_id'          => $info['awm_id'],
    'last_login'      => $info['last_login'],
    'account_created' => $info['account_created'],
];

// ══════════════════════════════════════════
// 2. اللجان الحالية (end_year IS NULL)
// ══════════════════════════════════════════
$stmt = $pdo->prepare(
    'SELECT c.id, c.name, c.icon, c.color,
            cm.role, cm.start_year, cm.created_at AS joined_at
     FROM committee_members cm
     JOIN committees c ON c.id = cm.committee_id
     WHERE cm.member_id = :id AND cm.end_year IS NULL
     ORDER BY cm.start_year ASC, c.name ASC'
);
$stmt->execute([':id' => $memberId]);
$currentCommittees = $stmt->fetchAll();

// ══════════════════════════════════════════
// 3. اللجان السابقة (end_year IS NOT NULL)
// ══════════════════════════════════════════
$stmt = $pdo->prepare(
    'SELECT c.id, c.name, c.icon, c.color,
            cm.role, cm.start_year, cm.end_year
     FROM committee_members cm
     JOIN committees c ON c.id = cm.committee_id
     WHERE cm.member_id = :id AND cm.end_year IS NOT NULL
     ORDER BY cm.end_year DESC, c.name ASC'
);
$stmt->execute([':id' => $memberId]);
$pastCommittees = $stmt->fetchAll();

// ══════════════════════════════════════════
// 4. سجل الاشتراكات (كل الفترات مع حالة الدفع)
// ══════════════════════════════════════════
$stmt = $pdo->prepare(
    'SELECT p.id AS period_id, p.name AS period_name,
            p.fee_amount, p.start_date, p.end_date,
            pay.amount AS paid_amount, pay.pay_date,
            pay.status AS pay_status, pay.method
     FROM periods p
     LEFT JOIN payments pay ON pay.period_id = p.id AND pay.member_id = :id
     ORDER BY p.start_date ASC'
);
$stmt->execute([':id' => $memberId]);
$subscriptions = $stmt->fetchAll();

// ── حساب الملخص المالي ──
$totalRequired = 0.0;
$totalPaid     = 0.0;
$periodsCount  = 0;

foreach ($subscriptions as &$sub) {
    $feeAmount = (float) ($sub['fee_amount'] ?? 0);
    $paidAmount = (float) ($sub['paid_amount'] ?? 0);

    // تحديد حالة الدفع
    if ($sub['pay_status'] === null) {
        $sub['pay_status'] = 'لم يدفع';
        $sub['paid_amount'] = 0;
    }

    $totalRequired += $feeAmount;
    $totalPaid     += $paidAmount;
    $periodsCount++;
}
unset($sub);

$complianceRate = $totalRequired > 0
    ? round(($totalPaid / $totalRequired) * 100, 1)
    : 0;

$financialSummary = [
    'total_required'  => $totalRequired,
    'total_paid'      => $totalPaid,
    'total_unpaid'    => $totalRequired - $totalPaid,
    'compliance_rate' => $complianceRate,
    'periods_count'   => $periodsCount,
];

// ══════════════════════════════════════════
// 5. الاعتراضات السابقة
// ══════════════════════════════════════════
$stmt = $pdo->prepare(
    'SELECT id, subject, body, related_to, related_id,
            status, admin_reply, replied_at, created_at
     FROM member_objections
     WHERE member_id = :id
     ORDER BY created_at DESC'
);
$stmt->execute([':id' => $memberId]);
$objections = $stmt->fetchAll();

// ══════════════════════════════════════════
// إرجاع JSON الشامل
// ══════════════════════════════════════════
respond(200, [
    'profile'             => $profile,
    'current_committees'  => $currentCommittees,
    'past_committees'     => $pastCommittees,
    'subscriptions'       => $subscriptions,
    'financial_summary'   => $financialSummary,
    'objections'          => $objections,
]);
