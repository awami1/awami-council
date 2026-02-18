<?php
$content = file_get_contents(__DIR__ . '/members.php');
echo substr($content, 0, 300);
