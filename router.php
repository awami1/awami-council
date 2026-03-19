<?php
/**
 * router.php — Front Controller لموقع عائلة العوامي
 * يوجّه الطلبات إلى الصفحات المناسبة ويدعم AJAX navigation
 * يعمل مع: Apache (.htaccess) / nginx (try_files) / PHP Built-in Server
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

// ─── PHP Built-in Server: تمرير الملفات الثابتة و API و Admin ───
if (php_sapi_name() === 'cli-server') {
    $filePath = __DIR__ . $uri;
    // ملفات ثابتة (CSS, JS, صور, خطوط)
    if ($uri !== '/' && is_file($filePath)) {
        return false; // دع PHP يخدم الملف مباشرة
    }
    // API requests
    if (str_starts_with($uri, '/api/')) {
        $apiFile = __DIR__ . $uri;
        if (is_file($apiFile)) {
            include $apiFile;
            return;
        }
    }
    // Member login
    if ($uri === '/login' || $uri === '/login/') {
        include __DIR__ . '/login/index.php';
        return;
    }
    // Member area
    if (str_starts_with($uri, '/member')) {
        $memberPath = $uri === '/member' || $uri === '/member/' ? '/member/index.php' : $uri;
        $memberFile = __DIR__ . $memberPath;
        // حماية من directory traversal
        $realMember = realpath($memberFile);
        if ($realMember && str_starts_with($realMember, __DIR__ . DIRECTORY_SEPARATOR . 'member') && is_file($realMember)) {
            include $realMember;
            return;
        }
    }
    // Admin panel
    if (str_starts_with($uri, '/admin')) {
        $adminPath = $uri === '/admin' || $uri === '/admin/' ? '/admin/index.php' : $uri;
        $adminFile = __DIR__ . $adminPath;
        // حماية من directory traversal
        $realAdmin = realpath($adminFile);
        if ($realAdmin && str_starts_with($realAdmin, __DIR__ . DIRECTORY_SEPARATOR . 'admin') && is_file($realAdmin)) {
            if (str_ends_with($realAdmin, '.php')) {
                include $realAdmin;
            } else {
                return false;
            }
            return;
        }
    }
    // Sitemap
    if ($uri === '/sitemap.xml') {
        include __DIR__ . '/sitemap.php';
        return;
    }
}

// ── Coming Soon Mode (حذف هذا البلوك عند الإطلاق) ──
$comingSoon = true;
$exemptPaths = ['/eid', '/admin', '/api', '/assets', '/public'];

if ($comingSoon) {
    $isExempt = false;
    foreach ($exemptPaths as $prefix) {
        if (str_starts_with($uri, $prefix)) {
            $isExempt = true;
            break;
        }
    }
    if (!$isExempt) {
        include __DIR__ . '/pages/coming-soon.php';
        exit;
    }
}

require_once __DIR__ . '/includes/helpers.php';

// خريطة التوجيه
$routes = [
    '/'        => ['file' => 'pages/home.php',    'page' => 'home',    'title' => null,                   'scripts' => ['/public/js/countdown.js']],
    '/council' => ['file' => 'pages/council.php',  'page' => 'council', 'title' => 'المجلس',              'scripts' => ['/public/js/countdown.js', '/public/js/positions.js']],
    '/tree'    => ['file' => 'pages/tree.php',     'page' => 'tree',    'title' => 'شجرة العائلة',        'scripts' => ['/public/js/tree.js']],
    '/news'    => ['file' => 'pages/news.php',     'page' => 'news',    'title' => 'الأخبار',             'scripts' => ['/public/js/news.js']],
    '/events'  => ['file' => 'pages/events.php',   'page' => 'events',  'title' => 'الفعاليات',            'scripts' => []],
    '/gallery' => ['file' => 'pages/gallery.php',  'page' => 'gallery', 'title' => 'المعرض',              'scripts' => ['/public/js/media.js']],
    '/contact' => ['file' => 'pages/contact.php',  'page' => 'contact', 'title' => 'تواصل معنا',          'scripts' => []],
    '/eid'     => ['file' => 'pages/eid.php',      'page' => 'eid',     'title' => 'تهنئة العيد',         'scripts' => ['/public/js/eid.js']],
    '/riwaq'   => ['file' => 'pages/riwaq.php',   'page' => 'riwaq',   'title' => 'الرِّوَاق',          'scripts' => [], 'standalone' => true],
    '/login'   => ['file' => 'login/index.php',  'page' => 'login',   'title' => 'تسجيل الدخول',       'scripts' => [], 'standalone' => true],
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
// يدعم طريقتين: هيدر X-Requested-With أو معامل ?_ajax=1 (fallback للاستضافات التي تحذف الهيدرات)
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
       || !empty($_GET['_ajax']);

if (!empty($route['standalone'])) {
    // صفحات مستقلة تعرض HTML كامل بدون layout
    include $pageFile;
} elseif ($isAjax) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    include $pageFile;
} else {
    include __DIR__ . '/includes/layout.php';
}
