<?php
/**
 * ai.php — Backend proxy لوكيل AI
 * كل طلبات AI تمر عبر هذا الـ endpoint لحماية API key
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
require_once __DIR__ . '/ai_helper.php';
requireAuth();

// ──────────────────────────────────────────────────────────────
// HANDLERS
// ──────────────────────────────────────────────────────────────

/**
 * GET ?action=check — هل AI متاح؟
 */
function handleCheck(): void
{
    respond(200, ['available' => isAIAvailable()]);
}

/**
 * POST ?action=analyze-members — تحليل أعضاء من Excel
 * Body: { cells: [...], existingMembers: [...], familyTree: [...], branches: [...] }
 */
function handleAnalyzeMembers(): void
{
    $data = bodyJson();
    $cells = $data['cells'] ?? [];

    if (empty($cells)) {
        respond(422, ['error' => 'لا توجد بيانات للتحليل']);
    }

    $existingMembers = $data['existingMembers'] ?? [];
    $familyTree = $data['familyTree'] ?? [];
    $branches = $data['branches'] ?? [];

    if (!isAIAvailable()) {
        // Fallback: تحليل محلي بسيط
        $result = localAnalyzeMembers($cells, $existingMembers, $branches);
        respond(200, ['data' => $result, 'mode' => 'local']);
    }

    // AI Analysis
    $systemPrompt = <<<'PROMPT'
أنت خبير تحليل بيانات متخصص في أسماء عربية وبيانات العائلات السعودية.

مهمتك: تحليل خلايا Excel مرفوعة وتصنيفها.

## القواعد:
1. صنّف كل خلية إلى أحد الأنواع: اسم_شخص، رقم_هاتف، رقم_هوية، عنوان، ملاحظة، رأس_جدول، خلية_فارغة، بيانات_أخرى
2. للأسماء: حدد مستوى ثقة (عالي/متوسط/منخفض) مع سبب الشك إن وُجد
3. قارن الأسماء مع الأعضاء الحاليين (انتبه لاختلافات الهمزة والتشكيل وتبديل الأحرف)
4. للأسماء الجديدة: اقترح فرع عائلي بناءً على اسم العائلة إن أمكن
5. اكتشف التكرار حتى لو كان بصياغات مختلفة (مثل: أحمد / احمد / أحمد بن...)

## تنسيق الإخراج (JSON فقط):
{
  "confirmed": [
    {"name": "...", "phone": "...", "idNum": "...", "family": "...", "existsInSystem": false, "suggestedBranch": "...", "matchedMemberId": null}
  ],
  "needsReview": [
    {"name": "...", "reason": "...", "confidence": "متوسط", "phone": "...", "existsInSystem": false, "suggestedBranch": "...", "matchedMemberId": null}
  ],
  "unknownData": [
    {"value": "...", "detectedType": "رأس_جدول", "row": 1}
  ],
  "duplicates": [
    {"name": "...", "duplicateOf": "...", "type": "في_الملف|في_النظام"}
  ],
  "stats": {
    "totalCells": 0,
    "confirmedNames": 0,
    "reviewNames": 0,
    "unknownCells": 0,
    "duplicateCount": 0
  }
}
PROMPT;

    // Prepare the context for AI
    $memberNames = array_map(fn($m) => $m['name'] ?? '', $existingMembers);
    $branchNames = array_map(fn($b) => [
        'id'   => $b['id'] ?? '',
        'name' => $b['name'] ?? '',
    ], $branches);

    $userMessage = "## خلايا Excel المرفوعة:\n" . json_encode($cells, JSON_UNESCAPED_UNICODE) .
        "\n\n## الأعضاء الحاليون في النظام:\n" . json_encode($memberNames, JSON_UNESCAPED_UNICODE) .
        "\n\n## الفروع العائلية:\n" . json_encode($branchNames, JSON_UNESCAPED_UNICODE);

    try {
        $aiResult = callAnthropic($systemPrompt, $userMessage, 0.2, 4096);

        if ($aiResult['parsed']) {
            logAudit('تحليل_AI', 'استيراد_أعضاء', '', '', ['cells_count' => count($cells)]);
            respond(200, ['data' => $aiResult['parsed'], 'mode' => 'ai']);
        } else {
            // AI responded but not valid JSON — fallback
            $result = localAnalyzeMembers($cells, $existingMembers, $branches);
            respond(200, ['data' => $result, 'mode' => 'local', 'warning' => 'تعذر تحليل استجابة AI، تم استخدام التحليل المحلي']);
        }
    } catch (\Throwable $e) {
        // AI failed — fallback to local
        $result = localAnalyzeMembers($cells, $existingMembers, $branches);
        respond(200, ['data' => $result, 'mode' => 'local', 'warning' => 'فشل الاتصال بالذكاء الاصطناعي: ' . $e->getMessage()]);
    }
}

