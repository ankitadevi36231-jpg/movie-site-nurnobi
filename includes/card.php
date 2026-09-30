<a class="card" href="<?= esc($m['type'] === 'series' ? seriesUrl($m) : movieUrl($m)) ?>">
  <div class="card-poster">
    <img src="<?= esc(mediaUrl($m['poster'])) ?>" alt="<?= esc($m['title']) ?>" loading="lazy"
         onerror="this.onerror=null;this.src='<?= ASSETS_URL ?>/no-poster.svg'">
    <?php $mCats = movieCats($m); ?>
    <?php if ($mCats): ?>
    <div class="card-cats">
      <?php foreach (array_slice($mCats, 0, 3) as $mc): ?>
      <span class="cat-badge" style="--cat:<?= esc($mc['color']) ?>"><?= esc($mc['name']) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <span class="card-type"><?= $m['type'] === 'series' ? 'SERIES' : 'MOVIE' ?></span>
    <?php if (!empty($m['pinned'])): ?><span class="pin-badge">📌 Pinned</span><?php endif; ?>
    <div class="card-play"><span>▶</span></div>
  </div>
  <div class="card-info">
    <h3><?= esc($m['title']) ?></h3>
    <div class="card-meta">
      <span><?= esc($m['year'] ?: timeAgo($m['created_at'])) ?></span>
      <?php if ((float)$m['rating'] > 0): ?><span class="rate">⭐ <?= esc($m['rating']) ?></span><?php endif; ?>
    </div>
  </div>
</a>
