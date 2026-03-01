<?php
/**
 * stories.php — صفحة سِيَر وقصص أبناء العائلة
 * SSR: يعرض أول 6 مواضيع من PHP، ثم JS يتولى الفلترة والتصفح
 */
$ws = getWS();
$ssrStories = getPublishedStories(6);

$categoryLabels = [
    'biography'  => 'سيرة ذاتية',
    'self_made'  => 'قصة عصامية',
    'eulogy'     => 'رثاء',
    'tribute'    => 'مقال تكريمي',
    'other'      => 'أخرى',
];
?>

<section>
  <div class="section-header">
    <div class="section-badge">شخصيات من العائلة</div>
    <h2 class="section-title">سِيَر وقصص أبناء العائلة</h2>
    <p class="section-subtitle">قصص ملهمة وسِيَر عطرة من أبناء عائلة العوامي، أحياءً وراحلين</p>
  </div>

  <!-- فلاتر التصنيف وحالة الشخصية -->
  <div class="stories-filters">
    <div class="stories-filter-group" id="stories-cat-tabs">
      <button class="media-tab active stories-tab" data-category="">الكل</button>
      <button class="media-tab stories-tab" data-category="biography">سيرة ذاتية</button>
      <button class="media-tab stories-tab" data-category="self_made">قصة عصامية</button>
      <button class="media-tab stories-tab" data-category="eulogy">رثاء</button>
      <button class="media-tab stories-tab" data-category="tribute">مقال تكريمي</button>
    </div>
    <div class="stories-filter-group" id="stories-status-tabs">
      <button class="media-tab active stories-status-tab" data-person-status="">الجميع</button>
      <button class="media-tab stories-status-tab" data-person-status="alive">حفظه الله</button>
      <button class="media-tab stories-status-tab" data-person-status="deceased">رحمه الله</button>
    </div>
  </div>

  <div id="stories-container" style="max-width:1100px;margin:0 auto">
    <?php if (!empty($ssrStories)): ?>
    <!-- محتوى SSR — سيُستبدل بـ JS عند التحميل -->
    <div class="stories-grid">
      <?php foreach ($ssrStories as $s): ?>
        <?php $catClass = 'cat-' . ($s['category'] ?? 'other'); ?>
        <article class="story-card animate-in">
          <a href="/stories/<?= esc($s['slug'] ?? $s['id']) ?>" class="story-card-link">
            <div class="story-card-cover">
              <?php if (!empty($s['cover_image'])): ?>
                <img src="<?= esc($s['cover_image']) ?>" alt="<?= esc($s['title']) ?>" loading="lazy">
              <?php else: ?>
                <div class="story-card-placeholder">&#128214;</div>
              <?php endif; ?>
              <span class="story-category-badge <?= $catClass ?>"><?= esc($categoryLabels[$s['category']] ?? 'أخرى') ?></span>
              <?php if (!empty($s['is_pinned'])): ?>
                <span class="story-pin-badge" title="مثبّت">&#128204;</span>
              <?php endif; ?>
              <?php if (!empty($s['person_status'])): ?>
                <span class="story-person-status status-<?= esc($s['person_status']) ?>">
                  <?= $s['person_status'] === 'deceased' ? 'رحمه الله' : 'حفظه الله' ?>
                </span>
              <?php endif; ?>
            </div>
            <div class="story-card-body">
              <?php if (!empty($s['person_name'])): ?>
                <div class="story-card-person">
                  <?php if (!empty($s['person_image'])): ?>
                    <img src="<?= esc($s['person_image']) ?>" alt="" class="story-card-person-img" loading="lazy">
                  <?php else: ?>
                    <div class="story-card-person-img-placeholder">&#128100;</div>
                  <?php endif; ?>
                  <span class="story-card-person-name"><?= esc($s['person_name']) ?></span>
                </div>
              <?php endif; ?>
              <h3 class="story-card-title"><?= esc($s['title']) ?></h3>
              <?php if (!empty($s['excerpt'])): ?>
                <p class="story-card-excerpt"><?= esc($s['excerpt']) ?></p>
              <?php endif; ?>
              <div class="story-card-footer">
                <?php if (!empty($s['author_name'])): ?>
                  <span class="story-card-author">&#9998; <?= esc($s['author_name']) ?></span>
                <?php endif; ?>
                <span class="story-card-date">&#128197; <?= esc(substr($s['published_at'] ?? $s['created_at'] ?? '', 0, 10)) ?></span>
              </div>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
      <div style="font-size:52px;margin-bottom:12px;opacity:.4">&#128214;</div>
      <p>لا توجد قصص حالياً</p>
      <p style="font-size:13px;margin-top:8px">ترقبوا قريباً سِيَر وقصص ملهمة من أبناء العائلة</p>
    </div>
    <?php endif; ?>
  </div>
</section>
