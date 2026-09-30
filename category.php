<?php
require_once __DIR__ . '/includes/functions.php';

$type     = ($_GET['type'] ?? '') === 'series' ? 'series' : (($_GET['type'] ?? '') === 'movie' ? 'movie' : '');
$genre    = trim((string)($_GET['genre'] ?? ''));
$catSlug  = trim((string)($_GET['cat'] ?? ''));
$catRow   = $catSlug !== '' ? categoryBySlug($catSlug) : null;
if (strcasecmp($genre, 'all') === 0) { $genre = ''; }
if ($catSlug !== '' && !$catRow) {
    http_response_code(404);
    $pageTitle = '404 — Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="empty"><div class="big">😕</div><h2>ক্যাটাগরি পাওয়া যায়নি!</h2><p style="margin-top:8px"><a href="' . BASE_URL . '/category.php">← সব ক্যাটাগরি দেখুন</a></p></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$perPage = 24;
if ($catRow) {
    $total  = countItemsByCat((string)$catRow['slug'], $type);
} else {
    $total  = countItems($type, $genre);
}
$pages   = max(1, (int)ceil($total / $perPage));
$page    = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
$offset  = ($page - 1) * $perPage;

if ($catRow) {
    $items = itemsByCat((string)$catRow['slug'], $type, $perPage, $offset);
} elseif ($genre !== '') {
    $items = itemsByGenre($genre, $type, $perPage, $offset);
} elseif ($type !== '') {
    $items = latestItems($type, $perPage, $offset);
} else {
    $items = DB::rows('SELECT ' . MV_COLS . ' FROM movies WHERE status = ? ORDER BY pinned DESC, created_at DESC, id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset, ['published']);
}

$heading = $catRow ? ('📂 ' . $catRow['name']) : ($genre !== '' ? ('🎭 Genre: ' . $genre) : ($type === 'series' ? '📺 All Web Series' : ($type === 'movie' ? '🎬 All Movies' : '🎬 All Movies & Series')));
$pageTitle = $heading . ' | ' . getSetting('site_name', 'MovieVerse');
require __DIR__ . '/includes/header.php';
?>
<div class="container">
  <?php renderAd('header_banner', 'Sponsored'); ?>
  <div class="page-bar">
    <h1><?= esc($heading) ?> <small>(<?= $total ?> items)</small></h1>
    <?php if ($catRow): ?><span class="chip" style="border-color:<?= esc(categoryColor((string)$catRow['slug'])) ?>;color:#fff;background:<?= esc(categoryColor((string)$catRow['slug'])) ?>22">● Category</span><?php endif; ?>
  </div>

  <?php if ($items): ?>
  <div class="grid">
    <?php foreach ($items as $m) { include __DIR__ . '/includes/card.php'; } ?>
  </div>
  <?php renderPagination($page, $pages); ?>
  <?php else: ?>
  <div class="empty"><div class="big">🎬</div>কোনো কনটেন্ট পাওয়া যায়নি।</div>
  <?php endif; ?>

  <?php renderAd('footer_banner', 'Sponsored'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
