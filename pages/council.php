<?php
/**
 * council.php — صفحة المجلس
 * تحتوي: الهيئة الإدارية، الفعاليات، اللجان
 */
$ws = getWS();
$meeting = getMeeting();
$upcomingEvents = getUpcomingEvents();
?>

<!-- العداد التنازلي -->
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
<section class="hero" style="padding:50px 24px 40px">
  <div class="hero-content" style="max-width:700px;margin:0 auto">
    <div class="countdown-box" id="countdown-section">
      <div class="countdown-title">&#9200; <?= esc($meeting['title'] ?? 'الجلسة العمومية القادمة') ?></div>
      <div class="countdown-timer">
        <div class="countdown-item"><div class="countdown-num" id="cd-d"><?= esc($cdDays) ?></div><div class="countdown-label">يوم</div></div>
        <div class="countdown-item"><div class="countdown-num" id="cd-h"><?= esc($cdHours) ?></div><div class="countdown-label">ساعة</div></div>
        <div class="countdown-item"><div class="countdown-num" id="cd-m"><?= esc($cdMins) ?></div><div class="countdown-label">دقيقة</div></div>
      </div>
      <div id="cd-date" style="font-size:12px;opacity:.65;margin-top:14px;font-weight:600"></div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- الهيئة الإدارية -->
<section id="council">
  <div class="section-header">
    <div class="section-badge">الهيئة الإدارية</div>
    <h2 class="section-title">إدارة المجلس</h2>
    <p class="section-subtitle">الهيئة الإدارية لمجلس عائلة العوامي</p>
  </div>
  <div class="council-grid" id="council-grid">
    <?php if (!empty($ws['councilPositions'])): ?>
      <?php foreach ($ws['councilPositions'] as $pos): ?>
        <?php
          $cardClass = 'council-card animate-in';
          if (($pos['type'] ?? '') === 'president') $cardClass .= ' president';
          if (($pos['type'] ?? '') === 'advisory')  $cardClass .= ' advisory';
        ?>
        <div class="<?= $cardClass ?>">
          <div class="council-icon"><?= esc($pos['icon'] ?? '👤') ?></div>
          <div class="council-role"><?= esc($pos['role']) ?></div>
          <div class="council-name"><?= esc($pos['name']) ?></div>
          <?php if (!empty($pos['tasks'])): ?>
            <ul class="council-tasks">
              <?php foreach ($pos['tasks'] as $task): ?>
                <li><?= esc($task) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
    <div class="council-card president animate-in">
      <div class="council-icon">&#x1F451;</div>
      <div class="council-role">الرئيس</div>
      <div class="council-name">منصور علي</div>
      <ul class="council-tasks"><li>الإشراف العام على أعمال المجلس</li><li>إدارة الاجتماعات وتمثيل المجلس</li></ul>
    </div>
    <div class="council-card animate-in">
      <div class="council-icon">&#x1F91D;</div>
      <div class="council-role">نائب الرئيس</div>
      <div class="council-name">حسين عبدالحميد - عبدالله عماد</div>
      <ul class="council-tasks"><li>مساندة الرئيس في جميع المهام</li><li>متابعة تنفيذ القرارات</li></ul>
    </div>
    <div class="council-card animate-in">
      <div class="council-icon">&#x1F4B0;</div>
      <div class="council-role">أمين الصندوق</div>
      <div class="council-name">محمود حسن - عبدالله عماد - راضي ابراهيم</div>
      <ul class="council-tasks"><li>إدارة الشؤون المالية</li><li>إعداد التقارير المالية</li></ul>
    </div>
    <div class="council-card animate-in">
      <div class="council-icon">&#x1F4CB;</div>
      <div class="council-role">المنسق العام</div>
      <div class="council-name">راضي ابراهيم - عبدالله عماد - محمود حسن</div>
      <ul class="council-tasks"><li>تنظيم الفعاليات والأنشطة</li><li>التواصل مع الأعضاء</li></ul>
    </div>
    <div class="council-card animate-in">
      <div class="council-icon">&#x1F4DD;</div>
      <div class="council-role">أمين السر</div>
      <div class="council-name">منصور علي - حسين عبدالحميد</div>
      <ul class="council-tasks"><li>تدوين محاضر الاجتماعات</li><li>أرشفة القرارات والمكاتبات</li></ul>
    </div>
    <div class="council-card advisory animate-in">
      <div class="council-icon">&#x1F393;</div>
      <div class="council-role">اللجنة الاستشارية</div>
      <div class="council-name">علي العوامي (أبو حيدر) - فخري العوامي - حسين علي سلمان</div>
      <ul class="council-tasks"><li>تقديم المشورة والتوجيه</li><li>وضع رؤية عامة للمجلس</li></ul>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- الفعاليات القادمة -->
