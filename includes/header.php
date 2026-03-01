<?php $ws = getWS(); ?>
<a href="#main-content" class="skip-link">تخطَّ إلى المحتوى الرئيسي</a>

<header>
  <div class="header-content">
    <a href="/" class="logo-section" style="text-decoration:none">
      <div class="logo-img">
        <?php if (!empty($ws['logo'])): ?>
          <img src="<?= esc($ws['logo']) ?>" class="logo-svg" style="width:40px;height:40px;border-radius:8px" alt="شعار عائلة العوامي">
        <?php else: ?>
          <svg class="logo-svg" viewBox="0 0 80 80" fill="none">
            <path d="M55 12 C58 8,65 10,64 18 C63 26,54 30,50 38 C46 46,48 56,42 62 C36 68,26 66,24 58 C22 50,30 44,32 36" stroke="#3D8B37" stroke-width="6" stroke-linecap="round" fill="none"/>
            <path d="M32 36 C28 44,20 46,20 54 C20 62,28 66,34 62" stroke="#3D8B37" stroke-width="5" stroke-linecap="round" fill="none"/>
            <circle cx="34" cy="62" r="5" fill="#3D8B37"/>
          </svg>
        <?php endif; ?>
      </div>
      <div class="logo-text">
        <h1><?= esc($ws['header']['title']) ?></h1>
        <p><?= esc($ws['header']['subtitle']) ?></p>
      </div>
    </a>
    <div style="display:flex;align-items:center;gap:4px">
      <button class="theme-toggle" id="themeToggle" aria-label="تبديل الوضع الداكن/الفاتح" title="تبديل الوضع">&#9790;</button>
      <button class="menu-toggle" id="menuBtn" aria-label="القائمة" aria-expanded="false" aria-controls="mainNav">&#9776;</button>
    </div>
    <nav id="mainNav">
      <a href="/"<?= ($currentPage ?? '') === 'home' ? ' class="active"' : '' ?>>الرئيسية</a>
      <a href="/council"<?= ($currentPage ?? '') === 'council' ? ' class="active"' : '' ?>>المجلس</a>
      <a href="/tree"<?= ($currentPage ?? '') === 'tree' ? ' class="active"' : '' ?>>شجرة العائلة</a>
      <a href="/news"<?= ($currentPage ?? '') === 'news' ? ' class="active"' : '' ?>>الأخبار</a>
      <a href="/riwaq" data-no-ajax<?= ($currentPage ?? '') === 'riwaq' ? ' class="active"' : '' ?>>الرِّوَاق</a>
      <a href="/events"<?= ($currentPage ?? '') === 'events' ? ' class="active"' : '' ?>>الفعاليات</a>
      <a href="/gallery"<?= ($currentPage ?? '') === 'gallery' ? ' class="active"' : '' ?>>المعرض</a>
      <a href="/contact"<?= ($currentPage ?? '') === 'contact' ? ' class="active"' : '' ?>>تواصل معنا</a>
    </nav>
  </div>
</header>
