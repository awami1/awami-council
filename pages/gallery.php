<?php
/**
 * gallery.php — معرض الصور والفيديوهات
 */
$ws = getWS();
?>

<section id="media" class="full-section" style="background:var(--bg-alt)">
  <div class="section-header">
    <div class="section-badge">أرشيف الفعاليات</div>
    <h2 class="section-title">المعرض</h2>
    <p class="section-subtitle">صور وفيديوهات من فعاليات المجلس</p>
  </div>
  <div class="media-tabs" role="tablist" aria-label="تصفية المعرض">
    <button class="media-tab active" data-filter="all" role="tab" aria-selected="true">الكل</button>
    <button class="media-tab" data-filter="images" role="tab" aria-selected="false">&#x1F4F7; الصور</button>
    <button class="media-tab" data-filter="videos" role="tab" aria-selected="false">&#x1F3AC; الفيديوهات</button>
    <button class="media-tab" data-filter="youtube" role="tab" aria-selected="false">&#x25B6;&#xFE0F; يوتيوب</button>
    <button class="media-tab" data-filter="events" role="tab" aria-selected="false">&#x1F389; الفعاليات</button>
  </div>
  <div class="media-grid" id="media-grid">
    <?php $mediaItems = $ws['media'] ?? []; ?>
    <?php if (empty($mediaItems)): ?>
      <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted)">
        <div style="font-size:52px;margin-bottom:12px;opacity:.4">&#x1F4F7;</div>
        <p>لا توجد وسائط حالياً</p>
        <p style="font-size:12px;margin-top:8px">يمكن إضافة الوسائط من لوحة التحكم</p>
      </div>
    <?php else: ?>
      <?php foreach ($mediaItems as $item): ?>
        <?php $itemType = $item['type'] ?? 'images'; ?>
        <div class="media-item animate-in" data-type="<?= esc($itemType) ?>">
          <?php if ($itemType === 'videos'): ?>
            <video controls preload="metadata"><source src="<?= esc($item['url'] ?? '') ?>"></video>
          <?php elseif ($itemType === 'youtube'): ?>
            <?php
              $ytUrl = $item['url'] ?? '';
              preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $ytUrl, $ytM);
              $ytId = $ytM[1] ?? '';
            ?>
            <?php if ($ytId): ?>
              <div class="media-item-youtube">
                <iframe src="https://www.youtube.com/embed/<?= esc($ytId) ?>" loading="lazy" allowfullscreen title="<?= esc($item['title'] ?? '') ?>"></iframe>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <img src="<?= esc($item['url'] ?? '') ?>" alt="<?= esc($item['title'] ?? '') ?>" loading="lazy" onclick="openLightbox(this)">
          <?php endif; ?>
          <div class="media-item-content">
            <div class="media-item-title"><?= esc($item['title'] ?? '') ?></div>
            <?php if (!empty($item['date'])): ?>
              <div class="media-item-date" data-date="<?= esc($item['date']) ?>"></div>
            <?php endif; ?>
            <?php foreach ($item['tags'] ?? [] as $tag): ?>
              <span class="media-item-tag"><?= esc($tag) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<script>
(function() {
  if (typeof initMedia === 'function') {
    var obs = new IntersectionObserver(function(entries) {
      entries.forEach(function(e) { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    initMedia(obs);
  }
})();
</script>
