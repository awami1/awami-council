<?php $ws = getWS(); ?>

<footer>
  <div class="footer-content">
    <div class="footer-logo">
      <svg viewBox="0 0 80 80" fill="none">
        <path d="M55 12 C58 8,65 10,64 18 C63 26,54 30,50 38 C46 46,48 56,42 62 C36 68,26 66,24 58 C22 50,30 44,32 36" stroke="#fff" stroke-width="6" stroke-linecap="round" fill="none"/>
        <path d="M32 36 C28 44,20 46,20 54 C20 62,28 66,34 62" stroke="#fff" stroke-width="5" stroke-linecap="round" fill="none"/>
        <circle cx="34" cy="62" r="5" fill="#fff"/>
      </svg>
    </div>
    <p class="footer-name"><?= esc($ws['header']['title']) ?></p>
    <p class="footer-year">تأسس عام ١٩٩٢م - ١٤١٣هـ</p>
    <?php if (empty($comingSoon)): ?>
    <nav class="footer-links" aria-label="روابط سريعة">
      <a href="/">الرئيسية</a>
      <a href="/council">المجلس</a>
      <a href="/tree">شجرة العائلة</a>
      <a href="/news">الأخبار</a>
      <a href="/events">الفعاليات</a>
      <a href="/gallery">المعرض</a>
      <a href="/contact">تواصل معنا</a>
    </nav>
    <?php endif; ?>
    <?php if (!empty($ws['contact']['whatsapp'])): ?>
    <div class="footer-whatsapp">
      <a href="https://wa.me/<?= esc(preg_replace('/\D/', '', $ws['contact']['whatsapp'])) ?>" target="_blank" rel="noopener">
        &#128241; تواصل معنا عبر واتساب
      </a>
    </div>
    <?php endif; ?>
    <p class="footer-copy">جميع الحقوق محفوظة &copy; <?= date('Y') ?> <?= esc($ws['header']['title']) ?></p>
  </div>
</footer>


<!-- Lightbox (صور + فيديو + يوتيوب) -->
<div class="lightbox-overlay" id="lightbox" role="dialog" aria-modal="true" aria-label="معاينة الوسائط">
  <button class="lightbox-close" onclick="closeLightbox()" aria-label="إغلاق">&#10005;</button>
  <button class="lightbox-nav lightbox-prev" onclick="lightboxNav(-1)" aria-label="السابق">&#8250;</button>
  <div class="lightbox-content" id="lightbox-content">
    <img class="lightbox-img" id="lightbox-img" src="" alt="" style="display:none">
    <video class="lightbox-video" id="lightbox-video" controls style="display:none"></video>
    <div class="lightbox-iframe-wrap" id="lightbox-iframe-wrap" style="display:none">
      <iframe id="lightbox-iframe" src="" allowfullscreen title="فيديو يوتيوب"></iframe>
    </div>
  </div>
  <button class="lightbox-nav lightbox-next" onclick="lightboxNav(1)" aria-label="التالي">&#8249;</button>
  <div class="lightbox-caption" id="lightbox-caption"></div>
</div>

<!-- Image fallback for broken images -->
<script>
document.addEventListener('error', function(e) {
  if (e.target.tagName === 'IMG' && !e.target.dataset.fallback) {
    e.target.dataset.fallback = '1';
    e.target.style.opacity = '.3';
    e.target.alt = 'صورة غير متوفرة';
  }
}, true);
</script>

<!-- Service Worker registration -->
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').catch(function() {});
}
</script>

<?php if (empty($comingSoon)): ?>
<!-- Bottom Navigation (mobile) -->
<nav class="bottom-nav" aria-label="تنقل سريع">
  <a href="/" class="bottom-nav-item<?= (isset($currentPage) && $currentPage === 'home') ? ' active' : '' ?>">
    <span class="bottom-nav-icon">&#127968;</span>
    <span class="bottom-nav-label">الرئيسية</span>
  </a>
  <a href="/news" class="bottom-nav-item<?= (isset($currentPage) && $currentPage === 'news') ? ' active' : '' ?>">
    <span class="bottom-nav-icon">&#128240;</span>
    <span class="bottom-nav-label">الأخبار</span>
  </a>
  <a href="/tree" class="bottom-nav-item<?= (isset($currentPage) && $currentPage === 'tree') ? ' active' : '' ?>">
    <span class="bottom-nav-icon">&#127795;</span>
    <span class="bottom-nav-label">الشجرة</span>
  </a>
  <a href="/gallery" class="bottom-nav-item<?= (isset($currentPage) && $currentPage === 'gallery') ? ' active' : '' ?>">
    <span class="bottom-nav-icon">&#128247;</span>
    <span class="bottom-nav-label">المعرض</span>
  </a>
  <a href="/council" class="bottom-nav-item<?= (isset($currentPage) && $currentPage === 'council') ? ' active' : '' ?>">
    <span class="bottom-nav-icon">&#127970;</span>
    <span class="bottom-nav-label">المجلس</span>
  </a>
</nav>
<?php endif; ?>
