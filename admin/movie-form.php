<?php
require_once __DIR__ . '/inc/auth.php';
$adminPage = 'movie-form';

$id       = (int)($_GET['id'] ?? 0);
$item     = $id > 0 ? itemById($id) : null;
$pageTitle = $item ? 'Edit — ' . $item['title'] : 'Add New Movie / Series';
$errors   = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    /* ক্যাটাগরি: শুধু ডাটাবেজে থাকা স্লাগই নেওয়া হয় */
    $allowedCats = array_column(categoriesAll(), 'slug');
    $pickedCats  = [];
    foreach ((array)($_POST['cats'] ?? []) as $cs) {
        $cs = trim((string)$cs);
        if (in_array($cs, $allowedCats, true)) { $pickedCats[] = $cs; }
    }
    $data = [
        'title'      => trim((string)($_POST['title'] ?? '')),
        'type'       => ($_POST['type'] ?? 'movie') === 'series' ? 'series' : 'movie',
        'year'       => trim((string)($_POST['year'] ?? '')),
        'genres'     => trim((string)($_POST['genres'] ?? '')),
        'categories' => implode(',', $pickedCats),
        'quality'    => trim((string)($_POST['quality'] ?? '')),
        'language'   => trim((string)($_POST['language'] ?? '')),
        'duration'   => trim((string)($_POST['duration'] ?? '')),
        'rating'     => (float)($_POST['rating'] ?? 0),
        'description'    => trim((string)($_POST['description'] ?? '')),
        'drive_link'     => trim((string)($_POST['drive_link'] ?? '')),
        'download_link'  => trim((string)($_POST['download_link'] ?? '')),
        'seo_title'      => trim((string)($_POST['seo_title'] ?? '')),
        'seo_description'=> trim((string)($_POST['seo_description'] ?? '')),
        'status'   => ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published',
        'featured' => isset($_POST['featured']) ? 1 : 0,
        'trending' => isset($_POST['trending']) ? 1 : 0,
        'pinned'   => isset($_POST['pinned']) ? 1 : 0,
    ];
    if ($data['title'] === '') $errors[] = 'টাইটেল অবশ্যই দিতে হবে।';

    /* Poster / Backdrop: Upload > URL > পুরনো মান */
    $poster   = trim((string)($_POST['poster_url'] ?? ''));
    $backdrop = trim((string)($_POST['backdrop_url'] ?? ''));
    if (($up = handleUpload('poster_file', 'posters')) !== '')   $poster   = $up;
    if (($up2 = handleUpload('backdrop_file', 'posters')) !== '') $backdrop = $up2;

    /* Extra Servers rows */
    $servers = [];
    $names = $_POST['s_name'] ?? []; $stypes = $_POST['s_type'] ?? []; $surls = $_POST['s_url'] ?? [];
    foreach ((array)$surls as $i => $u) {
        $u = trim((string)$u);
        if ($u === '') continue;
        $nm = trim((string)($names[$i] ?? '')) !== '' ? trim((string)$names[$i]) : 'Server ' . ($i + 1);
        $servers[] = ['name' => $nm, 'type' => (($stypes[$i] ?? 'drive') === 'mp4') ? 'mp4' : 'drive', 'url' => $u];
    }
    $serversJson = $servers ? json_encode($servers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

    if (!$errors) {
        if ($item) {
            DB::q('UPDATE movies SET title=?, type=?, year=?, genres=?, categories=?, quality=?, language=?, duration=?, rating=?, description=?, drive_link=?, download_link=?, poster=?, backdrop=?, servers=?, seo_title=?, seo_description=?, status=?, featured=?, trending=?, pinned=? WHERE id=?', [
                $data['title'], $data['type'], $data['year'], $data['genres'], $data['categories'], $data['quality'], $data['language'], $data['duration'],
                $data['rating'], $data['description'], $data['drive_link'], $data['download_link'],
                $poster !== '' ? $poster : $item['poster'], $backdrop !== '' ? $backdrop : $item['backdrop'],
                $serversJson, $data['seo_title'], $data['seo_description'], $data['status'], $data['featured'], $data['trending'], $data['pinned'], $id,
            ]);
            $_SESSION['flash'] = '✅ আপডেট হয়ে গেছে!';
        } else {
            $base = slugify($data['title']);
            $slug = $base; $n = 1;
            while ((int)DB::val('SELECT COUNT(*) FROM movies WHERE slug = ?', [$slug]) > 0) { $slug = $base . '-' . (++$n); }
            DB::q('INSERT INTO movies (title, slug, type, year, genres, categories, quality, language, duration, rating, description, drive_link, download_link, poster, backdrop, servers, seo_title, seo_description, status, featured, trending, pinned) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $data['title'], $slug, $data['type'], $data['year'], $data['genres'], $data['categories'], $data['quality'], $data['language'], $data['duration'],
                $data['rating'], $data['description'], $data['drive_link'], $data['download_link'],
                $poster !== '' ? $poster : '', $backdrop !== '' ? $backdrop : '',
                $serversJson, $data['seo_title'], $data['seo_description'], $data['status'], $data['featured'], $data['trending'], $data['pinned'],
            ]);
            $newId = (int)DB::conn()->lastInsertId();
            DB::q('UPDATE movies SET slug = ? WHERE id = ?', [$slug . '-' . $newId, $newId]);  /* স্লাগ ইউনিক রাখতে id জোড়া */
            $_SESSION['flash'] = '✅ নতুন আইটেম যোগ হয়েছে!';
            if ($data['type'] === 'series') { header('Location: episodes.php?series_id=' . $newId . '&added=1'); exit; }
            $id   = $newId;
            $item = itemById($newId);
        }
        header('Location: movie-form.php?id=' . $id . '&saved=1');
        exit;
    }

    /* Error হলে ফর্মে পুরনো ভ্যালু দেখাও */
    $item = array_merge($item ?: [], $data, [
        'poster'   => $poster   !== '' ? $poster   : (string)($item['poster'] ?? ''),
        'backdrop' => $backdrop !== '' ? $backdrop : (string)($item['backdrop'] ?? ''),
        'servers'  => $serversJson !== null ? $serversJson : (string)($item['servers'] ?? ''),
    ]);
}
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$serversArr = decodeServers($item['servers'] ?? null);

