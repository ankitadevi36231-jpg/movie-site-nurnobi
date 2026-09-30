<?php
require_once __DIR__ . '/../includes/functions.php';

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    $u = trim((string)($_POST['username'] ?? ''));
    $p = (string)($_POST['password'] ?? '');
    if ($u !== '' && $p !== '') {
        try {
            $row = DB::row('SELECT * FROM admin_users WHERE username = ? LIMIT 1', [$u]);
            if ($row && password_verify($p, $row['password'])) {
                $_SESSION['admin_id'] = (int)$row['id'];
                session_regenerate_id(true);
                header('Location: index.php');
                exit;
            }
            $error = '❌ ভুল ইউজারনেম বা পাসওয়ার্ড!';
        } catch (Throwable $e) {
            $error = '⚠️ ডেটাবেজ কানেক্ট হয়নি — আগে <a href="' . BASE_URL . '/install.php" style="text-decoration:underline">install.php</a> চালান!';
        }
    } else {
        $error = 'সব ফিল্ড পূরণ করুন।';
    }
}
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login — MovieVerse</title>
<link rel="icon" href="<?= ASSETS_URL ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/admin.css?v=1.0">
</head>
<body>
<div class="login-wrap">
  <form class="login-box" method="post" autocomplete="off">
    <h1>🎬 MovieVerse Admin</h1>
    <p class="sub">প্যানেলে ঢুকতে লগইন করুন</p>
    <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label class="fl">Username</label>
    <input class="in" type="text" name="username" value="<?= esc($_POST['username'] ?? '') ?>" required autofocus>
    <label class="fl">Password</label>
    <input class="in" type="password" name="password" required>
    <button class="btn btn-primary" style="width:100%;justify-content:center;margin-top:18px" type="submit">🔓 Login</button>
    <p class="sub" style="margin-top:14px">← <a href="<?= BASE_URL ?>/">সাইটে ফিরে যান</a></p>
  </form>
</div>
</body>
</html>
