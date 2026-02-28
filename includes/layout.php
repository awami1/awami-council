<?php require_once __DIR__ . '/helpers.php'; ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<?php include __DIR__ . '/head.php'; ?>
<body>
<?php include __DIR__ . '/header.php'; ?>
<main id="main-content">
  <div id="page-content">
    <?php include $pageFile; ?>
  </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
<?php $__av = 0; foreach (glob(__DIR__.'/../public/js/*.js') as $__f) $__av = max($__av, filemtime($__f)); ?>
<script>window.__ASSET_V__="<?= $__av ?>";</script>
<script src="<?= asset('/public/js/api.js') ?>"></script>
<script src="<?= asset('/public/js/theme.js') ?>"></script>
<script src="<?= asset('/public/js/navbar.js') ?>"></script>
<script src="<?= asset('/public/js/animations.js') ?>"></script>
<?php foreach ($pageScripts ?? [] as $script): ?>
  <script src="<?= asset($script) ?>"></script>
<?php endforeach; ?>
<script src="<?= asset('/public/js/ajax-nav.js') ?>"></script>
</body>
</html>
