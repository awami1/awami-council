<?php
/**
 * home.php — الصفحة الرئيسية
 */
$ws = getWS();
$meeting = getMeeting();
$upcomingEvents = getUpcomingEvents();
?>

<!-- HERO -->
<section class="hero">
  <div class="hero-content">
    <h2><?= esc($ws['hero']['title']) ?></h2>
    <p><?= esc($ws['hero']['description']) ?></p>
    <div class="hero-cta">
      <a href="/council" class="cta-btn cta-primary">تعرف على المجلس</a>
      <?php if (!empty($ws['contact']['whatsapp'])): ?>
        <a href="https://wa.me/<?= esc(preg_replace('/\D/', '', $ws['contact']['whatsapp'])) ?>" target="_blank" rel="noopener" class="cta-btn cta-secondary">&#128241; تواصل معنا</a>
      <?php endif; ?>
    </div>
    <div class="hero-meta">
      <div>
        <div class="num" data-count="<?= (int)$ws['stats']['years'] ?>"><?= esc((string)$ws['stats']['years']) ?></div>
        <div class="lbl">عاماً من العطاء</div>
      </div>
      <div>
        <div class="num" data-count="<?= (int)$ws['stats']['committees'] ?>"><?= esc((string)$ws['stats']['committees']) ?></div>
        <div class="lbl">لجنة متخصصة</div>
      </div>
      <div>
        <div class="num" data-count="<?= (int)preg_replace('/\D/', '', $ws['stats']['members']) ?>" data-prefix="+"><?= esc((string)$ws['stats']['members']) ?></div>
        <div class="lbl">عضو نشط</div>
      </div>
    </div>

    <?php if ($meeting): ?>
    <?php
      $cdDays = '--'; $cdHours = '--'; $cdMins = '--';
      if ($meeting['date']) {
        $diff    = max(0, strtotime($meeting['date']) - time());
        $cdDays  = (string)floor($diff / 86400);
        $cdHours = (string)floor(($diff % 86400) / 3600);
        $cdMins  = (string)floor(($diff % 3600) / 60);
      }
    ?>
    <div class="countdown-box" id="countdown-section">
      <div class="countdown-title">&#9200; <?= esc($meeting['title'] ?? 'الجلسة العمومية القادمة') ?></div>
      <div class="countdown-timer">
        <div class="countdown-item"><div class="countdown-num" id="cd-d"><?= esc($cdDays) ?></div><div class="countdown-label">يوم</div></div>
        <div class="countdown-item"><div class="countdown-num" id="cd-h"><?= esc($cdHours) ?></div><div class="countdown-label">ساعة</div></div>
        <div class="countdown-item"><div class="countdown-num" id="cd-m"><?= esc($cdMins) ?></div><div class="countdown-label">دقيقة</div></div>
      </div>
      <div id="cd-date" style="font-size:12px;opacity:.65;margin-top:14px;font-weight:600"></div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- أقسام الموقع -->
<section>
  <div class="section-header">
    <div class="section-badge">استكشف</div>
    <h2 class="section-title">أقسام الموقع</h2>
    <p class="section-subtitle">تعرّف على مجلس عائلة العوامي من خلال أقسامنا المتنوعة</p>
  </div>
  <div class="section-links-grid">
    <a href="/council" class="section-link-card animate-in">
      <div class="section-link-icon">&#127970;</div>
      <div class="section-link-title">المجلس</div>
      <div class="section-link-desc">الهيئة الإدارية واللجان والفعاليات</div>
    </a>
    <a href="/tree" class="section-link-card animate-in">
      <div class="section-link-icon">&#127795;</div>
      <div class="section-link-title">شجرة العائلة</div>
      <div class="section-link-desc">الأفرع الرئيسية لعائلة العوامي</div>
    </a>
    <a href="/news" class="section-link-card animate-in">
      <div class="section-link-icon">&#128240;</div>
      <div class="section-link-title">الأخبار</div>
      <div class="section-link-desc">آخر أخبار وفعاليات العائلة</div>
    </a>
    <a href="/gallery" class="section-link-card animate-in">
      <div class="section-link-icon">&#128247;</div>
      <div class="section-link-title">المعرض</div>
      <div class="section-link-desc">صور وفيديوهات من فعاليات المجلس</div>
    </a>
    <a href="/eid" class="section-link-card animate-in">
      <div class="section-link-icon">&#127769;</div>
      <div class="section-link-title">تهنئة العيد</div>
      <div class="section-link-desc">اصنع بطاقة تهنئة شخصية بإسمك</div>
    </a>
    <a href="/contact" class="section-link-card animate-in">
      <div class="section-link-icon">&#128172;</div>
      <div class="section-link-title">تواصل معنا</div>
      <div class="section-link-desc">نسعد بتواصلكم واقتراحاتكم</div>
    </a>
  </div>
</section>

