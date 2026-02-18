<?php
require_once __DIR__ . '/config.php';
echo function_exists('getPDO') ? 'OK - getPDO exists' : 'FAIL - getPDO not defined';
