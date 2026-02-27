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
    <nav class="footer-links" aria-label="روابط سريعة">
      <a href="/">الرئيسية</a>
      <a href="/council">المجلس</a>
      <a href="/tree">شجرة العائلة</a>
      <a href="/news">الأخبار</a>
      <a href="/gallery">المعرض</a>
      <a href="/contact">تواصل معنا</a>
    </nav>
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

<button class="scroll-top" id="scrollTop" aria-label="العودة للأعلى">&#8593;</button>

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightbox" role="dialog" aria-modal="true" aria-label="معاينة الصورة">
  <button class="lightbox-close" onclick="closeLightbox()" aria-label="إغلاق">&#10005;</button>
  <button class="lightbox-nav lightbox-prev" onclick="lightboxNav(-1)" aria-label="السابق">&#8250;</button>
  <img class="lightbox-img" id="lightbox-img" src="" alt="">
  <button class="lightbox-nav lightbox-next" onclick="lightboxNav(1)" aria-label="التالي">&#8249;</button>
  <div class="lightbox-caption" id="lightbox-caption"></div>
</div>