/**
 * تحليل محلي للأعضاء (fallback عند عدم توفر AI)
 */
function localAnalyzeMembers(array $cells, array $existingMembers, array $branches): array
{
    $confirmed = [];
    $needsReview = [];
    $unknownData = [];
    $duplicates = [];
    $seenNames = [];

    $memberNameMap = [];
    foreach ($existingMembers as $m) {
        $normalized = normalizeArabicName($m['name'] ?? '');
        $memberNameMap[$normalized] = $m;
    }

    foreach ($cells as $idx => $cell) {
        $value = is_array($cell) ? ($cell['value'] ?? ($cell['v'] ?? '')) : (string) $cell;
        $value = trim((string) $value);

        if ($value === '' || $value === null) {
            $unknownData[] = ['value' => '', 'detectedType' => 'خلية_فارغة', 'row' => $idx + 1];
            continue;
        }

        // Detect type
        $type = detectCellType($value);

        if ($type === 'phone') {
            $unknownData[] = ['value' => $value, 'detectedType' => 'رقم_هاتف', 'row' => $idx + 1];
            continue;
        }

        if ($type === 'id_number') {
            $unknownData[] = ['value' => $value, 'detectedType' => 'رقم_هوية', 'row' => $idx + 1];
            continue;
        }

        if ($type === 'header') {
            $unknownData[] = ['value' => $value, 'detectedType' => 'رأس_جدول', 'row' => $idx + 1];
            continue;
        }

        if ($type === 'number') {
            $unknownData[] = ['value' => $value, 'detectedType' => 'بيانات_أخرى', 'row' => $idx + 1];
            continue;
        }

        if ($type !== 'name') {
            $unknownData[] = ['value' => $value, 'detectedType' => 'بيانات_أخرى', 'row' => $idx + 1];
            continue;
        }

        // It looks like a name
        $normalized = normalizeArabicName($value);

        // Check for duplicates in file
        if (isset($seenNames[$normalized])) {
            $duplicates[] = ['name' => $value, 'duplicateOf' => $seenNames[$normalized], 'type' => 'في_الملف'];
            continue;
        }
        $seenNames[$normalized] = $value;

        // Check against existing members
        $existsInSystem = isset($memberNameMap[$normalized]);
        $matchedMemberId = $existsInSystem ? ($memberNameMap[$normalized]['id'] ?? null) : null;

        // Suggest branch
        $suggestedBranch = suggestBranch($value, $branches);

        $entry = [
            'name'            => $value,
            'phone'           => '',
            'idNum'           => '',
            'family'          => extractFamilyName($value),
            'existsInSystem'  => $existsInSystem,
            'suggestedBranch' => $suggestedBranch,
            'matchedMemberId' => $matchedMemberId,
        ];

        // Determine confidence
        $wordCount = mb_substr_count($value, ' ') + 1;
        if ($wordCount >= 2 && $wordCount <= 6 && !preg_match('/\d/', $value)) {
            $confirmed[] = $entry;
        } else {
            $entry['reason'] = $wordCount < 2 ? 'اسم من كلمة واحدة' :
                              ($wordCount > 6 ? 'اسم طويل جداً' : 'يحتوي أرقام');
            $entry['confidence'] = 'متوسط';
            $needsReview[] = $entry;
        }
    }

    return [
        'confirmed'   => $confirmed,
        'needsReview' => $needsReview,
        'unknownData' => $unknownData,
        'duplicates'  => $duplicates,
        'stats'       => [
            'totalCells'     => count($cells),
            'confirmedNames' => count($confirmed),
            'reviewNames'    => count($needsReview),
            'unknownCells'   => count($unknownData),
            'duplicateCount' => count($duplicates),
        ],
    ];
}

/**
 * تطبيع اسم عربي للمقارنة
 */
