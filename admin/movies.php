<?php
require_once __DIR__ . '/inc/auth.php';

$type      = ($_GET['type'] ?? 'movie') === 'series' ? 'series' : 'movie';
$adminPage = $type === 'series' ? 'series' : 'movies';
$pageTitle = $type === 'series' ? 'Web Series' : 'Movies';

/* ---------- POST Actions ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    $id     = (int)($_POST['id'] ?? 0);
    if ($action === 'delete' && $id > 0) {
        DB::q('DELETE FROM movies WHERE id = ?', [$id]);   /* এপিসোডগুলো CASCADE দিয়ে মুছে যায় */
        $_SESSION['flash'] = '🗑 ডিলিট হয়ে গেছে।';
    } elseif ($action === 'toggle_status' && $id > 0) {
        DB::q('UPDATE movies SET status = IF(status = "published", "draft", "published") WHERE id = ?', [$id]);
        $_SESSION['flash'] = '✅ Status বদলে গেছে।';
    } elseif ($action === 'toggle_featured' && $id > 0) {
        DB::q('UPDATE movies SET featured = 1 - featured WHERE id = ?', [$id]);
        $_SESSION['flash'] = '✅ Featured আপডেট হয়েছে।';
    } elseif ($action === 'toggle_trending' && $id > 0) {
        DB::q('UPDATE movies SET trending = 1 - trending WHERE id = ?', [$id]);
        $_SESSION['flash'] = '✅ Trending আপডেট হয়েছে।';
    } elseif ($action === 'toggle_pinned' && $id > 0) {
        DB::q('UPDATE movies SET pinned = 1 - pinned WHERE id = ?', [$id]);
        $_SESSION['flash'] = '📌 Pin স্ট্যাটাস বদলে গেছে।';
    }
    header('Location: movies.php?type=' . $type . '&page=' . (int)($_POST['page'] ?? 1));
    exit;
}
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

/* ---------- List + Search + Pagination ---------- */
$search  = trim((string)($_GET['q'] ?? ''));
$perPage = 20;
$where   = 'type = ?';
$p       = [$type];
if ($search !== '') { $where .= ' AND title LIKE ?'; $p[] = '%' . $search . '%'; }

$total   = (int)DB::val('SELECT COUNT(*) FROM movies WHERE ' . $where, $p);
$pages   = max(1, (int)ceil($total / $perPage));
$page    = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
$items   = DB::rows('SELECT ' . MV_COLS . ' FROM movies WHERE ' . $where . ' ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $p);

require __DIR__ . '/inc/header.php';
?>
<div class="page-title">
  <h1><?= $type === 'series' ? '📺 Web Series' : '🎬 All Movies' ?> <small style="color:var(--muted);font-size:14px">(<?= $total ?>)</small></h1>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <form method="get" style="display:flex;gap:8px">
      <input type="hidden" name="type" value="<?= esc($type) ?>">
      <input class="in" type="text" name="q" placeholder="টাইটেল দিয়ে খুঁজুন..." value="<?= esc($search) ?>" style="width:200px">
      <button class="btn" type="submit">🔍</button>
    </form>
    <a class="btn btn-primary" href="movie-form.php?type=<?= esc($type) ?>">➕ Add New</a>
  </div>
</div>

<?php if ($flash): ?><div class="success"><?= esc($flash) ?></div><?php endif; ?>

<div class="panel">
  <div style="overflow-x:auto">
  <table class="tb">
    <tr><th>#</th><th>Poster</th><th>Title</th><th>Year</th><th>Status</th><th>⭐</th><th>🔥</th><th>📌</th><th>Views</th><th>Actions</th></tr>
    <?php if (!$items): ?><tr><td colspan="10" style="color:var(--muted)">কিছু পাওয়া যায়নি।</td></tr><?php endif; ?>
    <?php foreach ($items as $i => $m): $pid = (int)$m['id']; ?>
    <tr>
      <td><?= ($page - 1) * $perPage + $i + 1 ?></td>
      <td><img class="thumb" src="<?= esc(mediaUrl($m['poster']) ?: ASSETS_URL . '/no-poster.svg') ?>" alt="" onerror="this.src='<?= ASSETS_URL ?>/no-poster.svg'"></td>
      <td><b><?= esc(excerpt($m['title'], 42)) ?></b><br><small style="color:var(--muted)">/<?= esc($m['slug']) ?></small></td>
      <td><?= esc($m['year']) ?></td>
      <td>
        <form method="post" style="display:inline"><?= csrf_field() ?>
          <input type="hidden" name="action" value="toggle_status"><input type="hidden" name="id" value="<?= $pid ?>"><input type="hidden" name="page" value="<?= $page ?>">
          <button class="badge <?= $m['status'] === 'published' ? 'on' : 'off' ?>" style="border:0;cursor:pointer"><?= $m['status'] === 'published' ? 'Published' : 'Draft' ?></button>
        </form>
      </td>
      <td>
        <form method="post" style="display:inline"><?= csrf_field() ?>
          <input type="hidden" name="action" value="toggle_featured"><input type="hidden" name="id" value="<?= $pid ?>"><input type="hidden" name="page" value="<?= $page ?>">
          <button class="btn btn-sm" style="cursor:pointer"><?= $m['featured'] ? '⭐' : '☆' ?></button>
        </form>
      </td>
      <td>
        <form method="post" style="display:inline"><?= csrf_field() ?>
          <input type="hidden" name="action" value="toggle_trending"><input type="hidden" name="id" value="<?= $pid ?>"><input type="hidden" name="page" value="<?= $page ?>">
          <button class="btn btn-sm" style="cursor:pointer"><?= $m['trending'] ? '🔥' : '○' ?></button>
        </form>
      </td>
      <td>
        <form method="post" style="display:inline"><?= csrf_field() ?>
          <input type="hidden" name="action" value="toggle_pinned"><input type="hidden" name="id" value="<?= $pid ?>"><input type="hidden" name="page" value="<?= $page ?>">
          <button class="btn btn-sm" style="cursor:pointer;<?= $m['pinned'] ? 'background:rgba(245,165,36,.18);border-color:rgba(245,165,36,.5);color:#f5a524' : '' ?>" title="হোমপেজে সবার উপরে রাখবে"><?= $m['pinned'] ? '📌' : '📍' ?></button>
        </form>
      </td>
      <td><?= formatViews($m['views']) ?></td>
      <td style="white-space:nowrap">
        <a class="btn btn-sm" href="movie-form.php?id=<?= $pid ?>">✏️ Edit</a>
        <?php if ($m['type'] === 'series'): ?><a class="btn btn-sm" href="episodes.php?series_id=<?= $pid ?>">🎞 Episodes</a><?php endif; ?>
        <form method="post" style="display:inline" data-confirm="আপনি কি নিশ্চিত? সিরিজ হলে এর সব এপিসোডও মুছে যাবে!"><?= csrf_field() ?>
          <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $pid ?>"><input type="hidden" name="page" value="<?= $page ?>">
          <button class="btn btn-danger btn-sm" type="submit">🗑</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>

  <?php if ($pages > 1): ?>
  <div class="pager">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <?php if ($i === $page): ?><span class="active"><?= $i ?></span>
      <?php else: ?><a href="?type=<?= esc($type) ?>&q=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
