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
<script src="/public/js/api.js"></script>
<script src="/public/js/theme.js"></script>
<script src="/public/js/navbar.js"></script>
<script src="/public/js/animations.js"></script>
<?php foreach ($pageScripts ?? [] as $script): ?>
  <script src="<?= $script ?>"></script>
<?php endforeach; ?>
<script src="/public/js/ajax-nav.js"></script>
</body>
</html>
