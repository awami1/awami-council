<?php
/**
 * gallery.php — معرض الصور والفيديوهات (ألبومات + ميديا)
 */
$albums = getAlbumsWithCount();
$unassignedMedia = getUnassignedMedia();
?>

<section id="media" class="full-section" style="background:var(--bg-alt)">
  <div class="section-header">
    <div class="section-badge">أرشيف الفعاليات</div>
    <h2 class="section-title">المعرض</h2>
    <p class="section-subtitle">صور وفيديوهات من فعاليات المجلس</p>
  </div>

  <!-- عرض الألبومات -->
  <div id="gallery-albums-view">
    <?php if (empty($albums) && empty($unassignedMedia)): ?>
      <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
        <div style="font-size:52px;margin-bottom:12px;opacity:.4">📷</div>
        <p>لا توجد وسائط حالياً</p>
        <p style="font-size:12px;margin-top:8px">يمكن إضافة الوسائط من لوحة التحكم</p>
      </div>
    <?php else: ?>
      <?php if (!empty($albums)): ?>
        <div class="albums-grid">
          <?php foreach ($albums as $album): ?>
            <div class="album-card animate-in" onclick="openAlbum('<?= esc($album['id']) ?>')" data-album-id="<?= esc($album['id']) ?>">
              <?php if (!empty($album['cover_url'])): ?>
                <img class="album-card-cover" src="<?= esc($album['cover_url']) ?>" alt="<?= esc($album['title']) ?>" loading="lazy">
              <?php else: ?>
                <div class="album-card-cover" style="display:flex;align-items:center;justify-content:center;font-size:48px;color:var(--text-muted);background:var(--bg-alt)">📁</div>
              <?php endif; ?>
              <div class="album-card-body">
                <div class="album-card-title"><?= esc($album['title']) ?></div>
                <div class="album-card-meta">
                  <?= (int)($album['media_count'] ?? 0) ?> عنصر
                  <?php if (!empty($album['date'])): ?>
                    &middot; <span data-date="<?= esc($album['date']) ?>"><?= esc($album['date']) ?></span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($album['description'])): ?>
                  <div style="font-size:13px;color:var(--text-muted);margin-top:6px"><?= esc(mb_substr($album['description'], 0, 100)) ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($unassignedMedia)): ?>
        <div style="max-width:1200px;margin:0 auto;padding:0 20px">
          <?php if (!empty($albums)): ?>
            <h3 style="font-size:18px;font-weight:700;margin:30px 0 16px;color:var(--text)">عام</h3>
          <?php endif; ?>
          <div class="media-grid" id="media-grid-unassigned">
            <?php foreach ($unassignedMedia as $item): ?>
              <?php $itemType = $item['type'] ?? 'images'; ?>
              <?php $tags = is_string($item['tags'] ?? '') ? json_decode($item['tags'], true) : ($item['tags'] ?? []); ?>
              <div class="media-item animate-in" data-type="<?= esc($itemType) ?>" data-id="<?= esc($item['id']) ?>">
                <?php if ($itemType === 'videos'): ?>
                  <div class="media-item-thumb" onclick="openLightbox('videos','<?= esc($item['url'] ?? '') ?>','<?= esc($item['title'] ?? '') ?>')">
                    <video preload="metadata" style="width:100%;height:100%;object-fit:cover"><source src="<?= esc($item['url'] ?? '') ?>"></video>
                    <div class="media-play-overlay">▶</div>
                  </div>
                <?php elseif ($itemType === 'youtube'): ?>
                  <?php
                    $ytUrl = $item['url'] ?? '';
                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $ytUrl, $ytM);
                    $ytId = $ytM[1] ?? '';
                  ?>
                  <?php if ($ytId): ?>
                    <div class="media-item-thumb" onclick="openLightbox('youtube','<?= esc($ytId) ?>','<?= esc($item['title'] ?? '') ?>')">
                      <img src="https://img.youtube.com/vi/<?= esc($ytId) ?>/mqdefault.jpg" alt="<?= esc($item['title'] ?? '') ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover">
                      <div class="media-play-overlay">▶</div>
                    </div>
                  <?php endif; ?>
                <?php else: ?>
                  <img src="<?= esc($item['url'] ?? '') ?>" alt="<?= esc($item['title'] ?? '') ?>" loading="lazy" onclick="openLightbox('images','<?= esc($item['url'] ?? '') ?>','<?= esc($item['title'] ?? '') ?>')">
                <?php endif; ?>
                <div class="media-item-content">
                  <div class="media-item-title"><?= esc($item['title'] ?? '') ?></div>
                  <?php if (!empty($item['date'])): ?>
                    <div class="media-item-date" data-date="<?= esc($item['date']) ?>"></div>
                  <?php endif; ?>
                  <?php foreach ($tags as $tag): ?>
                    <span class="media-item-tag"><?= esc($tag) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- عرض محتوى ألبوم (يُملأ بـ JS) -->
  <div id="gallery-album-detail" style="display:none;max-width:1200px;margin:0 auto;padding:0 20px">
    <button class="album-back-btn" onclick="closeAlbum()">← رجوع للمعرض</button>
    <h3 id="album-detail-title" style="font-size:22px;font-weight:700;margin-bottom:6px"></h3>
    <p id="album-detail-desc" style="color:var(--text-muted);margin-bottom:20px"></p>
    <div class="media-grid" id="album-media-grid"></div>
  </div>
</section>

<script>
(function() {
  function _initGallery() {
    var obs = new IntersectionObserver(function(entries) {
      entries.forEach(function(e) { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    initGallery(obs);
  }
  if (typeof initGallery === 'function') {
    _initGallery();
  } else {
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof initGallery === 'function') _initGallery();
    });
  }
})();
</script>
