<?php
/**
 * council.php — صفحة المجلس
 * تحتوي: الهيئة الإدارية، الفعاليات، اللجان (ديناميكية من قاعدة البيانات)
 */
$ws = getWS();
$meeting = getMeeting();
$upcomingEvents = getUpcomingEvents();
$committees = getCommittees();
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
          <div class="council-icon"><?= esc($pos['icon'] ?? '&#128100;') ?></div>
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
        <div class="event-icon"><?= esc($ev['icon'] ?? '&#128197;') ?></div>
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
<?php
  $committeeCount = count($committees);
  $committeeBadge = $committeeCount > 0 ? $committeeCount . ' لجنة متخصصة' : 'لجان المجلس';
?>
<section id="committees" class="full-section"<?= empty($upcomingEvents) ? ' style="background:var(--bg-alt)"' : '' ?>>
  <div class="section-header">
    <div class="section-badge"><?= esc($committeeBadge) ?></div>
    <h2 class="section-title">لجان المجلس</h2>
    <p class="section-subtitle">لجان متخصصة لخدمة العائلة في مختلف المجالات</p>
  </div>
  <div class="committees-grid">
    <?php if (!empty($committees)): ?>
      <?php foreach ($committees as $c): ?>
        <?php $mc = (int)$c['member_count']; ?>
        <div class="committee-card animate-in">
          <div class="committee-banner" style="background:<?= esc($c['color'] ?? 'linear-gradient(135deg,#47915C,#2d6b40)') ?>">
            <?= esc($c['icon'] ?? '&#127970;') ?>
            <?php if ($mc > 0): ?>
              <span class="committee-badge">&#x1F465; <?= $mc ?></span>
            <?php endif; ?>
          </div>
          <div class="committee-body">
            <div class="committee-title"><?= esc($c['name']) ?></div>
            <?php if (!empty($c['description'])): ?>
              <div class="committee-desc"><?= esc($c['description']) ?></div>
            <?php endif; ?>
            <?php if ($mc > 0): ?>
              <div class="committee-members"><?= $mc ?> <?= $mc === 1 ? 'عضو' : 'أعضاء' ?></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted)">
        <div style="font-size:52px;margin-bottom:12px;opacity:.4">&#127970;</div>
        <p>لم تُضف لجان بعد</p>
        <p style="font-size:12px;margin-top:8px">يمكن إضافة اللجان من لوحة التحكم</p>
      </div>
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
