<?php
/**
 * MovieVerse — ফ্রন্ট-এন্ড হেডার (সব পেজে এক)
 * ব্যবহার: $pageTitle / $pageDesc / $ogImage সেট করে এই ফাইল require করুন।
 */
if (!defined('BASE_URL')) { require_once __DIR__ . '/../config/config.php'; }
require_once __DIR__ . '/functions.php';

$siteName    = getSetting('site_name', 'MovieVerse');
$pageTitle   = $pageTitle ?? $siteName;
$pageDesc    = $pageDesc  ?? getSetting('site_description', '');
$ogImage     = $ogImage   ?? mediaUrl(getSetting('site_logo', ''));
$telegram    = getSetting('telegram_link', '');
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($pageTitle) ?></title>
<?php if ($pageDesc !== ''): ?><meta name="description" content="<?= esc(excerpt($pageDesc, 300)) ?>"><?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= esc($siteName) ?>">
<meta property="og:title" content="<?= esc($pageTitle) ?>">
<?php if ($pageDesc !== ''): ?><meta property="og:description" content="<?= esc(excerpt($pageDesc, 300)) ?>"><?php endif; ?>
<?php if ($ogImage !== ''): ?><meta property="og:image" content="<?= esc($ogImage) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="<?= ASSETS_URL ?>/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=1.0">
<!-- Head Ad Code (Admin Panel → Ad Manager থেকে সেট করা) -->
<?= adCode('head_code') ?>
</head>
<body data-direct-link="<?= esc(directAdLink()) ?>" data-ad-expiry="<?= (int)adGateExpiry() ?>" data-base-url="<?= BASE_URL ?>">
<!-- Body Top Ad Code (Popunder / Social Bar ইত্যাদি এখানে বসে) -->
<?= adCode('body_code') ?>

<header class="site-header">
  <div class="container header-inner">
    <a class="logo" href="<?= BASE_URL ?>/">
      <?php $logo = getSetting('site_logo', ''); ?>
      <?php if ($logo !== ''): ?>
        <img src="<?= esc(mediaUrl($logo)) ?>" alt="<?= esc($siteName) ?>">
      <?php else: ?>
        <span class="logo-text">🎬 <?= esc($siteName) ?></span>
      <?php endif; ?>
    </a>
    <nav class="main-nav" id="mainNav">
      <a class="nv-home" href="<?= BASE_URL ?>/">🏠 Home</a>
      <a class="nv-movies" href="<?= BASE_URL ?>/category.php?type=movie">🎬 Movies</a>
      <a class="nv-series" href="<?= BASE_URL ?>/category.php?type=series">📺 Web Series</a>
      <div class="nav-drop">
        <a class="nv-cats" href="<?= BASE_URL ?>/category.php">📂 Categories ▾</a>
        <div class="drop-menu">
          <?php foreach (categoriesAll() as $navCat): ?>
          <a href="<?= esc(categoryPageUrl((string)$navCat['slug'])) ?>"><span class="dot" style="background:<?= esc(categoryColor((string)$navCat['slug'])) ?>"></span><?= esc($navCat['name']) ?></a>
          <?php endforeach; ?>
          <div class="drop-sep"></div>
          <a href="<?= BASE_URL ?>/category.php">📋 View All →</a>
        </div>
      </div>
      <div class="nav-drop nv-genres">
        <a href="javascript:void(0)">🎭 Genres ▾</a>
        <div class="drop-menu">
          <?php foreach (genresList(14) as $g): ?><a href="<?= esc(categoryUrl($g)) ?>"><?= esc($g) ?></a><?php endforeach; ?>
        </div>
      </div>
      <?php if ($telegram !== ''): ?><a class="nav-tg" href="<?= esc($telegram) ?>" target="_blank" rel="noopener">✈️ Telegram</a><?php endif; ?>
    </nav>
    <form class="search-box" action="<?= BASE_URL ?>/search.php" method="get">
      <input type="text" name="q" placeholder="মুভি / সিরিজ খুঁজুন..." value="<?= esc($_GET['q'] ?? '') ?>">
      <button type="submit" aria-label="Search">🔍</button>
    </form>
    <button class="nav-toggle" id="navToggle" aria-label="Menu">☰</button>
  </div>
</header>
<?php renderAd('header_banner', 'Sponsored'); ?>
<main class="site-main">
