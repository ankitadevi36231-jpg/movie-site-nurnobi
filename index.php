<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = getSetting('site_name', 'MovieVerse') . ' — ' . getSetting('site_tagline', 'Watch & Download HD Movies');
require __DIR__ . '/includes/header.php';
?>

<?php /* ---------- Hero Slider (Featured) ---------- */
$featured = featuredMovies(5); ?>
<?php if ($featured): ?>
<section class="hero" id="hero">
  <?php foreach ($featured as $i => $m): ?>
  <div class="hero-slide<?= $i === 0 ? ' active' : '' ?>">
    <div class="hero-bg">
      <img src="<?= esc(mediaUrl($m['backdrop'] ?: $m['poster'])) ?>" alt="<?= esc($m['title']) ?>" onerror="this.style.display='none'">
    </div>
    <div class="hero-content container">
      <span class="h-badge">⭐ FEATURED</span>
      <h2><?= esc($m['title']) ?></h2>
      <div class="hero-meta">
        <?php if ((float)$m['rating'] > 0): ?><span class="rate">⭐ <?= esc($m['rating']) ?></span><?php endif; ?>
        <?php if ($m['year']): ?><span><?= esc($m['year']) ?></span><?php endif; ?>
        <?php if ($m['quality']): ?><span><?= esc($m['quality']) ?></span><?php endif; ?>
        <?php if ($m['language']): ?><span><?= esc($m['language']) ?></span><?php endif; ?>
        <?php if ($m['duration']): ?><span>⏱ <?= esc($m['duration']) ?></span><?php endif; ?>
      </div>
      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="#" data-gate data-target="<?= esc(watchMovieUrl((int)$m['id'])) ?>">▶ Watch Now</a>
        <a class="btn btn-outline btn-lg" href="#" data-gate data-target="<?= esc(downloadMovieUrl((int)$m['id'])) ?>">⬇ Download</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <div class="hero-dots">
    <?php foreach ($featured as $i => $m): ?><button class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<div class="container">
  <?php renderAd('incontent_banner', 'Sponsored'); ?>

  <?php /* ---------- Category Tiles (রঙিন বক্স) ---------- */
  $homeCats = categoriesAll(); ?>
  <?php if ($homeCats): $homeCatCounts = categoryCounts(); ?>
  <section class="cat-tiles" aria-label="Categories">
    <?php foreach ($homeCats as $hc): ?>
    <a class="cat-tile" href="<?= esc(categoryPageUrl((string)$hc['slug'])) ?>" style="--cat:<?= esc(categoryColor((string)$hc['slug'])) ?>">
      <span class="cat-tile-dot"></span>
      <span class="cat-tile-name"><?= esc($hc['name']) ?></span>
      <span class="cat-tile-count"><?= (int)($homeCatCounts[$hc['slug']] ?? 0) ?> টি আইটেম</span>
    </a>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <?php /* ---------- Trending ---------- */
  $trending = trendingItems(12); ?>
  <?php if ($trending): ?>
  <section class="section">
    <div class="section-head"><h2>🔥 Trending Now</h2></div>
    <div class="scroll-row">
      <?php foreach ($trending as $m) { include __DIR__ . '/includes/card.php'; } ?>
    </div>
  </section>
  <?php endif; ?>

  <?php /* ---------- Latest Movies ---------- */
  $movies = latestItems('movie', 12); ?>
  <section class="section">
    <div class="section-head">
      <h2>🎬 Latest Movies</h2>
      <a href="<?= BASE_URL ?>/category.php?type=movie">View All →</a>
    </div>
    <?php if ($movies): ?>
    <div class="grid">
      <?php foreach ($movies as $m) { include __DIR__ . '/includes/card.php'; } ?>
    </div>
    <?php else: ?>
    <div class="empty"><div class="big">🎬</div>এখনো কোনো মুভি যোগ করা হয়নি। Admin Panel থেকে যোগ করুন।</div>
    <?php endif; ?>
  </section>

  <?php renderAd('mid_banner', 'Sponsored'); ?>

  <?php /* ---------- Web Series ---------- */
  $series = latestItems('series', 12); ?>
  <section class="section">
    <div class="section-head">
      <h2>📺 Web Series</h2>
      <a href="<?= BASE_URL ?>/category.php?type=series">View All →</a>
    </div>
    <?php if ($series): ?>
    <div class="grid">
      <?php foreach ($series as $m) { include __DIR__ . '/includes/card.php'; } ?>
    </div>
    <?php else: ?>
    <div class="empty"><div class="big">📺</div>এখনো কোনো ওয়েব সিরিজ যোগ করা হয়নি।</div>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
