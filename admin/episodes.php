<?php
require_once __DIR__ . '/inc/auth.php';

$seriesId = (int)($_GET['series_id'] ?? 0);
$series   = $seriesId > 0 ? itemById($seriesId) : null;
if (!$series || $series['type'] !== 'series') { header('Location: movies.php?type=series'); exit; }

$adminPage = 'series';
$pageTitle = 'Episodes — ' . $series['title'];

/* ---------- POST Actions ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    $id     = (int)($_POST['id'] ?? 0);
    if ($action === 'delete' && $id > 0) {
        DB::q('DELETE FROM episodes WHERE id = ?', [$id]);
        $_SESSION['flash'] = '🗑 এপিসোড ডিলিট হয়েছে।';
    } elseif ($action === 'toggle_status' && $id > 0) {
        DB::q('UPDATE episodes SET status = IF(status = "published", "draft", "published") WHERE id = ?', [$id]);
        $_SESSION['flash'] = '✅ Status বদলে গেছে।';
    }
    header('Location: episodes.php?series_id=' . $seriesId);
    exit;
}
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$eps = episodesOfSeries($seriesId);
require __DIR__ . '/inc/header.php';
?>
<div class="page-title">
  <h1>🎞 Episodes — <span style="color:var(--gold)"><?= esc($series['title']) ?></span></h1>
  <div style="display:flex;gap:10px">
    <a class="btn" href="movie-form.php?id=<?= $seriesId ?>">✏️ Series Info</a>
    <a class="btn btn-primary" href="episode-form.php?series_id=<?= $seriesId ?>">➕ Add Episode</a>
  </div>
</div>

<?php if (isset($_GET['added'])): ?><div class="success">🎉 সিরিজ তৈরি হয়েছে! এখন এপিসোড যোগ করুন।</div><?php endif; ?>
<?php if ($flash): ?><div class="success"><?= esc($flash) ?></div><?php endif; ?>

<div class="panel">
  <?php if (!$eps): ?>
    <div class="empty" style="padding:30px"><div class="big">🎞</div>কোনো এপিসোড নেই — উপরের ➕ বাটন দিয়ে প্রথম এপিসোড যোগ করুন।</div>
  <?php else: ?>
  <div style="overflow-x:auto">
  <table class="tb">
    <tr><th>Season</th><th>Episode</th><th>Title</th><th>Duration</th><th>Status</th><th>Views</th><th>Actions</th></tr>
    <?php foreach ($eps as $ep): $eid = (int)$ep['id']; ?>
    <tr>
      <td>S<?= (int)$ep['season'] ?></td>
      <td><b style="color:var(--gold)">E<?= (int)$ep['episode_number'] ?></b></td>
      <td><?= esc(excerpt($ep['title'] !== '' ? $ep['title'] : '—', 45)) ?><br>
          <small style="color:var(--muted)"><?= esc(mb_substr(trim(strip_tags((string)$ep['drive_link'])), 0, 42)) ?>…</small></td>
      <td><?= esc($ep['duration']) ?></td>
      <td>
        <form method="post" style="display:inline"><?= csrf_field() ?>
          <input type="hidden" name="action" value="toggle_status"><input type="hidden" name="id" value="<?= $eid ?>">
          <button class="badge <?= $ep['status'] === 'published' ? 'on' : 'off' ?>" style="border:0;cursor:pointer"><?= $ep['status'] === 'published' ? 'Published' : 'Draft' ?></button>
        </form>
      </td>
      <td><?= formatViews($ep['views']) ?></td>
      <td style="white-space:nowrap">
        <a class="btn btn-sm" href="episode-form.php?id=<?= $eid ?>">✏️ Edit</a>
        <form method="post" style="display:inline" data-confirm="এই এপিসোডটি ডিলিট করবেন?"><?= csrf_field() ?>
          <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $eid ?>">
          <button class="btn btn-danger btn-sm" type="submit">🗑</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
