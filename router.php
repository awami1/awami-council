<?php
/**
 * router.php — Front Controller لموقع عائلة العوامي
 * يوجّه الطلبات إلى الصفحات المناسبة ويدعم AJAX navigation
 */

require_once __DIR__ . '/includes/helpers.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

// خريطة التوجيه
$routes = [
    '/'        => ['file' => 'pages/home.php',    'page' => 'home',    'title' => null,                   'scripts' => ['/public/js/countdown.js']],
    '/council' => ['file' => 'pages/council.php',  'page' => 'council', 'title' => 'المجلس',              'scripts' => ['/public/js/countdown.js']],
    '/tree'    => ['file' => 'pages/tree.php',     'page' => 'tree',    'title' => 'شجرة العائلة',        'scripts' => ['/public/js/tree.js']],
    '/news'    => ['file' => 'pages/news.php',     'page' => 'news',    'title' => 'الأخبار',             'scripts' => ['/public/js/news.js']],
    '/events'  => ['file' => 'pages/events.php',   'page' => 'events',  'title' => 'الفعاليات',            'scripts' => []],
    '/gallery' => ['file' => 'pages/gallery.php',  'page' => 'gallery', 'title' => 'المعرض',              'scripts' => ['/public/js/media.js']],
    '/contact' => ['file' => 'pages/contact.php',  'page' => 'contact', 'title' => 'تواصل معنا',          'scripts' => []],
    '/eid'     => ['file' => 'pages/eid.php',      'page' => 'eid',     'title' => 'تهنئة العيد',         'scripts' => ['/public/js/eid.js']],
];

$route = $routes[$uri] ?? null;

if (!$route) {
    http_response_code(404);
    $route = ['file' => 'pages/404.php', 'page' => '404', 'title' => 'الصفحة غير موجودة', 'scripts' => []];
}

$currentPage      = $route['page'];
$pageFile         = __DIR__ . '/' . $route['file'];
$pageScripts      = $route['scripts'];
$pageTitleOverride = $route['title'] ?? null;

// كشف طلبات AJAX — يُرجع محتوى الصفحة فقط بدون layout
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: text/html; charset=UTF-8');
    include $pageFile;
} else {
    include __DIR__ . '/includes/layout.php';
}
