<?php
require_once __DIR__ . '/includes/functions.php';

$links = [];      // [name, finalUrl]
$infoTitle = '';
$chips = [];

if (isset($_GET['ep'])) {
    /* ---------- Episode Download ---------- */
    $ep = episodeById((int)$_GET['ep']);
    if (!$ep) { header('Location: ' . BASE_URL . '/'); exit; }
    $infoTitle = $ep['series_title'] . ' — S' . (int)$ep['season'] . ' E' . (int)$ep['episode_number'] . ($ep['title'] !== '' ? ' · ' . $ep['title'] : '');
    if (trim((string)$ep['download_link']) !== '') {
        $links[] = ['📥 Download Episode', trim((string)$ep['download_link'])];
    } elseif (trim((string)$ep['drive_link']) !== '') {
        $links[] = ['📥 Download Episode (Drive)', driveDownloadUrl($ep['drive_link'])];
    }
} else {
    /* ---------- Movie Download ---------- */
    $movie = itemById((int)($_GET['id'] ?? 0));
    if (!$movie || $movie['status'] !== 'published') { header('Location: ' . BASE_URL . '/'); exit; }
    $infoTitle = $movie['title'] . ($movie['year'] ? ' (' . $movie['year'] . ')' : '');
    $mainUrl = trim((string)$movie['download_link']) !== '' ? $movie['download_link'] : driveDownloadUrl($movie['drive_link']);
    if ($mainUrl !== '') $links[] = ['📥 Main Download Link', $mainUrl];
    foreach (decodeServers($movie['servers']) as $i => $s) {
        $links[] = ['📥 ' . $s['name'], driveDownloadUrl($s['url'])];
    }
    if ($movie['quality'])  { $chips[] = '🎞 ' . $movie['quality']; }
    if ($movie['language']) { $chips[] = '🌐 ' . $movie['language']; }
    if ($movie['duration']) { $chips[] = '⏱ ' . $movie['duration']; }
}

$timer = max(0, (int)getSetting('download_timer', '10'));
$pageTitle = 'Download — ' . $infoTitle . ' | ' . getSetting('site_name', 'MovieVerse');
require __DIR__ . '/includes/header.php';
?>
<div class="container">
  <?php renderAd('header_banner', 'Sponsored'); ?>

  <div class="dl-box" id="dlBox" data-timer="<?= $timer ?>">
    <h1>⬇ Download Ready!</h1>
    <p style="color:#cbd3e1;margin-top:6px"><?= esc($infoTitle) ?></p>
    <?php if ($chips): ?>
    <div class="dl-info">
      <?php foreach ($chips as $c): ?><span class="chip"><?= esc($c) ?></span><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php renderAd('incontent_banner', 'Sponsored'); ?>

    <?php if ($links): ?>
    <div class="dl-timer-box">
      <div class="dl-timer" id="dlTimer"><?= $timer ?></div>
      <p class="dl-hint">⏳ অপেক্ষা করুন, আপনার লিংক প্রস্তুত হচ্ছে...</p>
    </div>

    <div class="dl-ready-btn" style="flex-direction:column;gap:12px">
      <?php foreach ($links as $l): ?>
        <a class="btn btn-green btn-lg" href="#" data-gate data-target="<?= esc($l[1]) ?>" data-newtab="1"><?= esc($l[0]) ?></a>
      <?php endforeach; ?>
      <p class="dl-hint">⚠️ ক্লিক করার পর একটি পেজ খুললে তা বন্ধ করে ফিরে এসে আবার এই বাটনে ক্লিক করুন — তখনই আপনার ফাইল খুলবে।</p>
    </div>
    <?php else: ?>
    <div class="empty" style="padding:20px"><div class="big">🔗</div>এই কনটেন্টের ডাউনলোড লিংক এখনো যোগ করা হয়নি।</div>
    <?php endif; ?>
  </div>

  <?php renderAd('footer_banner', 'Sponsored'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
