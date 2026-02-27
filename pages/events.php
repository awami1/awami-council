<?php
/**
 * events.php — صفحة الفعاليات والأنشطة
 */
$ws = getWS();
$allEvents = getAllEvents(50);
$statusLabels = [
    'قادم'  => ['label' => 'قادم',  'color' => 'var(--green)',  'bg' => 'var(--green-light)'],
    'جاري'  => ['label' => 'جاري',  'color' => '#2980b9',      'bg' => '#ebf5fb'],
    'مكتمل' => ['label' => 'مكتمل', 'color' => 'var(--text-muted)', 'bg' => 'var(--bg-alt)'],
    'ملغي'  => ['label' => 'ملغي',  'color' => '#c0392b',      'bg' => '#fce4ec'],
];
?>

<section>
  <div class="section-header">
    <div class="section-badge">جميع الفعاليات</div>
    <h2 class="section-title">فعاليات وأنشطة المجلس</h2>
    <p class="section-subtitle">استعرض جميع الفعاليات والأنشطة المُنظّمة من مجلس عائلة العوامي</p>
  </div>

  <!-- فلاتر الحالة -->
  <div class="media-tabs" id="events-filter">
    <button class="media-tab active events-tab" data-status="">الكل</button>
    <button class="media-tab events-tab" data-status="قادم">&#128197; القادمة</button>
    <button class="media-tab events-tab" data-status="جاري">&#9654;&#65039; الجارية</button>
    <button class="media-tab events-tab" data-status="مكتمل">&#9989; المكتملة</button>
  </div>

  <div id="events-container" style="max-width:1200px;margin:0 auto">
    <?php if (!empty($allEvents)): ?>
    <div class="events-full-grid">
      <?php foreach ($allEvents as $ev): ?>
        <?php $st = $statusLabels[$ev['status']] ?? $statusLabels['قادم']; ?>
        <div class="event-full-card animate-in" data-status="<?= esc($ev['status']) ?>">
          <div class="event-full-icon"><?= esc($ev['icon'] ?? '&#128197;') ?></div>
          <div class="event-full-body">
            <div class="event-full-header">
              <h3 class="event-full-name"><?= esc($ev['name']) ?></h3>
              <span class="event-full-status" style="color:<?= $st['color'] ?>;background:<?= $st['bg'] ?>"><?= esc($st['label']) ?></span>
            </div>
            <div class="event-full-meta">
              <?php if (!empty($ev['event_date'])): ?>
                <span>&#128197; <?= esc(date('d/m/Y', strtotime($ev['event_date']))) ?></span>
              <?php endif; ?>
              <?php if (!empty($ev['lead'])): ?>
                <span>&#128100; <?= esc($ev['lead']) ?></span>
              <?php endif; ?>
              <?php if (!empty($ev['participants'])): ?>
                <span>&#128101; <?= (int)$ev['participants'] ?> مشارك</span>
              <?php endif; ?>
            </div>
            <?php if (!empty($ev['notes'])): ?>
              <p class="event-full-notes"><?= esc($ev['notes']) ?></p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
      <div style="font-size:52px;margin-bottom:12px;opacity:.4">&#128197;</div>
      <p>لا توجد فعاليات حالياً</p>
      <p style="font-size:13px;margin-top:8px">ترقبوا قريباً فعاليات وأنشطة المجلس</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<script>
(function() {
  var tabs = document.querySelectorAll('.events-tab');
  var cards = document.querySelectorAll('.event-full-card');
  tabs.forEach(function(tab) {
    tab.addEventListener('click', function() {
      tabs.forEach(function(t) { t.classList.remove('active'); });
      tab.classList.add('active');
      var status = tab.dataset.status;
      cards.forEach(function(card) {
        if (!status || card.dataset.status === status) {
          card.style.display = '';
          card.classList.remove('hiding');
        } else {
          card.classList.add('hiding');
          setTimeout(function() { if (card.classList.contains('hiding')) card.style.display = 'none'; }, 250);
        }
      });
    });
  });
})();
</script>
