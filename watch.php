<?php
require_once __DIR__ . '/includes/functions.php';

$siteName  = getSetting('site_name', 'MovieVerse');
$serverIdx = max(0, (int)($_GET['server'] ?? 0));
$servers   = [];   // [name, type(drive|mp4), url, fileId]
$dlTarget  = '';
$backUrl   = BASE_URL . '/';
$backLabel = '← Home';

if (isset($_GET['ep'])) {
    /* ---------- Episode Watch ---------- */
    $ep = episodeById((int)$_GET['ep']);
    if (!$ep) { header('Location: ' . BASE_URL . '/'); exit; }
    incrementViews('episodes', (int)$ep['id']);
    $title     = $ep['series_title'] . ' — S' . (int)$ep['season'] . ' E' . (int)$ep['episode_number'] . ($ep['title'] !== '' ? ' · ' . $ep['title'] : '');
    $pageTitle = 'Watch ' . $title . ' | ' . $siteName;
    $pageDesc  = excerpt($ep['description'] ?: ('Watch ' . $title . ' online in HD.'), 250);
    $backUrl   = BASE_URL . '/movie.php?slug=' . rawurlencode((string)$ep['series_slug']);
    $backLabel = '← ' . $ep['series_title'];
    if (trim((string)$ep['drive_link']) !== '') {
        $servers[] = ['name' => 'Drive Server', 'type' => 'drive', 'url' => trim((string)$ep['drive_link']), 'fileId' => driveFileId($ep['drive_link'])];
    }
    $dlTarget = downloadEpisodeUrl((int)$ep['id']);
} else {
    /* ---------- Movie Watch ---------- */
    $movie = itemById((int)($_GET['id'] ?? 0));
    if (!$movie || $movie['status'] !== 'published') { header('Location: ' . BASE_URL . '/'); exit; }
    incrementViews('movies', (int)$movie['id']);
    $title     = $movie['title'];
    $pageTitle = 'Watch ' . $title . ' | ' . $siteName;
    $pageDesc  = excerpt($movie['description'] ?: ('Watch ' . $title . ' online in HD.'), 250);
    $backUrl   = movieUrl($movie);
    $backLabel = '← ' . $movie['title'];
    $n = 0;
    if (trim((string)$movie['drive_link']) !== '') {
        $servers[] = ['name' => 'Server ' . (++$n), 'type' => 'drive', 'url' => trim((string)$movie['drive_link']), 'fileId' => driveFileId($movie['drive_link'])];
    }
    foreach (decodeServers($movie['servers']) as $s) {
        if ($s['type'] === 'drive') { $s['fileId'] = driveFileId($s['url']); }
        $servers[] = $s;
    }
    $dlTarget = downloadMovieUrl((int)$movie['id']);
}

/* শুধু চালানো-যোগ্য সার্ভার (Drive id ঠিক আছে বা direct mp4) */
$servers = array_values(array_filter($servers, function ($s) {
    return ($s['type'] === 'drive' && $s['fileId'] !== '') || $s['type'] === 'mp4';
}));
if ($serverIdx >= count($servers)) $serverIdx = 0;
$cur = $servers[$serverIdx] ?? null;

require __DIR__ . '/includes/header.php';
?>
<div class="container watch-wrap">
  <div class="watch-head">
    <h1>▶ <?= esc($title) ?></h1>
    <a class="btn btn-outline btn-sm" href="<?= esc($backUrl) ?>"><?= esc($backLabel) ?></a>
  </div>

  <?php if ($cur): ?>
  <div class="player-box" id="playerBox">
    <div id="playerInner" style="position:absolute;inset:0">
      <?php if ($cur['type'] === 'mp4'): ?>
        <video controls playsinline preload="metadata" src="<?= esc($cur['url']) ?>"></video>
      <?php else: ?>
        <iframe class="drive-frame" src="https://drive.google.com/file/d/<?= esc($cur['fileId']) ?>/preview"
                allow="autoplay; fullscreen" allowfullscreen frameborder="0" scrolling="no"></iframe>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if (count($servers) > 1): ?>
  <div class="server-bar" id="serverBar">
    <span class="lbl">📺 Servers:</span>
    <?php foreach ($servers as $i => $s): ?>
      <button type="button" class="server-btn<?= $i === $serverIdx ? ' active' : '' ?>" onclick="mvLoadServer(<?= $i ?>, this)"><?= esc($s['name']) ?></button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <script>window.MV_SERVERS = <?= json_encode($servers, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>

  <?php renderAd('incontent_banner', 'Sponsored'); ?>

  <div class="watch-download">
    <a class="btn btn-green btn-lg" href="#" data-gate data-target="<?= esc($dlTarget) ?>">⬇ Download This Video</a>
  </div>

  <div class="watch-note">
    💡 <b>টিপস:</b> ভিডিও না চললে উপরের অন্য Server বাটনে ক্লিক করে দেখুন। প্লেয়ারের ভিতরে ডানদিকে আইকনে ক্লিক করলে ফুলস্ক্রিন হবে।
  </div>

  <?php renderAd('mid_banner', 'Sponsored'); ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
