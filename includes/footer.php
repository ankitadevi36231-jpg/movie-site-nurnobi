</main>

<?php renderAd('footer_banner', 'Sponsored'); ?>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="f-col">
      <h3><?= esc($siteName) ?></h3>
      <p><?= esc(getSetting('site_about', 'Watch and download your favourite movies & web series in HD quality.')) ?></p>
      <?php if ($telegram !== ''): ?><a class="tg-btn" href="<?= esc($telegram) ?>" target="_blank" rel="noopener">✈️ Join Telegram Channel</a><?php endif; ?>
    </div>
    <div class="f-col">
      <h4>Quick Links</h4>
      <a href="<?= BASE_URL ?>/">Home</a>
      <a href="<?= BASE_URL ?>/category.php?type=movie">Movies</a>
      <a href="<?= BASE_URL ?>/category.php?type=series">Web Series</a>
      <a href="<?= BASE_URL ?>/sitemap.php">Sitemap</a>
    </div>
    <div class="f-col">
      <h4>Disclaimer</h4>
      <p class="f-small"><?= esc(getSetting('footer_text', 'All contents are provided by third-party sources. We do not host any files on our server.')) ?></p>
    </div>
  </div>
  <div class="footer-bottom">© <?= date('Y') ?> <?= esc($siteName) ?> — All Rights Reserved.</div>
</footer>

<?php renderAd('native_ad', 'Recommended For You'); ?>

<script src="<?= ASSETS_URL ?>/js/main.js?v=1.0"></script>
<script src="<?= ASSETS_URL ?>/js/ad-click.js?v=1.0"></script>
</body>
</html>
