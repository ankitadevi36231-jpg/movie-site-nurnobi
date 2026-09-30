<?php
/* MovieVerse — XML Sitemap */
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/xml; charset=UTF-8');

$items = DB::rows('SELECT slug, type, updated_at FROM movies WHERE status = "published" ORDER BY id DESC');
$today = date('Y-m-d');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= esc(BASE_URL . '/') ?></loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
<?php foreach ($items as $m):
    $url = ($m['type'] === 'series') ? BASE_URL . '/series.php?slug=' . rawurlencode($m['slug']) : BASE_URL . '/movie.php?slug=' . rawurlencode($m['slug']);
    $lastmod = $m['updated_at'] ? date('Y-m-d', strtotime($m['updated_at'])) : $today;
?>
  <url>
    <loc><?= esc($url) ?></loc>
    <lastmod><?= $lastmod ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
<?php endforeach; ?>
</urlset>