function normalizeArabicName(string $name): string
{
    $name = mb_strtolower(trim($name));
    // Remove tashkeel
    $name = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $name);
    // Normalize hamza variants
    $name = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $name);
    $name = str_replace('ة', 'ه', $name);
    $name = str_replace('ى', 'ي', $name);
    // Remove extra spaces
    $name = preg_replace('/\s+/', ' ', $name);
    return $name;
}

/**
 * اكتشاف نوع الخلية
 */
function detectCellType(string $value): string
{
    // Phone patterns (Saudi)
    if (preg_match('/^0[0-9]{9,10}$/', preg_replace('/[\s\-\+]/', '', $value))) {
        return 'phone';
    }
    if (preg_match('/^\+?966[0-9]{8,9}$/', preg_replace('/[\s\-]/', '', $value))) {
        return 'phone';
    }

    // ID number (Saudi: 10 digits starting with 1 or 2)
    if (preg_match('/^[12]\d{9}$/', preg_replace('/\s/', '', $value))) {
        return 'id_number';
    }

    // Pure number
    if (preg_match('/^[\d,.\s]+$/', $value)) {
        return 'number';
    }

    // Common header keywords
    $headers = ['الاسم', 'الجوال', 'الهاتف', 'رقم', 'الحالة', 'ملاحظات', 'العنوان', 'تاريخ', 'م', '#',
                'name', 'phone', 'status', 'notes', 'email', 'البريد', 'الفرع', 'العائلة', 'الهوية'];
    $lower = mb_strtolower(trim($value));
    foreach ($headers as $h) {
        if ($lower === mb_strtolower($h)) return 'header';
    }

    // Check if it looks like a name (Arabic characters, 2-6 words)
    if (preg_match('/^[\p{Arabic}\s\-\.]+$/u', $value) && mb_strlen($value) >= 3) {
        return 'name';
    }

    // Mixed Arabic with some numbers might be an address
    if (preg_match('/[\p{Arabic}]/u', $value) && preg_match('/\d/', $value)) {
        return 'other';
    }

    // Short Arabic text is likely a name
    if (preg_match('/[\p{Arabic}]/u', $value) && mb_strlen($value) >= 3 && mb_strlen($value) <= 100) {
        return 'name';
    }

    return 'other';
}

/**
 * استخراج اسم العائلة (الكلمة الأخيرة عادة)
 */
function extractFamilyName(string $name): string
{
    $parts = explode(' ', trim($name));
    return count($parts) > 1 ? end($parts) : '';
}

/**
 * اقتراح فرع عائلي بناءً على الاسم
 */
function suggestBranch(string $name, array $branches): string
{
    $familyName = extractFamilyName($name);
    if ($familyName === '') return '';

    $normalizedFamily = normalizeArabicName($familyName);

    foreach ($branches as $branch) {
        $branchName = normalizeArabicName($branch['name'] ?? '');
        if (str_contains($branchName, $normalizedFamily) || str_contains($normalizedFamily, $branchName)) {
            return $branch['name'] ?? '';
        }
    }

    return '';
}

// ──────────────────────────────────────────────────────────────
// REPORT ANALYSIS
// ──────────────────────────────────────────────────────────────

/**
 * POST ?action=analyze-report — تحليل تقرير نصي
 * Body: { text: "...", reportType: "اجتماع|فعالية|مالي|عام" }
 */
