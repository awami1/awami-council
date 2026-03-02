<?php
// health.php — فحص سريع بدون اتصال بقاعدة البيانات
// يُستخدم من CranL للتأكد إن السيرفر شغال

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'ok', 'time' => date('c')]);
