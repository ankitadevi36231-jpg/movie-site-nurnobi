<?php
require_once __DIR__ . '/inc/auth.php';
$adminPage = 'password';
$pageTitle = 'Change Password';
$error = '';
$ok = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    $cur  = (string)($_POST['current'] ?? '');
    $new  = (string)($_POST['new'] ?? '');
    $new2 = (string)($_POST['new2'] ?? '');
    $row  = DB::row('SELECT * FROM admin_users WHERE id = ?', [(int)$_SESSION['admin_id']]);
    if (!$row || !password_verify($cur, $row['password'])) {
        $error = '❌ বর্তমান পাসওয়ার্ড ভুল!';
    } elseif (strlen($new) < 6) {
        $error = '❌ নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।';
    } elseif ($new !== $new2) {
        $error = '❌ দুটো নতুন পাসওয়ার্ড মিলছে না।';
    } else {
        DB::q('UPDATE admin_users SET password = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), (int)$_SESSION['admin_id']]);
        $ok = true;
    }
}
require __DIR__ . '/inc/header.php';
?>
<div class="page-title"><h1>🔑 Change Password</h1></div>

<?php if ($ok): ?><div class="success">✅ পাসওয়ার্ড বদলে গেছে!</div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= esc($error) ?></div><?php endif; ?>

<div class="panel" style="max-width:480px">
  <form method="post">
    <?= csrf_field() ?>
    <label class="fl">বর্তমান Password</label>
    <input class="in" type="password" name="current" required>
    <label class="fl">নতুন Password</label>
    <input class="in" type="password" name="new" required minlength="6">
    <label class="fl">নতুন Password (আবার)</label>
    <input class="in" type="password" name="new2" required minlength="6">
    <button class="btn btn-primary" type="submit" style="margin-top:16px">💾 Update Password</button>
  </form>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