<?php if (!empty($upcomingEvents)): ?>
<!-- الفعاليات القادمة -->
<section class="full-section" style="background:var(--bg-alt)">
  <div class="section-header">
    <div class="section-badge">الفعاليات القادمة</div>
    <h2 class="section-title">فعاليات المجلس</h2>
    <p class="section-subtitle">أبرز الفعاليات والأنشطة القادمة</p>
  </div>
  <div class="events-grid">
    <?php foreach (array_slice($upcomingEvents, 0, 3) as $ev): ?>
      <div class="event-card animate-in">
        <div class="event-icon"><?= esc($ev['icon'] ?? '📅') ?></div>
        <div class="event-info">
          <div class="event-name"><?= esc($ev['name']) ?></div>
          <?php if (!empty($ev['event_date'])): ?>
            <div class="event-date">&#128197; <?= esc(date('d/m/Y', strtotime($ev['event_date']))) ?></div>
          <?php endif; ?>
          <?php if (!empty($ev['lead'])): ?>
            <div class="event-lead">&#128100; <?= esc($ev['lead']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- عن المجلس -->
<section>
  <div class="section-header">
    <div class="section-badge">رسالتنا ورؤيتنا</div>
    <h2 class="section-title">عن مجلس عائلة العوامي</h2>
    <p class="section-subtitle">تعرف على رسالة ورؤية المجلس</p>
  </div>
  <div class="about-grid">
    <div class="about-content animate-in">
      <?php if (!empty($ws['about']['mission'])): ?>
        <h3>رسالتنا</h3><p><?= esc($ws['about']['mission']) ?></p>
      <?php endif; ?>
      <?php if (!empty($ws['about']['vision'])): ?>
        <h3>رؤيتنا</h3><p><?= esc($ws['about']['vision']) ?></p>
      <?php endif; ?>
      <?php if (empty($ws['about']['mission']) && empty($ws['about']['vision'])): ?>
        <h3>رسالتنا</h3>
        <p>تعزيز الترابط الأسري والتواصل بين أفراد عائلة العوامي من خلال تنظيم الأنشطة والفعاليات الدينية والاجتماعية والترفيهية التي تحقق المصلحة العامة وتُرسّخ القيم الأصيلة.</p>
        <h3>رؤيتنا</h3>
        <p>أن نكون مجلساً عائلياً نموذجياً يُحتذى به في التنظيم والتطوير والخدمة، ونسعى لبناء جيل واعٍ ومتماسك يفخر بانتمائه لعائلة العوامي.</p>
      <?php endif; ?>
    </div>
    <div class="about-visual animate-in">
      <svg viewBox="0 0 200 200" fill="none">
        <circle cx="100" cy="100" r="80" stroke="#fff" stroke-width="2.5" opacity=".2"/>
        <circle cx="100" cy="100" r="60" stroke="#fff" stroke-width="2.5" opacity=".35"/>
        <circle cx="100" cy="100" r="40" fill="#fff" opacity=".85"/>
        <text x="100" y="108" font-family="Amiri" font-size="22" fill="#1A5C32" text-anchor="middle" font-weight="700">العوامي</text>
        <text x="100" y="130" font-family="Cairo" font-size="11" fill="#1A5C32" text-anchor="middle" opacity=".6">١٩٩٢ - ٢٠٢٦</text>
      </svg>
    </div>
  </div>
</section>

<!-- القيم -->
<section class="full-section" style="background:var(--bg-alt)">
  <div class="section-header">
    <div class="section-badge">مبادئنا</div>
    <h2 class="section-title">قيمنا</h2>
    <p class="section-subtitle">المبادئ التي نعمل بها</p>
  </div>
  <div class="values-grid" style="max-width:1200px;margin:0 auto">
    <?php if (!empty($ws['values'])): ?>
      <?php foreach ($ws['values'] as $val): ?>
        <div class="value-card animate-in">
          <div class="value-icon"><?= esc($val['icon'] ?? '') ?></div>
          <div class="value-title"><?= esc($val['title'] ?? '') ?></div>
          <div class="value-desc"><?= esc($val['desc'] ?? '') ?></div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="value-card animate-in"><div class="value-icon">&#x1F91D;</div><div class="value-title">الترابط الأسري</div><div class="value-desc">نؤمن بأهمية التواصل والتآزر بين أفراد العائلة</div></div>
      <div class="value-card animate-in"><div class="value-icon">&#x2696;&#xFE0F;</div><div class="value-title">الشفافية والنزاهة</div><div class="value-desc">نلتزم بالشفافية في جميع أعمالنا المالية والإدارية</div></div>
      <div class="value-card animate-in"><div class="value-icon">&#x1F31F;</div><div class="value-title">التطوير المستمر</div><div class="value-desc">نسعى دائماً لتحسين خدماتنا وتطوير أنشطتنا</div></div>
    <?php endif; ?>
  </div>
</section>

<script>
(function() {
  <?php if ($meeting): ?>
  if (typeof initCountdown === 'function') {
    initCountdown(<?= json_encode($meeting) ?>);
  }
  <?php endif; ?>
})();
</script>
