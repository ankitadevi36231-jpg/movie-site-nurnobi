<?php
require_once __DIR__ . '/inc/auth.php';
$adminPage = 'series';

$id       = (int)($_GET['id'] ?? 0);
$ep       = $id > 0 ? episodeById($id) : null;
$seriesId = (int)($_GET['series_id'] ?? ($ep['series_id'] ?? 0));
$series   = $seriesId > 0 ? itemById($seriesId) : null;
if (!$series || $series['type'] !== 'series') { header('Location: movies.php?type=series'); exit; }

$pageTitle = $ep ? 'Edit Episode' : 'Add Episode';
$errors    = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    $d = [
        'season'         => max(1, (int)($_POST['season'] ?? 1)),
        'episode_number' => max(1, (int)($_POST['episode_number'] ?? 1)),
        'title'          => trim((string)($_POST['title'] ?? '')),
        'duration'       => trim((string)($_POST['duration'] ?? '')),
        'drive_link'     => trim((string)($_POST['drive_link'] ?? '')),
        'download_link'  => trim((string)($_POST['download_link'] ?? '')),
        'description'    => trim((string)($_POST['description'] ?? '')),
        'status'         => ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published',
    ];
    if ($d['drive_link'] === '') $errors[] = 'Google Drive লিংক দিতে হবে (এটাই ভিডিও চলবে)।';

    $thumb = trim((string)($_POST['thumb_url'] ?? ''));
    if (($up = handleUpload('thumb_file', 'posters')) !== '') $thumb = $up;

    if (!$errors) {
        if ($ep) {
            DB::q('UPDATE episodes SET season=?, episode_number=?, title=?, duration=?, drive_link=?, download_link=?, description=?, thumbnail=?, status=? WHERE id=?', [
                $d['season'], $d['episode_number'], $d['title'], $d['duration'], $d['drive_link'], $d['download_link'], $d['description'],
                $thumb !== '' ? $thumb : $ep['thumbnail'], $d['status'], $id,
            ]);
            $_SESSION['flash'] = '✅ এপিসোড আপডেট হয়েছে!';
        } else {
            DB::q('INSERT INTO episodes (series_id, season, episode_number, title, duration, drive_link, download_link, description, thumbnail, status) VALUES (?,?,?,?,?,?,?,?,?,?)', [
                $seriesId, $d['season'], $d['episode_number'], $d['title'], $d['duration'], $d['drive_link'], $d['download_link'], $d['description'], $thumb, $d['status'],
            ]);
            $_SESSION['flash'] = '✅ নতুন এপিসোড যোগ হয়েছে!';
        }
        header('Location: episodes.php?series_id=' . $seriesId);
        exit;
    }
    $ep = array_merge($ep ?: [], $d, ['thumbnail' => $thumb !== '' ? $thumb : (string)($ep['thumbnail'] ?? '')]);
}

$e = function (string $k, string $d = '') use ($ep): string {
    return esc((string)($ep[$k] ?? $d));
};
require __DIR__ . '/inc/header.php';
?>
<div class="page-title">
  <h1><?= $ep ? '✏️ Edit Episode' : '➕ Add Episode' ?> — <span style="color:var(--gold)"><?= esc((string)$series['title']) ?></span></h1>
  <a class="btn" href="episodes.php?series_id=<?= $seriesId ?>">← All Episodes</a>
</div>

<?php if ($errors): ?><div class="error"><?php foreach ($errors as $er) { echo '❌ ' . esc($er) . '<br>'; } ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="panel">
    <div class="grid3">
      <div><label class="fl">Season নম্বর</label><input class="in" type="number" name="season" min="1" value="<?= $e('season', '1') ?>"></div>
      <div><label class="fl">Episode নম্বর</label><input class="in" type="number" name="episode_number" min="1" value="<?= $e('episode_number', (string)(count(episodesOfSeries($seriesId)) + 1)) ?>"></div>
      <div><label class="fl">Duration</label><input class="in" type="text" name="duration" value="<?= $e('duration') ?>" placeholder="42m"></div>
    </div>
    <label class="fl">Episode Title (ঐচ্ছিক)</label>
    <input class="in" type="text" name="title" value="<?= $e('title') ?>" placeholder="যেমন: The Beginning">
    <div class="notice" style="margin-top:14px">📌 Drive ফাইল শেয়ার: <b>Anyone with the link</b></div>
    <label class="fl">Google Drive Link / File ID *</label>
    <input class="in" type="text" name="drive_link" value="<?= $e('drive_link') ?>" placeholder="https://drive.google.com/file/d/FILE_ID/view" required>
    <label class="fl">Custom Download Link (ঐচ্ছিক)</label>
    <input class="in" type="text" name="download_link" value="<?= $e('download_link') ?>" placeholder="খালি রাখলে Drive ডাউনলোড অটো">
    <label class="fl">Thumbnail (ঐচ্ছিক)</label>
    <input class="in" type="text" name="thumb_url" value="<?= $e('thumbnail') ?>" placeholder="https://...">
    <input class="in" type="file" name="thumb_file" accept="image/*" style="margin-top:8px">
    <label class="fl">Episode Description (ঐচ্ছিক)</label>
    <textarea class="in" name="description"><?= $e('description') ?></textarea>
    <label class="fl">Status</label>
    <select class="in" name="status">
      <option value="published" <?= (($ep['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>✅ Published</option>
      <option value="draft" <?= (($ep['status'] ?? '') === 'draft') ? 'selected' : '' ?>>📝 Draft</option>
    </select>
    <button class="btn btn-primary btn-lg" type="submit" style="margin-top:18px">💾 Save Episode</button>
  </div>
</form>

<?php require __DIR__ . '/inc/footer.php'; ?>
