<?php
require_once __DIR__ . '/includes/functions.php';

$q      = trim((string)($_GET['q'] ?? ''));
$perPage = 24;
$total  = $q !== '' ? countSearchItems($q) : 0;
$pages  = max(1, (int)ceil($total / $perPage));
$page   = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
$offset = ($page - 1) * $perPage;
$items  = $q !== '' ? searchItems($q, $perPage, $offset) : [];

$pageTitle = ($q !== '' ? 'Search: ' . $q : 'Search') . ' | ' . getSetting('site_name', 'MovieVerse');
require __DIR__ . '/includes/header.php';
?>
<div class="container">
  <?php renderAd('header_banner', 'Sponsored'); ?>
  <div class="page-bar">
    <h1>🔍 <?= $q !== '' ? 'Search: ' . esc($q) : 'Search' ?> <small>(<?= $total ?> results)</small></h1>
  </div>

  <?php if ($q === ''): ?>
    <div class="empty"><div class="big">🔍</div>উপরের সার্চ বক্সে মুভি বা সিরিজের নাম লিখুন।</div>
  <?php elseif ($items): ?>
    <div class="grid">
      <?php foreach ($items as $m) { include __DIR__ . '/includes/card.php'; } ?>
    </div>
    <?php renderPagination($page, $pages); ?>
  <?php else: ?>
    <div class="empty"><div class="big">😕</div>"<?= esc($q) ?>" এর জন্য কোনো ফলাফল পাওয়া যায়নি।</div>
  <?php endif; ?>

  <?php renderAd('footer_banner', 'Sponsored'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
