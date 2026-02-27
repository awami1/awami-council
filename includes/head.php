<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<?php
  $ws = getWS();
  $siteTitle = esc($ws['header']['title']);
  $siteDesc  = esc($ws['hero']['description']);
  $pageTitle = isset($pageTitleOverride) && $pageTitleOverride ? esc($pageTitleOverride) . ' — ' . $siteTitle : $siteTitle;
?>
<title><?= $pageTitle ?></title>
<!-- SEO -->
<meta name="description" content="<?= $siteDesc ?>">
<meta name="robots" content="index, follow">
<?php $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'); ?>
<link rel="canonical" href="<?= esc($siteUrl . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)) ?>">
<link rel="sitemap" type="application/xml" href="<?= esc($siteUrl) ?>/sitemap.xml">
<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= $pageTitle ?>">
<meta property="og:description" content="<?= $siteDesc ?>">
<meta property="og:locale" content="ar_SA">
<meta property="og:url" content="<?= esc($siteUrl . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
<!-- Twitter Card -->
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= $pageTitle ?>">
<meta name="twitter:description" content="<?= $siteDesc ?>">
<!-- Structured Data (Organization) -->
<script type="application/ld+json"><?= json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'Organization',
  'name' => $ws['header']['title'] ?? 'مجلس عائلة العوامي',
  'description' => $ws['hero']['description'] ?? '',
  'url' => $siteUrl,
  'foundingDate' => '1992',
  'address' => ['@type' => 'PostalAddress', 'addressCountry' => 'SA'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Crect width='80' height='80' rx='16' fill='%231A5C32'/%3E%3Cpath d='M55 12 C58 8,65 10,64 18 C63 26,54 30,50 38 C46 46,48 56,42 62 C36 68,26 66,24 58 C22 50,30 44,32 36' stroke='%23fff' stroke-width='6' stroke-linecap='round' fill='none'/%3E%3Cpath d='M32 36 C28 44,20 46,20 54 C20 62,28 66,34 62' stroke='%23fff' stroke-width='5' stroke-linecap='round' fill='none'/%3E%3Ccircle cx='34' cy='62' r='5' fill='%23fff'/%3E%3C/svg%3E">
<!-- Theme: prevent flash -->
<script>
(function(){var t=localStorage.getItem('awami-theme')||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t)})();
</script>
<!-- Fonts -->
<link rel="dns-prefetch" href="https://fonts.googleapis.com">
<link rel="dns-prefetch" href="https://fonts.gstatic.com">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@300;400;600;700;900&family=Readex+Pro:wght@400;700&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<!-- CSS -->
<link rel="stylesheet" href="/public/css/variables.css">
<link rel="stylesheet" href="/public/css/base.css">
<link rel="stylesheet" href="/public/css/layout.css">
<link rel="stylesheet" href="/public/css/components.css">
<link rel="stylesheet" href="/public/css/animations.css">
</head>
