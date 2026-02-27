<?php
/**
 * sitemap.php — خريطة الموقع الديناميكية (XML Sitemap)
 */

header('Content-Type: application/xml; charset=utf-8');

// تحميل إعدادات قاعدة البيانات للأخبار
require_once __DIR__ . '/api/config.php';

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

$today = date('Y-m-d');

// الصفحات الثابتة
$staticPages = [
    ['url' => '/',        'priority' => '1.0', 'changefreq' => 'daily'],
    ['url' => '/council', 'priority' => '0.8', 'changefreq' => 'weekly'],
    ['url' => '/tree',    'priority' => '0.7', 'changefreq' => 'monthly'],
    ['url' => '/news',    'priority' => '0.9', 'changefreq' => 'daily'],
    ['url' => '/gallery', 'priority' => '0.7', 'changefreq' => 'weekly'],
    ['url' => '/contact', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['url' => '/eid',     'priority' => '0.5', 'changefreq' => 'yearly'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($staticPages as $page): ?>
  <url>
    <loc><?= htmlspecialchars($baseUrl . $page['url']) ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq><?= $page['changefreq'] ?></changefreq>
    <priority><?= $page['priority'] ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
