<?php
require_once __DIR__ . '/includes/functions.php';

$slug  = trim((string)($_GET['slug'] ?? ''));
$movie = $slug !== '' ? movieBySlug($slug) : null;

if (!$movie || $movie['status'] !== 'published') {
    http_response_code(404);
    $pageTitle = '404 — Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="empty"><div class="big">😕</div><h2>পেজটি পাওয়া যায়নি!</h2><p style="margin-top:8px"><a href="' . BASE_URL . '/">← হোমপেজে ফিরে যান</a></p></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

incrementViews('movies', (int)$movie['id']);
$siteName = getSetting('site_name', 'MovieVerse');
$pageTitle = trim(($movie['seo_title'] ?: $movie['title'] . ' (' . $movie['year'] . ') ' . $movie['quality'] . ' — Watch & Download')) . ' | ' . $siteName;
$pageDesc  = $movie['seo_description'] ?: excerpt($movie['description'], 250);
$ogImage   = mediaUrl($movie['poster']);
require __DIR__ . '/includes/header.php';

$genresArr = array_filter(array_map('trim', explode(',', (string)$movie['genres'])));
$servers   = decodeServers($movie['servers']);
$related   = relatedItems($movie, 6);
$detailCats = movieCats($movie);
?>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"<?= $movie['type'] === 'series' ? 'TVSeries' : 'Movie' ?>","name":"<?= esc($movie['title']) ?>","image":"<?= esc(mediaUrl($movie['poster'])) ?>","description":"<?= esc(excerpt($movie['description'], 300)) ?>"<?php if ($movie['year']): ?>,"dateCreated":"<?= esc($movie['year']) ?>"<?php endif; ?>}
</script>

<div class="container">
  <nav class="crumbs">
    <a href="<?= BASE_URL ?>/">🏠 Home</a><span class="sep">›</span>
    <a href="<?= BASE_URL ?>/category.php?type=<?= esc($movie['type']) ?>"><?= $movie['type'] === 'series' ? '📺 Web Series' : '🎬 Movies' ?></a><span class="sep">›</span>
    <span class="cur"><?= esc($movie['title']) ?></span>
  </nav>
</div>

<div class="detail-hero">
  <div class="detail-backdrop">
    <img src="<?= esc(mediaUrl($movie['backdrop'] ?: $movie['poster'])) ?>" alt="" onerror="this.style.display='none'">
  </div>
  <div class="container detail-inner">
    <div class="detail-poster">
      <img src="<?= esc(mediaUrl($movie['poster'])) ?>" alt="<?= esc($movie['title']) ?>"
           onerror="this.onerror=null;this.src='<?= ASSETS_URL ?>/no-poster.svg'">
    </div>
    <div class="detail-info">
      <h1><?= esc($movie['title']) ?></h1>
      <div class="detail-tagline">👁 <?= formatViews($movie['views']) ?> views · <?= esc(timeAgo($movie['created_at'])) ?></div>
      <div class="detail-meta">
        <?php if ((float)$movie['rating'] > 0): ?><span class="chip">⭐ <b><?= esc($movie['rating']) ?></b>/10</span><?php endif; ?>
        <?php if ($movie['year']): ?><span class="chip">📅 <?= esc($movie['year']) ?></span><?php endif; ?>
        <?php if ($movie['quality']): ?><span class="chip">🎞 <?= esc($movie['quality']) ?></span><?php endif; ?>
        <?php if ($movie['language']): ?><span class="chip">🌐 <?= esc($movie['language']) ?></span><?php endif; ?>
        <?php if ($movie['duration']): ?><span class="chip">⏱ <?= esc($movie['duration']) ?></span><?php endif; ?>
        <span class="chip"><?= $movie['type'] === 'series' ? '📺 Web Series' : '🎬 Movie' ?></span>
        <?php if (!empty($movie['pinned'])): ?><span class="chip chip-pin">📌 Pinned</span><?php endif; ?>
      </div>
      <?php if ($detailCats): ?>
      <div class="detail-cats">
        <?php foreach ($detailCats as $dc): ?>
        <a class="cat-badge" style="--cat:<?= esc($dc['color']) ?>" href="<?= esc(categoryPageUrl($dc['slug'])) ?>"><?= esc($dc['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <p class="detail-lead"><?= esc(excerpt($movie['description'] ?: 'এই মুভির বিস্তারিত তথ্য এখনো যোগ করা হয়নি।', 260)) ?></p>
      <div class="detail-actions">
        <a class="btn btn-primary btn-lg" href="<?= esc(watchMovieUrl((int)$movie['id'])) ?>" data-gate data-target="<?= esc(watchMovieUrl((int)$movie['id'])) ?>">▶ Watch Now</a>
        <a class="btn btn-green btn-lg" href="<?= esc(downloadMovieUrl((int)$movie['id'])) ?>" data-gate data-target="<?= esc(downloadMovieUrl((int)$movie['id'])) ?>">⬇ Download</a>
      </div>
      <?php if ($genresArr): ?>
      <div class="genre-links">
        <?php foreach ($genresArr as $g): ?><a href="<?= esc(categoryUrl($g)) ?>"><?= esc($g) ?></a><?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="container">
  <?php renderAd('incontent_banner', 'Sponsored'); ?>

  <section class="section">
    <div class="section-head"><h2>📖 Storyline</h2></div>
    <p class="detail-desc"><?= nl2br(esc($movie['description'] ?: 'Description এখনো যোগ করা হয়নি।')) ?></p>
  </section>

  <?php if ($servers): ?>
  <section class="section">
    <div class="section-head"><h2>🔗 Download Links</h2></div>
    <div class="ep-list">
      <?php foreach ($servers as $i => $s): ?>
      <div class="ep-item">
        <div class="ep-num"><?= $i + 1 ?></div>
        <div class="ep-info">
          <h4><?= esc($s['name']) ?></h4>
          <p><?= $s['type'] === 'mp4' ? 'Direct Video Link' : 'Google Drive Link' ?></p>
        </div>
        <div class="ep-actions">
          <?php if ($s['type'] === 'drive' && driveFileId($s['url']) !== '' && driveEmbedUrl($s['url']) !== ''): ?>
          <a class="btn btn-outline btn-sm" href="#" data-gate data-target="<?= esc(watchMovieUrl((int)$movie['id']) . '&server=' . $i) ?>">▶ Play</a>
          <?php endif; ?>
          <a class="btn btn-green btn-sm" href="#" data-gate data-target="<?= esc(driveDownloadUrl($s['url'])) ?>" data-newtab="1">⬇ Download</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php renderAd('mid_banner', 'Sponsored'); ?>

  <?php if ($related): ?>
  <section class="section">
    <div class="section-head"><h2>🔗 You May Also Like</h2></div>
    <div class="grid">
      <?php foreach ($related as $m) { include __DIR__ . '/includes/card.php'; } ?>
    </div>
  </section>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