function handleAnalyzeReport(): void
{
    $data = bodyJson();
    $text = trim($data['text'] ?? '');
    $reportType = $data['reportType'] ?? 'عام';

    if ($text === '') {
        respond(422, ['error' => 'النص مطلوب']);
    }

    if (mb_strlen($text) < 10) {
        respond(422, ['error' => 'النص قصير جداً للتحليل']);
    }

    if (!isAIAvailable()) {
        respond(503, ['error' => 'الذكاء الاصطناعي غير متاح. يرجى التأكد من إضافة ANTHROPIC_API_KEY في ملف .env']);
    }

    $systemPrompt = <<<'PROMPT'
أنت محلل تقارير ذكي متخصص في تحليل تقارير المجالس والجمعيات العائلية.

مهمتك: تحليل نص التقرير المرفق واستخراج المعلومات التالية بدقة.

## تنسيق الإخراج (JSON فقط):
{
  "title": "عنوان مناسب للتقرير",
  "reportDate": "YYYY-MM-DD أو null إذا لم يُذكر",
  "summaryExecutive": "ملخص تنفيذي في سطرين كحد أقصى",
  "summaryExtended": "ملخص موسّع في فقرة واحدة",
  "summaryFinancial": "ملخص مالي: الأرقام والمبالغ فقط بشكل واضح",
  "financialItems": [
    {"type": "إيراد|مصروف", "description": "...", "amount": 0.00, "category": "...", "date": "YYYY-MM-DD أو null"}
  ],
  "decisions": [
    {"decision": "...", "responsible": "...", "deadline": "YYYY-MM-DD أو null"}
  ],
  "people": [
    {"name": "...", "role": "..."}
  ],
  "keywords": ["كلمة1", "كلمة2"],
  "totals": {
    "income": 0.00,
    "expense": 0.00,
    "remaining": 0.00
  }
}

## قواعد مهمة:
- إذا لم يُذكر مبلغ مالي، financialItems تكون مصفوفة فارغة
- إذا لم تُذكر قرارات، decisions تكون مصفوفة فارغة
- استخرج الأشخاص فقط إذا ذُكرت أسماؤهم صراحة
- الفئات المالية المتاحة: رسوم الأعضاء، رحلة العمرة، غداء العيد، رحلة ترفيهية، مسابقة، مصاريف إدارية، استثمار، عقيقة جماعية، تبرعات، صيانة، إيجار، أخرى
- الملخص المالي يكون فقط أرقام بدون شرح، مثال: "الإيرادات: 5,000 ريال | المصروفات: 3,200 ريال | المتبقي: 1,800 ريال"
PROMPT;

    $userMessage = "## نوع التقرير: {$reportType}\n\n## نص التقرير:\n{$text}";

    try {
        $aiResult = callAnthropic($systemPrompt, $userMessage, 0.3, 4096);

        if ($aiResult['parsed']) {
            // Check for budget conflicts
            $conflicts = detectBudgetConflicts($aiResult['parsed']['financialItems'] ?? []);
            $aiResult['parsed']['conflicts'] = $conflicts;

            logAudit('تحليل_AI', 'تقرير', '', $aiResult['parsed']['title'] ?? '', ['type' => $reportType]);
            respond(200, ['data' => $aiResult['parsed'], 'mode' => 'ai']);
        } else {
            respond(500, ['error' => 'تعذر تحليل استجابة الذكاء الاصطناعي. يرجى المحاولة مرة أخرى.']);
        }
    } catch (\Throwable $e) {
        respond(500, ['error' => 'فشل التحليل: ' . $e->getMessage()]);
    }
}

/**
 * كشف تعارضات مع الميزانية الحالية
 */
function detectBudgetConflicts(array $financialItems): array
{
    if (empty($financialItems)) return [];

    $pdo = getPDO();
    $conflicts = [];

    foreach ($financialItems as $item) {
        $amount = (float) ($item['amount'] ?? 0);
        $desc = $item['description'] ?? '';
        if ($amount <= 0 || $desc === '') continue;

        // Check if this exact item already exists
        $stmt = $pdo->prepare(
            'SELECT id, description, amount, tx_date FROM transactions
             WHERE ABS(amount - :amount) < 0.01
             AND description LIKE :desc
             LIMIT 1'
        );
        $stmt->execute([
            ':amount' => $amount,
            ':desc'   => '%' . mb_substr($desc, 0, 50) . '%',
        ]);

        $existing = $stmt->fetch();
        if ($existing) {
            $conflicts[] = [
                'item'     => $desc,
                'amount'   => $amount,
                'status'   => 'مسجَّل',
                'txId'     => $existing['id'],
                'txDate'   => $existing['tx_date'],
            ];
        }
    }

    return $conflicts;
}

/**
 * POST ?action=save-report — حفظ تقرير محلَّل
 * Body: { title, reportType, originalText, summary*, extracted*, totals, financialItems[], reportDate }
 */
