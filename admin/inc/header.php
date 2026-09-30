<?php
/* Admin Panel Layout — Header (ভিতরে $adminPage, $pageTitle সেট করে দিন) */
$mvAdmin = currentAdmin();
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($pageTitle ?? 'Admin') ?> — Admin Panel</title>
<link rel="icon" href="<?= ASSETS_URL ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/admin.css?v=1.0">
</head>
<body>
<header class="topbar">
  <div class="brand">🎬 MovieVerse <span>Admin</span></div>
  <div class="tb-actions">
    <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>/" target="_blank">🌐 View Site</a>
    <span style="color:var(--muted);font-size:13px">👤 <?= esc($mvAdmin['username'] ?? 'admin') ?></span>
    <a class="btn btn-danger btn-sm" href="logout.php">Logout</a>
  </div>
</header>
<div class="layout">
  <aside class="sidebar">
    <a href="index.php" class="<?= ($adminPage ?? '') === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
    <a href="movies.php?type=movie" class="<?= ($adminPage ?? '') === 'movies' ? 'active' : '' ?>">🎬 All Movies</a>
    <a href="movies.php?type=series" class="<?= ($adminPage ?? '') === 'series' ? 'active' : '' ?>">📺 Web Series</a>
    <a href="movie-form.php" class="<?= ($adminPage ?? '') === 'movie-form' ? 'active' : '' ?>">➕ Add New</a>
    <a href="categories.php" class="<?= ($adminPage ?? '') === 'categories' ? 'active' : '' ?>">📂 Categories</a>
    <a href="ads.php" class="<?= ($adminPage ?? '') === 'ads' ? 'active' : '' ?>">💰 Ads Manager</a>
    <a href="settings.php" class="<?= ($adminPage ?? '') === 'settings' ? 'active' : '' ?>">⚙️ Site Settings</a>
    <a href="password.php" class="<?= ($adminPage ?? '') === 'password' ? 'active' : '' ?>">🔑 Change Password</a>
  </aside>
  <main class="content">
