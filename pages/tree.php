<?php
/**
 * tree.php — شجرة العائلة
 */
$ws = getWS();
$branches = getBranches();
?>

<section id="tree">
  <div class="section-header">
    <div class="section-badge">الأفرع الرئيسية</div>
    <h2 class="section-title">شجرة العائلة</h2>
    <p class="section-subtitle">الأفرع الرئيسية لعائلة العوامي</p>
  </div>
  <div class="tree-root">
    <div class="tree-root-box">
      <h3>عائلة العوامي</h3>
      <p>AL AWAMI &bull; ١٩٩٢</p>
    </div>
  </div>
  <div class="tree-grid" id="tree-grid">
    <?php if (empty($branches)): ?>
      <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted)">
        <div style="font-size:52px;margin-bottom:12px;opacity:.4">&#x1F333;</div>
        <p>لم تُضف أفرع عائلية بعد</p>
      </div>
    <?php else: ?>
      <?php foreach ($branches as $b): ?>
        <?php
          $bMembers = $b['members'] ?? null;
          if (is_string($bMembers)) $bMembers = json_decode($bMembers, true) ?: [];
          $bCount = is_array($bMembers) ? count($bMembers) : 0;
          if ($bCount === 0) $bCount = (int)($b['count'] ?? 0);
          $bCol = esc($b['color'] ?? 'var(--green)');
        ?>
        <div class="tree-branch animate-in" style="border-color:<?= $bCol ?>">
          <div style="font-size:28px;margin-bottom:10px">&#x1F33F;</div>
          <div class="tree-branch-name"><?= esc($b['name']) ?></div>
          <?php if (!empty($b['head'])): ?>
            <div class="tree-branch-head"><?= esc($b['head']) ?></div>
          <?php endif; ?>
          <div class="tree-branch-stat">
            <div class="tree-branch-num" style="color:<?= $bCol ?>"><?= $bCount ?></div>
            <div class="tree-branch-label">فرد</div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