function handleSaveReport(): void
{
    $pdo = getPDO();
    ensureAITables($pdo);
    $data = bodyJson();

    $reportId = uid();

    $pdo->prepare(
        'INSERT INTO ai_reports
            (id, title, report_type, original_text, summary_executive, summary_extended, summary_financial,
             extracted_people, extracted_decisions, keywords, total_income, total_expense, total_remaining, report_date)
         VALUES
            (:id, :title, :type, :text, :se, :sx, :sf, :people, :decisions, :keywords, :income, :expense, :remaining, :date)'
    )->execute([
        ':id'        => $reportId,
        ':title'     => $data['title'] ?? '',
        ':type'      => $data['reportType'] ?? 'عام',
        ':text'      => $data['originalText'] ?? '',
        ':se'        => $data['summaryExecutive'] ?? '',
        ':sx'        => $data['summaryExtended'] ?? '',
        ':sf'        => $data['summaryFinancial'] ?? '',
        ':people'    => json_encode($data['people'] ?? [], JSON_UNESCAPED_UNICODE),
        ':decisions' => json_encode($data['decisions'] ?? [], JSON_UNESCAPED_UNICODE),
        ':keywords'  => json_encode($data['keywords'] ?? [], JSON_UNESCAPED_UNICODE),
        ':income'    => (float) ($data['totals']['income'] ?? 0),
        ':expense'   => (float) ($data['totals']['expense'] ?? 0),
        ':remaining' => (float) ($data['totals']['remaining'] ?? 0),
        ':date'      => $data['reportDate'] ?? null,
    ]);

    // Save financial items
    $items = $data['financialItems'] ?? [];
    $insertStmt = $pdo->prepare(
        'INSERT INTO ai_report_items (id, report_id, item_type, description, amount, category, item_date, status)
         VALUES (:id, :rid, :type, :desc, :amount, :cat, :date, :status)'
    );

    foreach ($items as $item) {
        $insertStmt->execute([
            ':id'     => uid(),
            ':rid'    => $reportId,
            ':type'   => $item['type'] ?? 'مصروف',
            ':desc'   => $item['description'] ?? '',
            ':amount' => (float) ($item['amount'] ?? 0),
            ':cat'    => $item['category'] ?? '',
            ':date'   => $item['date'] ?? null,
            ':status' => 'مستخرج',
        ]);
    }

    logAudit('إضافة', 'تقرير_ذكي', $reportId, $data['title'] ?? '');

    respond(201, ['id' => $reportId, 'message' => 'تم حفظ التقرير بنجاح']);
}

/**
 * POST ?action=budget-items — تأكيد/رفض بنود مالية وإضافتها للميزانية
 * Body: { items: [{id, status, amount?, category?, description?}] }
 */
function handleSaveBudgetItems(): void
{
    $pdo = getPDO();
    ensureAITables($pdo);
    $data = bodyJson();

    $items = $data['items'] ?? [];
    if (empty($items)) {
        respond(422, ['error' => 'لا توجد بنود']);
    }

    $synced = 0;
    $rejected = 0;

    foreach ($items as $item) {
        $itemId = $item['id'] ?? '';
        $status = $item['status'] ?? '';

        if ($itemId === '' || !in_array($status, ['مؤكد', 'مرفوض'], true)) continue;

        if ($status === 'مرفوض') {
            $pdo->prepare('UPDATE ai_report_items SET status = :status WHERE id = :id')
                ->execute([':status' => 'مرفوض', ':id' => $itemId]);
            $rejected++;
            continue;
        }

        // Fetch the item
        $stmt = $pdo->prepare('SELECT * FROM ai_report_items WHERE id = :id AND status = :status LIMIT 1');
        $stmt->execute([':id' => $itemId, ':status' => 'مستخرج']);
        $reportItem = $stmt->fetch();

        if (!$reportItem) continue;

        // Override values if provided
        $amount = (float) ($item['amount'] ?? $reportItem['amount']);
        $category = $item['category'] ?? $reportItem['category'];
        $description = $item['description'] ?? $reportItem['description'];
        $type = $reportItem['item_type'];
        $date = $reportItem['item_date'] ?? date('Y-m-d');

        // Create transaction
        $txId = uid();
        $pdo->prepare(
            'INSERT INTO transactions (id, type, amount, category, description, tx_date)
             VALUES (:id, :type, :amount, :cat, :desc, :date)'
        )->execute([
            ':id'     => $txId,
            ':type'   => $type,
            ':amount' => $amount,
            ':cat'    => $category,
            ':desc'   => $description,
            ':date'   => $date,
        ]);

        // Update report item status
        $pdo->prepare('UPDATE ai_report_items SET status = :status, transaction_id = :txid WHERE id = :id')
            ->execute([':status' => 'مزامن', ':txid' => $txId, ':id' => $itemId]);

        $synced++;
    }

    logAudit('مزامنة_ميزانية', 'تقرير_ذكي', '', '', ['synced' => $synced, 'rejected' => $rejected]);

    respond(200, [
        'synced'   => $synced,
        'rejected' => $rejected,
        'message'  => "تم مزامنة {$synced} بند مع الميزانية",
    ]);
}

