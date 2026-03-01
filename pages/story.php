<?php
/**
 * story.php — صفحة عرض موضوع/قصة واحدة بالتفصيل
 * تدعم SEO-friendly URLs عبر slug
 */
$ws = getWS();

$categoryLabels = [
    'biography'  => 'سيرة ذاتية',
    'self_made'  => 'قصة عصامية',
    'eulogy'     => 'رثاء',
    'tribute'    => 'مقال تكريمي',
    'other'      => 'أخرى',
];

$categoryColors = [
    'biography'  => 'rgba(26,92,50,.88)',
    'self_made'  => 'rgba(200,168,75,.92)',
    'eulogy'     => 'rgba(27,52,86,.88)',
    'tribute'    => 'rgba(139,69,19,.88)',
    'other'      => 'rgba(107,124,110,.88)',
];

// جلب slug من URL
$storySlug = $_GET['slug'] ?? '';
$story = null;
if ($storySlug) {
    $story = getStoryBySlug($storySlug);
}
?>

<?php if ($story): ?>
<div class="story-single" id="story-single" data-slug="<?= esc($storySlug) ?>">

  <!-- زر العودة -->
  <div style="max-width:800px;margin:0 auto;padding:20px 24px 0">
    <a href="/stories" class="story-back-btn">&#8594; العودة لجميع القصص</a>
  </div>

  <!-- صورة الغلاف -->
  <?php if (!empty($story['cover_image'])): ?>
  <div class="story-hero">
    <img src="<?= esc($story['cover_image']) ?>" alt="<?= esc($story['title']) ?>">
    <div class="story-hero-overlay">
      <span class="story-hero-category" style="background:<?= $categoryColors[$story['category']] ?? 'rgba(107,124,110,.88)' ?>">
        <?= esc($categoryLabels[$story['category']] ?? 'أخرى') ?>
      </span>
      <h1 class="story-hero-title"><?= esc($story['title']) ?></h1>
      <div class="story-hero-meta">
        <?php if (!empty($story['author_name'])): ?>
          <span>&#9998; <?= esc($story['author_name']) ?></span>
        <?php endif; ?>
        <span>&#128197; <?= esc(substr($story['published_at'] ?? $story['created_at'] ?? '', 0, 10)) ?></span>
        <?php if (!empty($story['person_name'])): ?>
          <span>&#128100; <?= esc($story['person_name']) ?>
            <?php if ($story['person_status'] === 'deceased'): ?>
              (رحمه الله)
            <?php else: ?>
              (حفظه الله)
            <?php endif; ?>
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div style="max-width:800px;margin:0 auto;padding:0 24px 20px">
    <span class="story-category-badge cat-<?= esc($story['category']) ?>" style="position:static;display:inline-block;margin-bottom:12px">
      <?= esc($categoryLabels[$story['category']] ?? 'أخرى') ?>
    </span>
    <h1 style="font-family:var(--font-heading);font-size:32px;font-weight:700;color:var(--text);line-height:1.5;margin-bottom:12px"><?= esc($story['title']) ?></h1>
    <div style="display:flex;gap:20px;font-size:14px;color:var(--text-muted);flex-wrap:wrap">
      <?php if (!empty($story['author_name'])): ?>
        <span>&#9998; <?= esc($story['author_name']) ?></span>
      <?php endif; ?>
      <span>&#128197; <?= esc(substr($story['published_at'] ?? $story['created_at'] ?? '', 0, 10)) ?></span>
    </div>
  </div>
  <?php endif; ?>

  <div class="story-content-wrap">

    <!-- بطاقة الشخصية -->
    <?php if (!empty($story['person_name'])): ?>
    <div class="story-person-card">
      <?php if (!empty($story['person_image'])): ?>
        <img src="<?= esc($story['person_image']) ?>" alt="<?= esc($story['person_name']) ?>" class="story-person-card-img">
      <?php else: ?>
        <div class="story-person-card-img-placeholder">&#128100;</div>
      <?php endif; ?>
      <div class="story-person-card-info">
        <div class="story-person-card-name"><?= esc($story['person_name']) ?></div>
        <div class="story-person-card-status <?= $story['person_status'] === 'deceased' ? 'deceased' : 'alive' ?>">
          <?= $story['person_status'] === 'deceased' ? '&#128336; رحمه الله وأسكنه فسيح جناته' : '&#127807; حفظه الله ورعاه' ?>
        </div>
        <?php if (!empty($story['person_bio'])): ?>
          <div class="story-person-card-bio"><?= esc($story['person_bio']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- المحتوى الرئيسي -->
    <div class="story-article-content">
      <?= $story['content'] ?? '' ?>
    </div>

    <!-- معلومات الكاتب -->
    <?php if (!empty($story['author_name'])): ?>
    <div class="story-author-info">
      <div class="story-author-avatar">&#9998;</div>
      <div class="story-author-details">
        <strong><?= esc($story['author_name']) ?></strong>
        <span>&#128197; <?= esc(substr($story['published_at'] ?? $story['created_at'] ?? '', 0, 10)) ?></span>
      </div>
    </div>
    <?php endif; ?>

    <!-- أزرار المشاركة -->
    <div class="story-share-bar">
      <span class="story-share-label">&#128279; مشاركة:</span>
      <a class="story-share-btn whatsapp" href="https://wa.me/?text=<?= urlencode($story['title'] . ' — ') ?>" target="_blank" rel="noopener" id="share-whatsapp">
        واتساب
      </a>
      <a class="story-share-btn twitter" href="https://twitter.com/intent/tweet?text=<?= urlencode($story['title'] . ' — ') ?>" target="_blank" rel="noopener" id="share-twitter">
        تويتر
      </a>
      <button class="story-share-btn copy" onclick="copyStoryLink()" id="share-copy">
        &#128203; نسخ الرابط
      </button>
    </div>

    <!-- زر العودة -->
    <div style="text-align:center;margin-top:40px">
      <a href="/stories" class="story-back-btn">&#8594; العودة لجميع القصص</a>
    </div>
  </div>
</div>

<script>
function copyStoryLink() {
  var url = window.location.href;
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url).then(function() {
      var btn = document.getElementById('share-copy');
      btn.textContent = '\u2705 تم النسخ!';
      setTimeout(function() { btn.innerHTML = '&#128203; نسخ الرابط'; }, 2000);
    });
  }
}
// تحديث روابط المشاركة بالرابط الحالي
(function() {
  var url = encodeURIComponent(window.location.href);
  var title = encodeURIComponent(document.querySelector('.story-hero-title, h1')?.textContent || '');
  var wa = document.getElementById('share-whatsapp');
  if (wa) wa.href = 'https://wa.me/?text=' + title + '%20' + url;
  var tw = document.getElementById('share-twitter');
  if (tw) tw.href = 'https://twitter.com/intent/tweet?text=' + title + '&url=' + url;
})();
</script>

<?php else: ?>
<!-- الموضوع غير موجود — سيتم محاولة التحميل عبر JS -->
<div id="story-single" data-slug="<?= esc($storySlug) ?>">
  <div style="max-width:800px;margin:0 auto;padding:20px 24px 0">
    <a href="/stories" class="story-back-btn">&#8594; العودة لجميع القصص</a>
  </div>
  <div id="story-loading" style="text-align:center;padding:60px 20px;color:var(--text-muted)">
    <div class="skeleton" style="width:200px;height:20px;margin:0 auto 16px;border-radius:8px"></div>
    <div class="skeleton" style="width:300px;height:14px;margin:0 auto;border-radius:8px"></div>
    <p style="margin-top:16px">جاري تحميل الموضوع...</p>
  </div>
</div>
<?php endif; ?>
