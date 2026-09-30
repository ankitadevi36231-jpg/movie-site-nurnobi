<?php
require_once __DIR__ . '/inc/auth.php';
$adminPage = 'settings';
$pageTitle = 'Site Settings';

$fields = [
    'site_name'        => 'Site Name (লোগো টেক্সট)',
    'site_tagline'     => 'Tagline (ব্রাউজার ট্যাবে দেখাবে)',
    'site_description' => 'Meta Description (SEO)',
    'site_about'       => 'Footer-এ About টেক্সট',
    'telegram_link'    => 'Telegram Channel Link (খালি রাখলে বাটন লুকাবে)',
    'footer_text'      => 'Footer Disclaimer টেক্সট',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();
    foreach ($fields as $k => $lbl) {
        setSetting($k, trim((string)($_POST[$k] ?? '')));
    }
    if (isset($_POST['site_logo_clear'])) {
        setSetting('site_logo', '');                      /* লোগো মুছে ফেলো */
    } else {
        $logo = trim((string)($_POST['site_logo_url'] ?? ''));
        if (($up = handleUpload('site_logo_file', 'branding')) !== '') $logo = $up;
        if ($logo !== '') setSetting('site_logo', $logo);
    }
    $_SESSION['flash'] = '✅ সেটিংস সেভ হয়ে গেছে!';
    header('Location: settings.php');
    exit;
}
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
require __DIR__ . '/inc/header.php';
?>
<div class="page-title"><h1>⚙️ Site Settings</h1></div>

<?php if ($flash): ?><div class="success"><?= esc($flash) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="panel">
    <h2>📝 General</h2>
    <div class="grid2">
      <?php foreach ($fields as $k => $lbl): ?>
      <div>
        <label class="fl"><?= esc($lbl) ?></label>
        <input class="in" type="text" name="<?= $k ?>" value="<?= esc(getSetting($k)) ?>">
      </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary btn-lg" type="submit" style="margin-top:18px">💾 Save Settings</button>
  </div>

  <div class="panel">
    <h2>🖼 Website Logo</h2>
    <div class="notice">এখানে লোগো ইমেজ বসালে সাইটের হেডারে ও ব্রাউজার ট্যাবে (favicon/og) সেটি দেখাবে। ছবি না থাকলে টেক্সট লোগো (🎬 Site Name) দেখাবে।</div>
    <label class="fl">লোগো ইমেজ আপলোড করুন (PNG / SVG / JPG — স্বচ্ছ ব্যাকগ্রাউন্ড ভালো)</label>
    <input class="in" type="file" name="site_logo_file" accept="image/*">
    <label class="fl" style="margin-top:12px">অথবা লিংক দিন (uploads/... অথবা https://...)</label>
    <input class="in" type="text" name="site_logo_url" value="<?= esc(getSetting('site_logo')) ?>" placeholder="https://example.com/logo.png">
    <?php if (getSetting('site_logo')): ?>
    <div style="margin-top:14px;background:var(--panel2);border:1px solid var(--border);border-radius:10px;padding:14px;display:flex;align-items:center;gap:16px">
      <img src="<?= esc(mediaUrl(getSetting('site_logo'))) ?>" style="height:52px;max-width:220px;object-fit:contain" alt="logo" onerror="this.style.display='none'">
      <div class="check" style="margin:0"><input type="checkbox" name="site_logo_clear" id="logoClear"><label for="logoClear" style="color:var(--danger)">❌ লোগো মুছে ফেলুন (টেক্সট লোগো দেখাবে)</label></div>
    </div>
    <?php endif; ?>
    <button class="btn btn-primary btn-lg" type="submit" style="margin-top:18px">💾 Save Logo</button>
  </div>
</form>

<?php require __DIR__ . '/inc/footer.php'; ?>
