<?php
require_once __DIR__ . '/inc/auth.php';
$adminPage = 'ads';
$pageTitle = 'Ads Manager';

$slots = [
    'head_code'        => 'Head Code — Popunder / সাধারণ স্ক্রিপ্ট (head-এ বসে)',
    'body_code'        => 'Body Top Code — Social Bar / Direct স্ক্রিপ্ট (body-র শুরুতে)',
    'header_banner'    => 'Header Banner — নেভবারের নিচে',
    'incontent_banner' => 'In-content Banner — পেজের মাঝখানে',
    'mid_banner'       => 'Mid Banner — কনটেন্টের শেষের দিকে',
    'footer_banner'    => 'Footer Banner — ফুটারের উপরে',
    'native_ad'        => 'Native Ads — ফুটারের নিচে (Recommended)',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    foreach ($slots as $k => $lbl) {
        setSetting('ad_' . $k . '_on', isset($_POST['on_' . $k]) ? '1' : '0');
        setSetting('ad_' . $k . '_code', trim((string)($_POST['code_' . $k] ?? '')));
    }
    setSetting('ad_direct_link', trim((string)($_POST['ad_direct_link'] ?? '')));
    setSetting('ad_gate_expiry', (string)max(1, (int)($_POST['ad_gate_expiry'] ?? 30)));
    setSetting('download_timer', (string)max(0, (int)($_POST['download_timer'] ?? 10)));
    setSetting('ad_show_label', isset($_POST['ad_show_label']) ? '1' : '0');
    $_SESSION['flash'] = '✅ Ads সেটিংস সেভ হয়ে গেছে!';
    header('Location: ads.php');
    exit;
}
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
require __DIR__ . '/inc/header.php';
?>
<div class="page-title"><h1>💰 Ads Manager</h1></div>

<?php if ($flash): ?><div class="success"><?= esc($flash) ?></div><?php endif; ?>

<form method="post">
  <?= csrf_field() ?>

  <div class="panel">
    <h2>🔗 Direct Link (Double-Click Gate)</h2>
    <div class="notice">
      🎯 <b>এটাই মূল সিস্টেম:</b> ইউজার Watch/Download বাটনে <b>১ম ক্লিক</b> দিলে এই Direct Link নতুন ট্যাবে খুলবে। ফিরে এসে <b>২য় ক্লিক</b> দিলেই আসল কনটেন্ট খুলবে। এখানে Adsterra-র <b>Direct Link</b> URL বসান।
    </div>
    <label class="fl">Adsterra Direct Link URL</label>
    <input class="in" type="text" name="ad_direct_link" value="<?= esc(getSetting('ad_direct_link')) ?>" placeholder="https://www.profitableratecpm.com/xxxxxxxx">
    <div class="grid2">
      <div><label class="fl">Gate Expiry (মিনিট — কতক্ষণের মধ্যে ২য় ক্লিক দিলে ad ছাড়া ঢুকবে)</label>
        <input class="in" type="number" min="1" name="ad_gate_expiry" value="<?= esc(getSetting('ad_gate_expiry', '30')) ?>"></div>
      <div><label class="fl">Download পেজের কাউন্টডাউন (সেকেন্ড)</label>
        <input class="in" type="number" min="0" name="download_timer" value="<?= esc(getSetting('download_timer', '10')) ?>"></div>
    </div>
    <div class="check"><input type="checkbox" name="ad_show_label" id="slb" <?= getSetting('ad_show_label', '1') === '1' ? 'checked' : '' ?>><label for="slb">Ad-এর উপরে "Advertisement" লেবেল দেখাও</label></div>
  </div>

  <?php foreach ($slots as $key => $label): ?>
  <div class="panel">
    <h2><?= esc($label) ?></h2>
    <div class="check"><input type="checkbox" name="on_<?= $key ?>" id="on_<?= $key ?>" <?= getSetting('ad_' . $key . '_on') === '1' ? 'checked' : '' ?>><label for="on_<?= $key ?>">✅ Enabled</label></div>
    <textarea class="in code" name="code_<?= $key ?>" placeholder="এখানে Adsterra-র ad code paste করুন..."><?= esc(getSetting('ad_' . $key . '_code')) ?></textarea>
  </div>
  <?php endforeach; ?>

  <button class="btn btn-primary btn-lg" type="submit">💾 Save All Ads Settings</button>
</form>

<?php require __DIR__ . '/inc/footer.php'; ?>
