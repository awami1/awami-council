<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
requireAuth();
echo substr(file_get_contents(__DIR__ . '/members.php'), 0, 150);