/**
 * POST ?action=search-reports — بحث في التقارير المؤرشفة
 * Body: { query: "..." }
 */
function handleSearchReports(): void
{
    $pdo = getPDO();
    ensureAITables($pdo);
    $data = bodyJson();
    $query = trim($data['query'] ?? '');

    if ($query === '') {
        respond(422, ['error' => 'يرجى إدخال كلمة للبحث']);
    }

    if (isSQLite()) {
        $stmt = $pdo->prepare(
            'SELECT id, title, report_type, summary_executive, total_income, total_expense,
                    total_remaining, report_date, created_at
             FROM ai_reports
             WHERE title LIKE :q OR original_text LIKE :q2 OR summary_extended LIKE :q3
             ORDER BY created_at DESC
             LIMIT 50'
        );
        $like = '%' . $query . '%';
        $stmt->execute([':q' => $like, ':q2' => $like, ':q3' => $like]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, title, report_type, summary_executive, total_income, total_expense,
                    total_remaining, report_date, created_at
             FROM ai_reports
             WHERE MATCH(title, original_text, summary_extended) AGAINST(:q IN NATURAL LANGUAGE MODE)
             ORDER BY created_at DESC
             LIMIT 50"
        );
        $stmt->execute([':q' => $query]);
    }

    respond(200, ['data' => $stmt->fetchAll()]);
}

/**
 * GET ?action=reports — جلب كل التقارير المؤرشفة
 */
function handleGetReports(): void
{
    $pdo = getPDO();
    ensureAITables($pdo);

    $stmt = $pdo->query(
        'SELECT id, title, report_type, summary_executive, total_income, total_expense,
                total_remaining, report_date, synced_to_budget, created_at
         FROM ai_reports
         ORDER BY created_at DESC
         LIMIT 100'
    );

    respond(200, ['data' => $stmt->fetchAll()]);
}

/**
 * GET ?action=report&id=xxx — جلب تقرير واحد بالتفصيل
 */
