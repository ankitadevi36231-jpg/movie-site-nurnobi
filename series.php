<?php
require_once __DIR__ . '/includes/functions.php';

$slug   = trim((string)($_GET['slug'] ?? ''));
$series = $slug !== '' ? movieBySlug($slug) : null;

if (!$series || $series['status'] !== 'published' || $series['type'] !== 'series') {
    http_response_code(404);
    $pageTitle = '404 — Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="empty"><div class="big">😕</div><h2>সিরিজটি পাওয়া যায়নি!</h2><p style="margin-top:8px"><a href="' . BASE_URL . '/">← হোমপেজে ফিরে যান</a></p></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

incrementViews('movies', (int)$series['id']);
$siteName  = getSetting('site_name', 'MovieVerse');
$pageTitle = trim(($series['seo_title'] ?: $series['title'] . ' — All Episodes Watch & Download')) . ' | ' . $siteName;
$pageDesc  = $series['seo_description'] ?: excerpt($series['description'], 250);
$ogImage   = mediaUrl($series['poster']);
require __DIR__ . '/includes/header.php';

$genresArr  = array_filter(array_map('trim', explode(',', (string)$series['genres'])));
$episodes   = episodesOfSeries((int)$series['id']);
$detailCats = movieCats($series);

/* সিজন অনুযায়ী ভাগ করা */
$bySeason = [];
foreach ($episodes as $ep) { $bySeason[(int)$ep['season']][] = $ep; }
ksort($bySeason);
$firstEp = $episodes[0] ?? null;
?>
<div class="container">
  <nav class="crumbs">
    <a href="<?= BASE_URL ?>/">🏠 Home</a><span class="sep">›</span>
    <a href="<?= BASE_URL ?>/category.php?type=series">📺 Web Series</a><span class="sep">›</span>
    <span class="cur"><?= esc($series['title']) ?></span>
  </nav>
</div>

<div class="detail-hero">
  <div class="detail-backdrop">
    <img src="<?= esc(mediaUrl($series['backdrop'] ?: $series['poster'])) ?>" alt="" onerror="this.style.display='none'">
  </div>
  <div class="container detail-inner">
    <div class="detail-poster">
      <img src="<?= esc(mediaUrl($series['poster'])) ?>" alt="<?= esc($series['title']) ?>"
           onerror="this.onerror=null;this.src='<?= ASSETS_URL ?>/no-poster.svg'">
    </div>
    <div class="detail-info">
      <h1><?= esc($series['title']) ?></h1>
      <div class="detail-tagline">👁 <?= formatViews($series['views']) ?> views · <?= count($episodes) ?> Episodes · <?= esc(timeAgo($series['created_at'])) ?></div>
      <div class="detail-meta">
        <?php if ((float)$series['rating'] > 0): ?><span class="chip">⭐ <b><?= esc($series['rating']) ?></b>/10</span><?php endif; ?>
        <?php if ($series['year']): ?><span class="chip">📅 <?= esc($series['year']) ?></span><?php endif; ?>
        <?php if ($series['quality']): ?><span class="chip">🎞 <?= esc($series['quality']) ?></span><?php endif; ?>
        <?php if ($series['language']): ?><span class="chip">🌐 <?= esc($series['language']) ?></span><?php endif; ?>
        <span class="chip">📺 Web Series</span>
        <?php if (!empty($series['pinned'])): ?><span class="chip chip-pin">📌 Pinned</span><?php endif; ?>
      </div>
      <?php if ($detailCats): ?>
      <div class="detail-cats">
        <?php foreach ($detailCats as $dc): ?>
        <a class="cat-badge" style="--cat:<?= esc($dc['color']) ?>" href="<?= esc(categoryPageUrl($dc['slug'])) ?>"><?= esc($dc['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <p class="detail-lead"><?= esc(excerpt($series['description'] ?: 'এই সিরিজের বিস্তারিত তথ্য এখনো যোগ করা হয়নি।', 260)) ?></p>
      <div class="detail-actions">
        <?php if ($firstEp): ?>
        <a class="btn btn-primary btn-lg" href="<?= esc(watchEpisodeUrl((int)$firstEp['id'])) ?>" data-gate data-target="<?= esc(watchEpisodeUrl((int)$firstEp['id'])) ?>">▶ Watch S1 E1</a>
        <?php endif; ?>
        <a class="btn btn-outline btn-lg" href="#episodes">🎞 All Episodes</a>
        <a class="btn btn-green btn-lg" href="#episodes">⬇ Downloads</a>
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
    <p class="detail-desc"><?= nl2br(esc($series['description'] ?: 'Description এখনো যোগ করা হয়নি।')) ?></p>
  </section>

  <section class="section" id="episodes">
    <div class="section-head"><h2>🎬 All Episodes</h2><span class="ep-count"><?= count($episodes) ?> টি এপিসোড</span></div>
    <?php if (!$episodes): ?>
      <div class="empty"><div class="big">📺</div>এখনো কোনো এপিসোড যোগ করা হয়নি।</div>
    <?php endif; ?>
    <?php foreach ($bySeason as $season => $eps): ?>
      <h3 class="ep-season-title">Season <?= (int)$season ?></h3>
      <div class="ep-grid">
        <?php foreach ($eps as $ep): ?>
        <a class="ep-box" href="<?= esc(watchEpisodeUrl((int)$ep['id'])) ?>" data-gate data-target="<?= esc(watchEpisodeUrl((int)$ep['id'])) ?>"
           title="<?= esc($ep['title'] !== '' ? $ep['title'] : ('Episode ' . (int)$ep['episode_number'])) ?>">
          <span class="ep-box-num">E<?= (int)$ep['episode_number'] ?></span>
          <span class="ep-box-name"><?= esc(excerpt($ep['title'] !== '' ? $ep['title'] : ('Episode ' . (int)$ep['episode_number']), 26)) ?></span>
          <span class="ep-box-meta"><?= ($ep['duration'] ? '⏱ ' . esc($ep['duration']) . ' · ' : '') ?>👁 <?= formatViews($ep['views']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <?php if ($episodes): ?>
    <h3 class="ep-season-title" style="margin-top:28px">⬇ Episode Downloads</h3>
    <?php foreach ($bySeason as $season => $eps): ?>
      <div class="ep-list">
        <?php foreach ($eps as $ep): ?>
        <div class="ep-item">
          <div class="ep-num">E<?= (int)$ep['episode_number'] ?></div>
          <div class="ep-info">
            <h4><?= esc($ep['title'] !== '' ? $ep['title'] : 'Episode ' . (int)$ep['episode_number']) ?></h4>
            <p><?= $ep['duration'] ? '⏱ ' . esc($ep['duration']) . ' · ' : '' ?>👁 <?= formatViews($ep['views']) ?> views</p>
          </div>
          <div class="ep-actions">
            <a class="btn btn-primary btn-sm" href="<?= esc(watchEpisodeUrl((int)$ep['id'])) ?>" data-gate data-target="<?= esc(watchEpisodeUrl((int)$ep['id'])) ?>">▶ Watch</a>
            <a class="btn btn-green btn-sm" href="<?= esc(downloadEpisodeUrl((int)$ep['id'])) ?>" data-gate data-target="<?= esc(downloadEpisodeUrl((int)$ep['id'])) ?>">⬇ Download</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <?php renderAd('mid_banner', 'Sponsored'); ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
