<?php
/**
 * validation.php — دوال التحقق المشتركة لجميع نقاط الـ API
 * يُستخدم بدلاً من تكرار parseId() و sanitizeString() في كل ملف
 */
declare(strict_types=1);

/**
 * تحليل معرف من معاملات الطلب مع التحقق من صلاحيته
 */
function parseId(): ?string
{
    $id = $_GET['id'] ?? null;
    if ($id !== null && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id)) {
        respond(400, ['error' => 'Invalid ID format.']);
    }
    return $id;
}

/**
 * تنظيف وتحقق من أن القيمة نصية
 */
function sanitizeString(mixed $value, string $field): string
{
    if (!is_string($value) && !is_numeric($value)) {
        respond(422, ['error' => "Field '{$field}' must be a string."]);
    }
    return trim((string) $value);
}
