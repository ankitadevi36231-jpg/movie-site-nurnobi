<?php
require_once __DIR__ . '/inc/auth.php';
$adminPage = 'categories';
$pageTitle = 'Categories';
$errors    = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'add') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'ক্যাটাগরির নাম দিতে হবে।';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'নাম সর্বোচ্চ ১০০ অক্ষরের হতে পারবে।';
        } else {
            $slug = slugify($name);
            if (categoryBySlug($slug)) {
                $errors[] = 'এই ক্যাটাগরি আগেই আছে: ' . $name;
            } else {
                DB::q('INSERT INTO categories (name, slug) VALUES (?, ?)', [$name, $slug]);
                $_SESSION['flash'] = '✅ ক্যাটাগরি যোগ হয়েছে: ' . $name;
                header('Location: categories.php');
                exit;
            }
        }
    } elseif ($action === 'delete') {
        $id   = (int)($_POST['id'] ?? 0);
        $slug = $id > 0 ? (string)DB::val('SELECT slug FROM categories WHERE id = ?', [$id]) : '';
        if ($slug !== '') {
            DB::q('DELETE FROM categories WHERE id = ?', [$id]);
            /* মুভিগুলো থেকেও স্লাগ বাদ দিই — নাহলে পুরনো ব্যাজ থেকে যাবে */
            DB::q("UPDATE movies SET categories = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', categories, ','), ?, ',')) WHERE FIND_IN_SET(?, categories)", [',' . $slug . ',', $slug]);
            $_SESSION['flash'] = '🗑 ক্যাটাগরি ডিলিট হয়েছে এবং সব মুভি থেকে সরানো হয়েছে।';
        }
        header('Location: categories.php');
        exit;
    }
}
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$cats = categoriesAll();
$counts = categoryCounts();
require __DIR__ . '/inc/header.php';
?>
<div class="page-title"><h1>📂 Categories</h1></div>

<?php if ($flash): ?><div class="success"><?= esc($flash) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="error"><?php foreach ($errors as $er) { echo '❌ ' . esc($er) . '<br>'; } ?></div><?php endif; ?>

<div class="grid2" style="align-items:start">
  <div class="panel">
    <h2>➕ নতুন ক্যাটাগরি যোগ করুন</h2>
    <div class="notice">মুভি আপলোডের সময় এখানে যোগ করা ক্যাটাগরিগুলো চেকবক্স হিসেবে দেখাবে। মেইন সাইটে থাম্বনেইলের বাম পাশে রঙিন ছোট বক্সে ক্যাটাগরি দেখাবে।</div>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <label class="fl">ক্যাটাগরির নাম *</label>
      <input class="in" type="text" name="name" placeholder="যেমন: Documentary, Bangla, Thriller" required maxlength="100">
      <button class="btn btn-primary" type="submit" style="margin-top:14px">➕ যোগ করুন</button>
    </form>
    <div class="notice" style="margin-top:18px">📌 ডিফল্ট ৪টি ক্যাটাগরি: <b>Adult, Serial, Web Series, Game</b> — চাইলে ডিলিট করে নিজের পছন্দমতো রাখতে পারেন।</div>
  </div>

  <div class="panel">
    <h2>📋 সব ক্যাটাগরি <small style="color:var(--muted);font-size:13px">(<?= count($cats) ?>)</small></h2>
    <?php if (!$cats): ?>
      <div class="empty" style="padding:24px">কোনো ক্যাটাগরি নেই।</div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="tb">
      <tr><th>#</th><th>রঙ</th><th>নাম</th><th>Slug</th><th>আইটেম</th><th></th></tr>
      <?php foreach ($cats as $i => $c): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><span style="display:inline-block;width:18px;height:18px;border-radius:5px;background:<?= esc(categoryColor((string)$c['slug'])) ?>"></span></td>
        <td><b><?= esc($c['name']) ?></b></td>
        <td><small style="color:var(--muted)"><?= esc($c['slug']) ?></small></td>
        <td><span class="badge on"><?= (int)($counts[$c['slug']] ?? 0) ?></span></td>
        <td>
          <form method="post" style="display:inline" data-confirm="<?= esc($c['name']) ?> ক্যাটাগরি ডিলিট করবেন? এর সব মুভি থেকেও এটি সরে যাবে।"><?= csrf_field() ?>
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">🗑</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>