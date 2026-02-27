<?php
/**
 * ai_helper.php — أدوات مشتركة لوكيل AI
 * يُضمَّن في ai.php
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * تحقق من توفر مفتاح Anthropic API
 */
function isAIAvailable(): bool
{
    $key = getenv('ANTHROPIC_API_KEY') ?: ($_ENV['ANTHROPIC_API_KEY'] ?? '');
    return $key !== '' && str_starts_with($key, 'sk-ant-');
}

/**
 * استدعاء Anthropic Messages API
 *
 * @param string $systemPrompt System prompt
 * @param string $userMessage  User message
 * @param float  $temperature  Temperature (0.0-1.0)
 * @param int    $maxTokens    Max tokens in response
 * @return array Parsed JSON response
 * @throws \RuntimeException on failure
 */
function callAnthropic(
    string $systemPrompt,
    string $userMessage,
    float  $temperature = 0.3,
    int    $maxTokens = 4096
): array {
    $apiKey = getenv('ANTHROPIC_API_KEY') ?: ($_ENV['ANTHROPIC_API_KEY'] ?? '');
    if ($apiKey === '') {
        throw new \RuntimeException('مفتاح Anthropic API غير مُعرَّف. أضف ANTHROPIC_API_KEY في ملف .env');
    }

    $payload = json_encode([
        'model'       => 'claude-sonnet-4-6-20250514',
        'max_tokens'  => $maxTokens,
        'temperature' => $temperature,
        'system'      => $systemPrompt,
        'messages'    => [
            ['role' => 'user', 'content' => $userMessage],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new \RuntimeException('فشل الاتصال بـ Anthropic API: ' . $curlError);
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new \RuntimeException('استجابة غير صالحة من Anthropic API');
    }

    if ($httpCode !== 200) {
        $errMsg = $data['error']['message'] ?? ('HTTP ' . $httpCode);
        throw new \RuntimeException('خطأ من Anthropic API: ' . $errMsg);
    }

    // Extract text content from response
    $text = '';
    foreach ($data['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'text') {
            $text .= $block['text'];
        }
    }

    // Try to parse as JSON (our prompts always request JSON output)
    $parsed = json_decode($text, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Try extracting JSON from markdown code block
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $text, $m)) {
            $parsed = json_decode($m[1], true);
        }
    }

    return [
        'raw_text' => $text,
        'parsed'   => $parsed,
        'usage'    => $data['usage'] ?? [],
    ];
}

/**
 * تأكد من وجود جداول AI (تُنشأ تلقائياً إن لم تكن موجودة)
 */
function ensureAITables(PDO $pdo): void
{
    static $checked = false;
    if ($checked) return;

    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ai_reports (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            title VARCHAR(500) NOT NULL DEFAULT '',
            report_type VARCHAR(50) NOT NULL DEFAULT 'عام',
            original_text TEXT NOT NULL,
            summary_executive TEXT DEFAULT '',
            summary_extended TEXT DEFAULT '',
            summary_financial TEXT DEFAULT '',
            extracted_people TEXT DEFAULT '[]',
            extracted_decisions TEXT DEFAULT '[]',
            keywords TEXT DEFAULT '[]',
            total_income DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_expense DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_remaining DECIMAL(12,2) NOT NULL DEFAULT 0,
            report_date DATE DEFAULT NULL,
            synced_to_budget INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ai_report_items (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            report_id VARCHAR(36) NOT NULL,
            item_type VARCHAR(20) NOT NULL DEFAULT 'مصروف',
            description VARCHAR(500) NOT NULL DEFAULT '',
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            category VARCHAR(100) DEFAULT '',
            item_date DATE DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'مستخرج',
            transaction_id VARCHAR(36) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES ai_reports(id) ON DELETE CASCADE
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `ai_reports` (
            `id` VARCHAR(36) NOT NULL,
            `title` VARCHAR(500) NOT NULL DEFAULT '',
            `report_type` VARCHAR(50) NOT NULL DEFAULT 'عام',
            `original_text` TEXT NOT NULL,
            `summary_executive` TEXT DEFAULT '',
            `summary_extended` TEXT DEFAULT '',
            `summary_financial` TEXT DEFAULT '',
            `extracted_people` JSON DEFAULT NULL,
            `extracted_decisions` JSON DEFAULT NULL,
            `keywords` JSON DEFAULT NULL,
            `total_income` DECIMAL(12,2) NOT NULL DEFAULT 0,
            `total_expense` DECIMAL(12,2) NOT NULL DEFAULT 0,
            `total_remaining` DECIMAL(12,2) NOT NULL DEFAULT 0,
            `report_date` DATE DEFAULT NULL,
            `synced_to_budget` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_air_type` (`report_type`),
            INDEX `idx_air_date` (`report_date`),
            FULLTEXT INDEX `ftx_air_search` (`title`, `original_text`, `summary_extended`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `ai_report_items` (
            `id` VARCHAR(36) NOT NULL,
            `report_id` VARCHAR(36) NOT NULL,
            `item_type` VARCHAR(20) NOT NULL DEFAULT 'مصروف',
            `description` VARCHAR(500) NOT NULL DEFAULT '',
            `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
            `category` VARCHAR(100) DEFAULT '',
            `item_date` DATE DEFAULT NULL,
            `status` ENUM('مستخرج','مؤكد','مرفوض','مزامن') NOT NULL DEFAULT 'مستخرج',
            `transaction_id` VARCHAR(36) DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_ari_report` (`report_id`),
            INDEX `idx_ari_status` (`status`),
            CONSTRAINT `fk_ari_report` FOREIGN KEY (`report_id`) REFERENCES `ai_reports` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $checked = true;
}
