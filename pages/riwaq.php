<?php
/**
 * riwaq.php — الرِّوَاق: معرض سينمائي لحكايا وسِيَر عائلة العوامي
 * صفحة مستقلة (standalone) لا تمر عبر layout.php
 */
require_once __DIR__ . '/../includes/helpers.php';

$stories = getActiveGalleryStories();

// مجموعات الألوان الافتراضية
$palettes = [
    ['p' => '#0B3D2E', 's' => '#1A6B4A', 'a' => '#D4AF37'],
    ['p' => '#1A1030', 's' => '#342A50', 'a' => '#B8A0D4'],
    ['p' => '#3A200A', 's' => '#6B4420', 'a' => '#E8B86D'],
    ['p' => '#0A2040', 's' => '#1A3A6A', 'a' => '#6CB4E8'],
    ['p' => '#1A2A1A', 's' => '#2A4A2A', 'a' => '#7BC88F'],
    ['p' => '#2A0A1A', 's' => '#5A2040', 'a' => '#D4899E'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>الرِّوَاق — مجلس عائلة العوامي</title>
<meta name="description" content="معرض سينمائي لحكايا وسِيَر عائلة العوامي">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Noto+Naskh+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('/public/css/riwaq.css') ?>">
</head>
<body>

<!-- ستارة الافتتاح -->
<div class="riwaq-curtain" id="riwaqCurtain">
  <div class="riwaq-curtain-inner">
    <h1 class="riwaq-curtain-title">الرِّوَاق</h1>
    <p class="riwaq-curtain-sub">معرض حكايا عائلة العوامي</p>
  </div>
</div>

<!-- الشريط العلوي -->
<div class="riwaq-topbar">
  <a href="/" class="riwaq-back" title="العودة للموقع">→</a>
  <span class="riwaq-topbar-title">الرِّوَاق</span>
  <span></span>
</div>

<!-- المعرض الأفقي -->
<div class="riwaq-gallery" id="riwaqGallery">

  <!-- لوحة الافتتاح -->
  <div class="riwaq-panel riwaq-panel--intro">
    <div class="riwaq-intro">
      <div class="riwaq-intro-line"></div>
      <p class="riwaq-intro-text">مرّر للاستكشاف ←</p>
    </div>
  </div>

  <!-- لوحات القصص -->
  <?php foreach ($stories as $i => $story):
    $pal = $palettes[$i % count($palettes)];
    $cp = !empty($story['color_primary'])   ? $story['color_primary']   : $pal['p'];
    $cs = !empty($story['color_secondary']) ? $story['color_secondary'] : $pal['s'];
    $ca = !empty($story['color_accent'])    ? $story['color_accent']    : $pal['a'];
  ?>
  <div class="riwaq-panel riwaq-panel--story" data-id="<?= esc($story['id']) ?>" data-cp="<?= esc($cp) ?>" data-cs="<?= esc($cs) ?>" data-ca="<?= esc($ca) ?>">
    <div class="riwaq-card" style="--cp:<?= esc($cp) ?>;--cs:<?= esc($cs) ?>;--ca:<?= esc($ca) ?>">
      <div class="riwaq-card__grain"></div>

      <!-- الرأس -->
      <div class="riwaq-card__top">
        <span class="riwaq-card__badge"><?= esc($story['type']) ?></span>
        <?php if (!empty($story['year_range'])): ?>
          <span class="riwaq-card__year"><?= esc($story['year_range']) ?></span>
        <?php endif; ?>
      </div>

      <!-- المحتوى -->
      <div class="riwaq-card__body">
        <?php if (!empty($story['quote'])): ?>
          <div class="riwaq-card__quote">
            <span class="riwaq-card__quote-mark">❝</span>
            <p><?= esc($story['quote']) ?></p>
          </div>
          <div class="riwaq-card__divider"></div>
        <?php endif; ?>

        <h2 class="riwaq-card__title"><?= esc($story['title']) ?></h2>

        <?php if (!empty($story['subtitle'])): ?>
          <p class="riwaq-card__subtitle"><?= esc($story['subtitle']) ?></p>
        <?php endif; ?>
      </div>

      <!-- الذيل -->
      <div class="riwaq-card__footer">
        <?php if (!empty($story['author_name'])): ?>
          <span class="riwaq-card__author"><?= esc($story['author_name']) ?></span>
        <?php endif; ?>
        <span class="riwaq-card__arrow">←</span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (empty($stories)): ?>
  <div class="riwaq-panel riwaq-panel--empty">
    <p class="riwaq-empty-text">لا توجد قصص بعد…</p>
  </div>
  <?php endif; ?>

  <!-- لوحة الختام -->
  <div class="riwaq-panel riwaq-panel--closing">
    <p class="riwaq-closing-text">…والقصص لا تنتهي</p>
  </div>

</div>

<!-- الشريط السفلي -->
<div class="riwaq-bottombar">
  <span>مجلس عائلة العوامي &middot; حكايا وسِيَر</span>
</div>

<!-- Overlay القصة الكاملة -->
<div class="riwaq-overlay" id="riwaqOverlay">
  <button class="riwaq-overlay__close" id="riwaqOverlayClose">&times;</button>
  <div class="riwaq-overlay__hero" id="riwaqOverlayHero">
    <div class="riwaq-overlay__hero-grain"></div>
    <div class="riwaq-overlay__hero-content">
      <span class="riwaq-overlay__badge" id="riwaqBadge"></span>
      <span class="riwaq-overlay__year" id="riwaqYear"></span>
      <h1 class="riwaq-overlay__title" id="riwaqTitle"></h1>
      <p class="riwaq-overlay__subtitle" id="riwaqSubtitle"></p>
      <div class="riwaq-overlay__divider"></div>
      <div class="riwaq-overlay__meta">
        <span id="riwaqAuthor"></span>
        <span id="riwaqReadTime"></span>
      </div>
    </div>
  </div>
  <article class="riwaq-overlay__body" id="riwaqBody"></article>
</div>

<!-- بيانات القصص لـ JS -->
<script>
window.__RIWAQ_STORIES__ = <?= json_encode(
    array_map(function($s) {
        return [
            'id'              => $s['id'],
            'title'           => $s['title'],
            'subtitle'        => $s['subtitle'] ?? '',
            'type'            => $s['type'],
            'year_range'      => $s['year_range'] ?? '',
            'quote'           => $s['quote'] ?? '',
            'full_text'       => $s['full_text'] ?? '',
            'author_name'     => $s['author_name'] ?? '',
            'read_time'       => (int)($s['read_time'] ?? 5),
            'color_primary'   => $s['color_primary'] ?? '',
            'color_secondary' => $s['color_secondary'] ?? '',
            'color_accent'    => $s['color_accent'] ?? '',
        ];
    }, $stories),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
) ?>;
</script>
<script src="<?= asset('/public/js/riwaq.js') ?>"></script>
</body>
</html>