$e = function (string $k, string $d = '') use ($item): string {
    return esc((string)($item[$k] ?? $d));
};
require __DIR__ . '/inc/header.php';
?>
<div class="page-title">
  <h1><?= $item ? '✏️ Edit: ' . esc((string)$item['title']) : '➕ Add New Movie / Series' ?></h1>
  <div style="display:flex;gap:10px">
    <?php if ($item && $item['type'] === 'series'): ?><a class="btn" href="episodes.php?series_id=<?= (int)$item['id'] ?>">🎞 Manage Episodes</a><?php endif; ?>
    <a class="btn" href="movies.php?type=<?= esc((string)($item['type'] ?? 'movie')) ?>">← Back</a>
  </div>
</div>

<?php if ($flash): ?><div class="success"><?= esc($flash) ?></div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="success">✅ সেভ হয়ে গেছে!</div><?php endif; ?>
<?php if ($errors): ?><div class="error"><?php foreach ($errors as $er) { echo '❌ ' . esc($er) . '<br>'; } ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="grid2">
    <div class="panel">
      <h2>📋 Basic Info</h2>
      <label class="fl">Title *</label>
      <input class="in" type="text" name="title" value="<?= $e('title') ?>" required>
      <div class="grid3">
        <div><label class="fl">Type</label>
          <select class="in" name="type">
            <option value="movie" <?= (($item['type'] ?? '') === 'movie') ? 'selected' : '' ?>>🎬 Movie</option>
            <option value="series" <?= (($item['type'] ?? '') === 'series') ? 'selected' : '' ?>>📺 Web Series</option>
          </select></div>
        <div><label class="fl">Year</label><input class="in" type="text" name="year" value="<?= $e('year') ?>" placeholder="2024"></div>
        <div><label class="fl">Rating (0-10)</label><input class="in" type="text" name="rating" value="<?= $e('rating') ?>" placeholder="7.5"></div>
      </div>
      <label class="fl">Genres (কমা দিয়ে আলাদা করুন)</label>
      <input class="in" type="text" name="genres" value="<?= $e('genres') ?>" placeholder="Action, Thriller, Drama">
      <label class="fl">📂 Categories (থাম্বনেইলে রঙিন বক্সে দেখাবে — একাধিক দিতে পারেন)</label>
      <div class="cat-checks">
        <?php
        $allCats      = categoriesAll();
        $curCatSlugs  = array_filter(array_map('trim', explode(',', (string)($item['categories'] ?? ''))));
        foreach ($allCats as $ac): ?>
        <label class="check" style="margin:0">
          <input type="checkbox" name="cats[]" value="<?= esc($ac['slug']) ?>" <?= in_array($ac['slug'], $curCatSlugs, true) ? 'checked' : '' ?>>
          <span style="display:inline-flex;align-items:center;gap:7px">
            <span style="width:12px;height:12px;border-radius:4px;background:<?= esc(categoryColor((string)$ac['slug'])) ?>;display:inline-block"></span>
            <?= esc($ac['name']) ?>
          </span>
        </label>
        <?php endforeach; ?>
        <?php if (!$allCats): ?><span style="color:var(--muted);font-size:13px">কোনো ক্যাটাগরি নেই — <a href="categories.php">Categories পেজে</a> তৈরি করুন।</span><?php endif; ?>
      </div>
      <div class="grid3">
        <div><label class="fl">Quality</label>
          <select class="in" name="quality">
            <?php foreach (['480p', '720p', '1080p', '4K', 'HDRip', 'Web-DL'] as $qv): ?>
            <option value="<?= $qv ?>" <?= ($item['quality'] ?? '') === $qv ? 'selected' : '' ?>><?= $qv ?></option>
            <?php endforeach; ?>
          </select></div>
        <div><label class="fl">Language</label><input class="in" type="text" name="language" value="<?= $e('language') ?>" placeholder="Bangla / Hindi / English"></div>
        <div><label class="fl">Duration</label><input class="in" type="text" name="duration" value="<?= $e('duration') ?>" placeholder="2h 15m"></div>
      </div>
      <label class="fl">Description / Storyline</label>
      <textarea class="in" name="description"><?= $e('description') ?></textarea>
      <div class="check"><input type="checkbox" name="featured" id="f1" <?= ($item['featured'] ?? 0) ? 'checked' : '' ?>><label for="f1">⭐ Homepage Slider-এ দেখাও (Featured)</label></div>
      <div class="check"><input type="checkbox" name="trending" id="f2" <?= ($item['trending'] ?? 0) ? 'checked' : '' ?>><label for="f2">🔥 Trending সেকশনে দেখাও</label></div>
      <div class="check"><input type="checkbox" name="pinned" id="f3" <?= ($item['pinned'] ?? 0) ? 'checked' : '' ?>><label for="f3">📌 Pin করো — লিস্টে সবার উপরে রাখবে (হোমপেজ, ক্যাটাগরি)</label></div>
      <label class="fl">Status</label>
      <select class="in" name="status">
        <option value="published" <?= (($item['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>✅ Published (সাইটে দেখাবে)</option>
        <option value="draft" <?= (($item['status'] ?? '') === 'draft') ? 'selected' : '' ?>>📝 Draft (লুকানো থাকবে)</option>
      </select>
    </div>

    <div class="panel">
      <h2>🖼 Images</h2>
      <label class="fl">Poster (2:3 রেশিও ভালো)</label>
      <input class="in" type="text" name="poster_url" value="<?= $e('poster') ?>" placeholder="https://... অথবা খালি রেখে নিচে আপলোড করুন">
      <input class="in" type="file" name="poster_file" accept="image/*" style="margin-top:8px">
      <img src="<?= esc(mediaUrl((string)($item['poster'] ?? '')) ?: ASSETS_URL . '/no-poster.svg') ?>" style="width:90px;margin-top:10px;border-radius:8px;border:1px solid var(--border)" onerror="this.src='<?= ASSETS_URL ?>/no-poster.svg'" alt="">
      <label class="fl">Backdrop (16:9 — হিরো স্লাইডার, ঐচ্ছিক)</label>
      <input class="in" type="text" name="backdrop_url" value="<?= $e('backdrop') ?>" placeholder="https://...">
      <input class="in" type="file" name="backdrop_file" accept="image/*" style="margin-top:8px">
    </div>
  </div>

  <div class="panel">
    <h2>🎥 Main Video (Google Drive)</h2>
    <div class="notice">📌 Drive ফাইলটি শেয়ার করে <b>Anyone with the link</b> দিন। লিংক paste করলেই সিস্টেম নিজে থেকে এমবেড করে নেবে — ইউজার বুঝতেই পারবে না ভিডিও Drive-এ হোস্ট করা।</div>
    <label class="fl">Google Drive Link / File ID (প্লেয়ারে চলবে)</label>
    <input class="in" type="text" name="drive_link" value="<?= $e('drive_link') ?>" placeholder="https://drive.google.com/file/d/FILE_ID/view">
    <label class="fl">Custom Download Link (ঐচ্ছিক — না দিলে Drive ডাউনলোড লিংক অটো তৈরি হবে)</label>
    <input class="in" type="text" name="download_link" value="<?= $e('download_link') ?>" placeholder="https://...">
  </div>

  <div class="panel">
    <h2>🖥 Extra Servers (বিকল্প লিংক — ঐচ্ছিক)</h2>
    <div class="notice">Drive-এর প্লে কোটা শেষ হলে ইউজার এই সার্ভারগুলো ব্যবহার করবে। Watch পেজে সার্ভার বাটন দেখাবে।</div>
    <div id="srvRows" style="display:flex;flex-direction:column;gap:10px"></div>
    <button class="btn" type="button" onclick="addSrv('', '', 'drive')">➕ Add Server</button>
  </div>

  <div class="panel">
    <h2>🔍 SEO (ঐচ্ছিক)</h2>
    <div class="grid2">
      <div><label class="fl">SEO Title</label><input class="in" type="text" name="seo_title" value="<?= $e('seo_title') ?>"></div>
      <div><label class="fl">SEO Description</label><input class="in" type="text" name="seo_description" value="<?= $e('seo_description') ?>"></div>
    </div>
  </div>

  <button class="btn btn-primary btn-lg" type="submit">💾 Save</button>
</form>

<script>
function escA(s) {
  return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
}
function addSrv(n, u, t) {
  var d = document.createElement('div');
  d.className = 'grid3';
  d.innerHTML =
    '<input class="in" type="text" name="s_name[]" placeholder="Server নাম (যেমন: Server 2)" value="' + escA(n) + '">' +
    '<input class="in" type="text" name="s_url[]" placeholder="https://... লিংক" value="' + escA(u) + '">' +
    '<select class="in" name="s_type[]">' +
    '<option value="drive"' + (t === 'drive' ? ' selected' : '') + '>Google Drive</option>' +
    '<option value="mp4"' + (t === 'mp4' ? ' selected' : '') + '>Direct MP4</option>' +
    '</select>';
  document.getElementById('srvRows').appendChild(d);
}
(function () {
  var srv = <?= json_encode($serversArr, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  if (!srv.length) { addSrv('', '', 'drive'); }
  else { srv.forEach(function (s) { addSrv(s.name, s.url, s.type); }); }
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