<?php if (!empty($upcomingEvents)): ?>
<section id="events" class="full-section" style="background:var(--bg-alt)">
  <div class="section-header">
    <div class="section-badge">الفعاليات القادمة</div>
    <h2 class="section-title">فعاليات المجلس</h2>
    <p class="section-subtitle">أبرز الفعاليات والأنشطة القادمة</p>
  </div>
  <div class="events-grid">
    <?php foreach ($upcomingEvents as $ev): ?>
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

<!-- اللجان -->
<section id="committees" class="full-section" style="background:var(--bg-alt)">
  <div class="section-header">
    <div class="section-badge">١٠ لجان متخصصة</div>
    <h2 class="section-title">لجان المجلس</h2>
    <p class="section-subtitle">لجان متخصصة لخدمة العائلة في مختلف المجالات</p>
  </div>
  <div class="committees-grid">
    <div class="committee-card animate-in"><div class="committee-banner" style="background:linear-gradient(135deg,#1a6b3c,#2d9955)">&#x1F54B;</div><div class="committee-body"><div class="committee-title">لجنة العمرة الرجبية</div><div class="committee-desc">تنظيم رحلة العمرة السنوية في شهر رجب لأفراد العائلة</div><div class="committee-members">&#x1F465; ٨ أعضاء</div></div></div>
    <div class="committee-card animate-in"><div class="committee-banner" style="background:linear-gradient(135deg,#c8a84b,#e8c96a)">&#x1F356;</div><div class="committee-body"><div class="committee-title">لجنة غداء العيدين</div><div class="committee-desc">تنظيم وإدارة غداء عيد الفطر وعيد الأضحى</div><div class="committee-members">&#x1F465; ٥ أعضاء</div></div></div>
    <div class="committee-card animate-in"><div class="committee-banner" style="background:linear-gradient(135deg,#1a3a6b,#2d5ab9)">&#x1F319;</div><div class="committee-body"><div class="committee-title">لجنة المسابقة الرمضانية</div><div class="committee-desc">إعداد وتحكيم المسابقات الرمضانية للعائلة</div><div class="committee-members">&#x1F465; ٥ أعضاء</div></div></div>
    <div class="committee-card animate-in"><div class="committee-banner" style="background:linear-gradient(135deg,#2980b9,#5dade2)">&#x1F3A1;</div><div class="committee-body"><div class="committee-title">لجنة الرحلات</div><div class="committee-desc">تخطيط وتنفيذ الرحلات الترفيهية للعائلة</div><div class="committee-members">&#x1F465; ١ عضو</div></div></div>
    <div class="committee-card animate-in"><div class="committee-banner" style="background:linear-gradient(135deg,#4a235a,#8e44ad)">&#x2728;</div><div class="committee-body"><div class="committee-title">لجنة ليلة القدر</div><div class="committee-desc">إحياء ليلة القدر وتنظيم فعالياتها الروحانية</div><div class="committee-members">&#x1F465; ٧ أعضاء</div></div></div>
    <div class="committee-card animate-in"><div class="committee-banner" style="background:linear-gradient(135deg,#117a65,#1abc9c)">&#x1F54C;</div><div class="committee-body"><div class="committee-title">لجنة تنظيف المساجد</div><div class="committee-desc">تنسيق حملات تنظيف وصيانة المساجد (العمل التطوعي)</div><div class="committee-members">&#x1F465; ٦ أعضاء</div></div></div>
    <div class="committee-card animate-in"><div class="committee-banner" style="background:linear-gradient(135deg,#b7950b,#d4ac0d)">&#x1F3C6;</div><div class="committee-body"><div class="committee-title">لجنة مسابقة العيد</div><div class="committee-desc">تنظيم مسابقات وفعاليات العيد للأطفال والكبار</div><div class="committee-members">&#x1F465; ٨ أعضاء</div></div></div>
    <div class="committee-card animate-in"><div class="committee-banner" style="background:linear-gradient(135deg,#1B3456,#2d5a85)">&#x1F4C8;</div><div class="committee-body"><div class="committee-title">لجنة الاستثمار</div><div class="committee-desc">إدارة واستثمار أموال الصندوق</div><div class="committee-members">&#x1F465; ٦ أعضاء</div></div></div>
    <div class="committee-card animate-in"><div class="committee-banner" style="background:linear-gradient(135deg,#c0392b,#e74c3c)">&#x1F4E2;<div class="committee-badge">جديد</div></div><div class="committee-body"><div class="committee-title">اللجنة الإعلامية</div><div class="committee-desc">إدارة المنصات الإعلامية وتوثيق الفعاليات</div><div class="committee-members">&#x1F465; ٤ أعضاء</div></div></div>
    <div class="committee-card animate-in"><div class="committee-banner" style="background:linear-gradient(135deg,#6b3a1a,#9b5a2d)">&#x1F411;<div class="committee-badge">جديد</div></div><div class="committee-body"><div class="committee-title">لجنة العقيقة الجماعية</div><div class="committee-desc">تنظيم مناسبات العقيقة الجماعية للعائلة</div><div class="committee-members">&#x1F465; ٤ أعضاء</div></div></div>
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
