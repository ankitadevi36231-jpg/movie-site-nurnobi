<?php
/**
 * MovieVerse — One-Click Installer
 * ব্রাউজারে এই ফাইলটা খুললেই ডেটাবেজ + টেবিল + অ্যাডমিন অ্যাকাউন্ট অটো তৈরি হবে।
 * ইনস্টল শেষ হলে নিরাপত্তার জন্য এই ফাইলটা ডিলিট করে ফেলুন (নিচে বাটন আছে)।
 */
require_once __DIR__ . '/config/config.php';

/* স্বয়ংসম্পূর্ণ ইনস্টলার — functions.php লোড হয় না, তাই esc() এখানেই */
if (!function_exists('esc')) {
    function esc(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

$err = '';
$ok  = false;
$alreadyInstalled = false;

/* ---------- DB কানেকশন ---------- */
function mv_install_connect(bool $withDb): PDO {
    $dsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4' . ($withDb ? ';dbname=' . DB_NAME : '');
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

$pdo = null;
try {
    $pdo = mv_install_connect(false);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo = mv_install_connect(true);
} catch (Throwable $e) {
    $err = 'MySQL কানেক্ট হচ্ছে না! XAMPP Control Panel থেকে <b>MySQL → Start</b> করুন। (cPanel হলে config/config.php-এ ডেটাবেজ ইউজার/পাসওয়ার্ড ঠিক করুন)<br><small>' . esc($e->getMessage()) . '</small>';
}

/* ---------- টেবিল তৈরি ---------- */
if ($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
        setting_value TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        username   VARCHAR(50)  NOT NULL UNIQUE,
        password   VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS movies (
        id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title           VARCHAR(255) NOT NULL,
        slug            VARCHAR(255) NOT NULL UNIQUE,
        type            ENUM('movie','series') NOT NULL DEFAULT 'movie',
        poster          VARCHAR(500) DEFAULT '',
        backdrop        VARCHAR(500) DEFAULT '',
        description     TEXT NULL,
        year            VARCHAR(10)  DEFAULT '',
        genres          VARCHAR(255) DEFAULT '',
        categories      VARCHAR(500) NOT NULL DEFAULT '',
        quality         VARCHAR(20)  DEFAULT '',
        language        VARCHAR(50)  DEFAULT '',
        duration        VARCHAR(20)  DEFAULT '',
        rating          DECIMAL(3,1) DEFAULT 0.0,
        drive_link      VARCHAR(500) DEFAULT '',
        download_link   VARCHAR(500) DEFAULT '',
        servers         TEXT NULL,
        seo_title       VARCHAR(255) DEFAULT '',
        seo_description VARCHAR(500) DEFAULT '',
        featured        TINYINT(1) NOT NULL DEFAULT 0,
        trending        TINYINT(1) NOT NULL DEFAULT 0,
        pinned          TINYINT(1) NOT NULL DEFAULT 0,
        views           INT UNSIGNED NOT NULL DEFAULT 0,
        status          ENUM('published','draft') NOT NULL DEFAULT 'published',
        created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_type_status (type, status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(100) NOT NULL,
        slug       VARCHAR(120) NOT NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $catSt = $pdo->prepare('INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)');
    foreach (['Adult', 'Serial', 'Web Series', 'Game'] as $catName) {
        $catSt->execute([$catName, strtolower(trim((string)preg_replace('~[^a-z0-9]+~i', '-', $catName), '-'))]);
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS episodes (
        id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        series_id      INT UNSIGNED NOT NULL,
        season         INT NOT NULL DEFAULT 1,
        episode_number INT NOT NULL DEFAULT 1,
        title          VARCHAR(255) DEFAULT '',
        duration       VARCHAR(20)  DEFAULT '',
        drive_link     VARCHAR(500) DEFAULT '',
        download_link  VARCHAR(500) DEFAULT '',
        description    TEXT NULL,
        thumbnail      VARCHAR(500) DEFAULT '',
        views          INT UNSIGNED NOT NULL DEFAULT 0,
        status         ENUM('published','draft') NOT NULL DEFAULT 'published',
        created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_ep (series_id, season, episode_number),
        CONSTRAINT fk_ep_series FOREIGN KEY (series_id) REFERENCES movies(id) ON DELETE CASCADE,
        INDEX idx_ep_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* ---------- ডিফল্ট সেটিংস ---------- */
    $defaults = [
        'site_name'        => 'MovieVerse',
        'site_tagline'     => 'Watch & Download Movies, Web Series in HD',
        'site_description' => 'Watch and download latest movies and web series in HD quality for free.',
        'site_about'       => 'MovieVerse is a movie & web series streaming/download demo site. All contents are collected from third-party sources.',
        'site_logo'        => '',
        'telegram_link'    => '',
        'footer_text'      => 'Disclaimer: This site does not store any files on its server. All contents are provided by non-affiliated third parties.',
        'ad_direct_link'   => '',
        'ad_gate_expiry'   => '30',
        'download_timer'   => '10',
        'ad_show_label'    => '1',
        'installed'        => '1',
    ];
    $st = $pdo->prepare('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)');
    foreach ($defaults as $k => $v) { $st->execute([$k, $v]); }

    $alreadyInstalled = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() > 0;

    /* ---------- অ্যাডমিন অ্যাকাউন্ট তৈরি ---------- */
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['setup_admin'])) {
        session_start();
        $tok = (string)($_POST['install_csrf'] ?? '');
        if (!isset($_SESSION['install_csrf']) || !hash_equals((string)$_SESSION['install_csrf'], $tok)) {
            $err = 'Security token invalid — আবার চেষ্টা করুন।';
        } else {
            $u = trim((string)($_POST['username'] ?? ''));
            $p = (string)($_POST['password'] ?? '');
            if (mb_strlen($u) < 3)     $err = 'ইউজারনেম কমপক্ষে ৩ অক্ষরের হতে হবে।';
            elseif (strlen($p) < 6)    $err = 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।';
            elseif ($alreadyInstalled) $err = 'অ্যাডমিন অ্যাকাউন্ট আগেই আছে — পুরনো তথ্য দিয়ে লগইন করুন।';
            else {
                $pdo->prepare('INSERT INTO admin_users (username, password) VALUES (?, ?)')
                    ->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
                $ok = true;
            }
        }
    } else {
        $_SESSION['install_csrf'] = $_SESSION['install_csrf'] ?? bin2hex(random_bytes(16));
    }

    /* ---------- install.php নিজেকে ডিলিট ---------- */
    if (isset($_GET['delete'])) {
        if (@unlink(__FILE__)) { header('Location: admin/login.php'); exit; }
        $err = 'ফাইল ডিলিট করা যায়নি (পারমিশন) — FTP/File Manager থেকে ম্যানুয়ালি ডিলিট করুন।';
    }
}
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install — MovieVerse</title>
<link rel="icon" href="<?= ASSETS_URL ?>/favicon.svg" type="image/svg+xml">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;background:#0b0f17;color:#e5e9f0;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.box{width:100%;max-width:520px;background:#141a26;border:1px solid #253046;border-radius:16px;padding:34px}
h1{font-size:22px;color:#fff;text-align:center}
.sub{text-align:center;color:#8b95a7;font-size:13.5px;margin:8px 0 22px}
.step{background:#1a2233;border:1px solid #2a3650;border-radius:10px;padding:14px 16px;margin-bottom:12px;font-size:14.5px}
.step b{color:#4ade80}
.done{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.4);color:#86efac;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px}
.err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.4);color:#fca5a5;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13.5px;line-height:1.7}
label{display:block;font-size:13px;color:#8b95a7;margin:14px 0 6px;font-weight:600}
input{width:100%;background:#0f1524;border:1px solid #2a3650;border-radius:9px;color:#e5e9f0;padding:11px 13px;font-size:14.5px;outline:none}
input:focus{border-color:#4f9cff}
.btn{display:block;width:100%;background:#e50914;color:#fff;border:0;border-radius:9px;padding:13px;font-size:15px;font-weight:700;cursor:pointer;margin-top:20px;font-family:inherit}
.btn:hover{background:#c40812}
.btn2{display:block;text-align:center;background:#22c55e;color:#fff;text-decoration:none;border-radius:9px;padding:12px;font-size:14.5px;font-weight:700;margin-top:12px}
.btn3{display:block;text-align:center;background:transparent;border:1px solid #2a3650;color:#8b95a7;text-decoration:none;border-radius:9px;padding:10px;font-size:13px;margin-top:10px}
.links{display:flex;gap:10px;margin-top:6px}
.links a{flex:1;text-align:center;text-decoration:none;padding:12px;border-radius:9px;font-weight:700;font-size:14px}
.a1{background:#e50914;color:#fff}.a2{background:#22c55e;color:#fff}
small{word-break:break-all}
</style>
</head>
<body>
<div class="box">
  <h1>🎬 MovieVerse Installer</h1>
  <p class="sub">ডেটাবেজ + টেবিল অটো তৈরি হবে — শুধু অ্যাডমিন তথ্য দিন</p>

  <?php if ($err): ?><div class="err">⚠️ <?= $err ?></div><?php endif; ?>

  <?php if ($ok): ?>
    <div class="done">🎉 ইনস্টলেশন সম্পন্ন! আপনার সাইট এখন রেডি।</div>
    <div class="links">
      <a class="a1" href="<?= BASE_URL ?>/">🌐 View Site</a>
      <a class="a2" href="<?= BASE_URL ?>/admin/login.php">🔐 Admin Panel</a>
    </div>
    <a class="btn3" href="?delete=1" onclick="return confirm('install.php ডিলিট হয়ে যাবে — নিশ্চিত?')">🗑 নিরাপত্তার জন্য install.php ডিলিট করুন</a>
  <?php elseif ($alreadyInstalled): ?>
    <div class="done">✅ সাইট আগেই ইনস্টল করা হয়েছে! সরাসরি লগইন করুন।</div>
    <a class="btn2" href="<?= BASE_URL ?>/admin/login.php">🔐 Admin Panel — Login</a>
    <a class="btn3" href="<?= BASE_URL ?>/">🌐 View Site</a>
  <?php elseif ($pdo): ?>
    <form method="post" autocomplete="off">
      <input type="hidden" name="install_csrf" value="<?= esc($_SESSION['install_csrf']) ?>">
      <div class="step">✅ MySQL কানেক্টেড — <b><?= esc(DB_NAME) ?></b> ডেটাবেজ + সব টেবিল তৈরি হয়েছে</div>
      <label>Admin Username</label>
      <input type="text" name="username" value="<?= esc($_POST['username'] ?? 'admin') ?>" required minlength="3">
      <label>Admin Password (কমপক্ষে ৬ অক্ষর)</label>
      <input type="password" name="password" required minlength="6">
      <button class="btn" type="submit" name="setup_admin" value="1">🚀 Install Now</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