function handleGetReport(): void
{
    $pdo = getPDO();
    ensureAITables($pdo);
    $id = $_GET['id'] ?? '';

    if ($id === '') {
        respond(422, ['error' => 'معرّف التقرير مطلوب']);
    }

    $stmt = $pdo->prepare('SELECT * FROM ai_reports WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $report = $stmt->fetch();

    if (!$report) {
        respond(404, ['error' => 'التقرير غير موجود']);
    }

    // Parse JSON fields
    foreach (['extracted_people', 'extracted_decisions', 'keywords'] as $field) {
        if (is_string($report[$field])) {
            $report[$field] = json_decode($report[$field], true) ?? [];
        }
    }

    // Fetch items
    $itemStmt = $pdo->prepare(
        'SELECT * FROM ai_report_items WHERE report_id = :rid ORDER BY created_at'
    );
    $itemStmt->execute([':rid' => $id]);
    $report['items'] = $itemStmt->fetchAll();

    respond(200, ['data' => $report]);
}

/**
 * GET ?action=quarterly-draft&quarter=1&year=2025 — مسودة التقرير الربعي
 */
function handleQuarterlyDraft(): void
{
    $pdo = getPDO();
    ensureAITables($pdo);

    $quarter = (int) ($_GET['quarter'] ?? ceil((int) date('n') / 3));
    $year = (int) ($_GET['year'] ?? (int) date('Y'));

    // Calculate quarter date range
    $startMonth = (($quarter - 1) * 3) + 1;
    $endMonth = $startMonth + 2;
    $startDate = sprintf('%04d-%02d-01', $year, $startMonth);
    $endDate = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $endMonth)));

    // Fetch synced items in this quarter
    $stmt = $pdo->prepare(
        'SELECT ari.*, ar.title as report_title, ar.report_type
         FROM ai_report_items ari
         JOIN ai_reports ar ON ari.report_id = ar.id
         WHERE ari.status = :status
         AND (ari.item_date BETWEEN :start AND :end
              OR (ari.item_date IS NULL AND ari.created_at BETWEEN :start2 AND :end2))
         ORDER BY ari.item_date ASC'
    );
    $stmt->execute([
        ':status' => 'مزامن',
        ':start'  => $startDate,
        ':end'    => $endDate,
        ':start2' => $startDate,
        ':end2'   => $endDate . ' 23:59:59',
    ]);
    $items = $stmt->fetchAll();

    // Fetch transactions in this quarter (for full picture)
    $txStmt = $pdo->prepare(
        'SELECT type, amount, category, description, tx_date
         FROM transactions
         WHERE tx_date BETWEEN :start AND :end
         ORDER BY tx_date ASC'
    );
    $txStmt->execute([':start' => $startDate, ':end' => $endDate]);
    $transactions = $txStmt->fetchAll();

    // Calculate totals
    $totalIncome = 0;
    $totalExpense = 0;
    $byCategory = [];

    foreach ($transactions as $tx) {
        $amount = (float) $tx['amount'];
        $cat = $tx['category'] ?: 'أخرى';
        if ($tx['type'] === 'إيراد') {
            $totalIncome += $amount;
        } else {
            $totalExpense += $amount;
        }
        if (!isset($byCategory[$cat])) $byCategory[$cat] = ['income' => 0, 'expense' => 0];
        $byCategory[$cat][$tx['type'] === 'إيراد' ? 'income' : 'expense'] += $amount;
    }

    // Reports count for this quarter
    $reportCountStmt = $pdo->prepare(
        'SELECT COUNT(*) as cnt FROM ai_reports
         WHERE (report_date BETWEEN :start AND :end)
            OR (report_date IS NULL AND created_at BETWEEN :start2 AND :end2)'
    );
    $reportCountStmt->execute([
        ':start' => $startDate, ':end' => $endDate,
        ':start2' => $startDate, ':end2' => $endDate . ' 23:59:59',
    ]);
    $reportCount = (int) $reportCountStmt->fetchColumn();

    respond(200, [
        'quarter'       => $quarter,
        'year'          => $year,
        'period'        => "{$startDate} إلى {$endDate}",
        'totalIncome'   => $totalIncome,
        'totalExpense'  => $totalExpense,
        'netBalance'    => $totalIncome - $totalExpense,
        'byCategory'    => $byCategory,
        'reportCount'   => $reportCount,
        'syncedItems'   => $items,
        'transactions'  => $transactions,
    ]);
}

/**
 * DELETE ?action=report&id=xxx — حذف تقرير
 */
function handleDeleteReport(): void
{
    $pdo = getPDO();
    ensureAITables($pdo);
    $id = $_GET['id'] ?? '';

    if ($id === '') {
        respond(422, ['error' => 'معرّف التقرير مطلوب']);
    }

    $stmt = $pdo->prepare('SELECT title FROM ai_reports WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $report = $stmt->fetch();

    if (!$report) {
        respond(404, ['error' => 'التقرير غير موجود']);
    }

    $pdo->prepare('DELETE FROM ai_reports WHERE id = :id')->execute([':id' => $id]);
    logAudit('حذف', 'تقرير_ذكي', $id, $report['title'] ?? '');

    respond(200, ['message' => 'تم حذف التقرير']);
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    match (true) {
        $method === 'GET'    && $action === 'check'            => handleCheck(),
        $method === 'POST'   && $action === 'analyze-members'  => handleAnalyzeMembers(),
        $method === 'POST'   && $action === 'analyze-report'   => handleAnalyzeReport(),
        $method === 'POST'   && $action === 'save-report'      => handleSaveReport(),
        $method === 'POST'   && $action === 'budget-items'     => handleSaveBudgetItems(),
        $method === 'POST'   && $action === 'search-reports'   => handleSearchReports(),
        $method === 'GET'    && $action === 'reports'          => handleGetReports(),
        $method === 'GET'    && $action === 'report'           => handleGetReport(),
        $method === 'GET'    && $action === 'quarterly-draft'  => handleQuarterlyDraft(),
        $method === 'DELETE' && $action === 'report'           => handleDeleteReport(),
        default => respond(405, ['error' => 'الإجراء غير مدعوم: ' . $action]),
    };
} catch (\PDOException $e) {
    respond(500, ['error' => 'خطأ في قاعدة البيانات', 'detail' => $e->getMessage()]);
}
