<?php
require_once __DIR__ . '/inc/auth.php';
$adminPage = 'dashboard';
$pageTitle = 'Dashboard';

$stats = [
    'movies'   => (int)DB::val('SELECT COUNT(*) FROM movies WHERE type = ?', ['movie']),
    'series'   => (int)DB::val('SELECT COUNT(*) FROM movies WHERE type = ?', ['series']),
    'episodes' => (int)DB::val('SELECT COUNT(*) FROM episodes'),
    'views'    => (int)DB::val('SELECT COALESCE(SUM(views), 0) FROM movies') + (int)DB::val('SELECT COALESCE(SUM(views), 0) FROM episodes'),
    'drafts'   => (int)DB::val('SELECT COUNT(*) FROM movies WHERE status = ?', ['draft']),
];
$recent = DB::rows('SELECT ' . MV_COLS . ' FROM movies ORDER BY id DESC LIMIT 8');

require __DIR__ . '/inc/header.php';
?>
<div class="page-title">
  <h1>📊 Dashboard</h1>
  <a class="btn btn-primary" href="movie-form.php">➕ Add New Movie / Series</a>
</div>

<?php if ($stats['drafts'] > 0): ?>
<div class="notice">⚠️ <?= $stats['drafts'] ?> টি ড্রাফট আইটেম আছে — লিস্ট থেকে Edit করে Publish করুন।</div>
<?php endif; ?>

<div class="cards">
  <div class="stat"><div class="n"><?= $stats['movies'] ?></div><div class="l">🎬 Total Movies</div></div>
  <div class="stat"><div class="n"><?= $stats['series'] ?></div><div class="l">📺 Web Series</div></div>
  <div class="stat"><div class="n"><?= $stats['episodes'] ?></div><div class="l">🎞 Total Episodes</div></div>
  <div class="stat"><div class="n"><?= formatViews($stats['views']) ?></div><div class="l">👁 Total Views</div></div>
</div>

<div class="panel">
  <h2>🕘 Recently Added</h2>
  <div style="overflow-x:auto">
  <table class="tb">
    <tr><th>Poster</th><th>Title</th><th>Type</th><th>Status</th><th>Views</th><th>Added</th><th>Actions</th></tr>
    <?php if (!$recent): ?><tr><td colspan="7" style="color:var(--muted)">এখনো কিছু যোগ করা হয়নি — উপরের ➕ বাটনে ক্লিক করুন।</td></tr><?php endif; ?>
    <?php foreach ($recent as $m): ?>
    <tr>
      <td><img class="thumb" src="<?= esc(mediaUrl($m['poster']) ?: ASSETS_URL . '/no-poster.svg') ?>" alt="" onerror="this.src='<?= ASSETS_URL ?>/no-poster.svg'"></td>
      <td><b><?= esc(excerpt($m['title'], 40)) ?></b><br><small style="color:var(--muted)"><?= esc($m['year']) ?> · <?= esc($m['quality']) ?></small></td>
      <td><?= $m['type'] === 'series' ? '📺 Series' : '🎬 Movie' ?></td>
      <td><span class="badge <?= $m['status'] === 'published' ? 'on' : 'off' ?>"><?= $m['status'] === 'published' ? 'Published' : 'Draft' ?></span></td>
      <td><?= formatViews($m['views']) ?></td>
      <td><?= esc(timeAgo($m['created_at'])) ?></td>
      <td>
        <a class="btn btn-sm" href="movie-form.php?id=<?= (int)$m['id'] ?>">✏️ Edit</a>
        <?php if ($m['type'] === 'series'): ?><a class="btn btn-sm" href="episodes.php?series_id=<?= (int)$m['id'] ?>">🎞 Episodes</a><?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
</div>

<div class="panel">
  <h2>⚡ Quick Start Guide</h2>
  <ol style="padding-left:20px;color:var(--muted);font-size:14px;line-height:2">
    <li><b style="color:#fff">💰 Ads Manager</b> — Adsterra-র Banner / Popunder / Social Bar / Native কোড ও Direct Link বসান।</li>
    <li><b style="color:#fff">➕ Add New</b> — মুভি/সিরিজ যোগ করুন (Google Drive লিংকসহ)।</li>
    <li><b style="color:#fff">⚙️ Site Settings</b> — সাইটের নাম, লোগো, Telegram লিংক ঠিক করুন।</li>
    <li>Watch/Download বাটনে ১ম ক্লিকে Ad → ২য় ক্লিকে কনটেন্ট — এটা অটোমেটিক কাজ করবে।</li>
  </ol>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
