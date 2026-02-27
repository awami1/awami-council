<?php
/**
 * news.php — صفحة الأخبار والمناسبات
 * SSR: يعرض أول 6 أخبار من PHP، ثم JS يتولى الفلترة والتصفح
 */
$ws = getWS();
$ssrNews = getPublishedNews(6);
?>

<section>
  <div class="section-header">
    <div class="section-badge">آخر المستجدات</div>
    <h2 class="section-title">الأخبار والمناسبات</h2>
    <p class="section-subtitle">تابع آخر أخبار وفعاليات مجلس عائلة العوامي</p>
  </div>

  <!-- تبويبات التصنيفات -->
  <div class="media-tabs" id="news-tabs">
    <button class="media-tab active news-tab" data-category="">الكل</button>
    <button class="media-tab news-tab" data-category="عام">عام</button>
    <button class="media-tab news-tab" data-category="إعلانات">إعلانات</button>
    <button class="media-tab news-tab" data-category="فعاليات">فعاليات</button>
    <button class="media-tab news-tab" data-category="اجتماعات">اجتماعات</button>
    <button class="media-tab news-tab" data-category="اجتماعية">اجتماعية</button>
    <button class="media-tab news-tab" data-category="مالية">مالية</button>
  </div>

  <div id="news-container" style="max-width:1000px;margin:0 auto">
    <?php if (!empty($ssrNews)): ?>
    <!-- محتوى SSR — سيُستبدل بـ JS عند التحميل -->
    <div class="news-grid">
      <?php foreach ($ssrNews as $n): ?>
        <article class="news-card animate-in">
          <?php if (!empty($n['image'])): ?>
            <div class="news-card-img"><img src="<?= esc($n['image']) ?>" alt="<?= esc($n['title']) ?>" loading="lazy"></div>
          <?php else: ?>
            <div class="news-card-img news-card-placeholder"><span>&#128240;</span></div>
          <?php endif; ?>
          <div class="news-card-body">
            <div class="news-card-meta">
              <span class="news-card-cat"><?= esc($n['category'] ?? 'عام') ?></span>
              <span class="news-card-date">&#128197; <?= esc(substr($n['created_at'] ?? '', 0, 10)) ?></span>
            </div>
            <h3 class="news-card-title"><?= esc($n['title']) ?></h3>
            <?php if (!empty($n['excerpt'])): ?>
              <p class="news-card-excerpt"><?= esc($n['excerpt']) ?></p>
            <?php endif; ?>
            <?php if (!empty($n['author'])): ?>
              <div class="news-card-author">&#128100; <?= esc($n['author']) ?></div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
      <div style="font-size:52px;margin-bottom:12px;opacity:.4">&#128240;</div>
      <p>لا توجد أخبار حالياً</p>
      <p style="font-size:13px;margin-top:8px">ترقبوا قريباً آخر أخبار وفعاليات العائلة</p>
    </div>
    <?php endif; ?>
  </div>
</section>
