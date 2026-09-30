<?php
/**
 * MovieVerse — Helper Functions
 * Movie/Series, Google Drive Embed, Ad System, Admin Auth — সব হেল্পার এখানে।
 */
require_once __DIR__ . '/db.php';

/* ============ Output Escape ============ */
function esc($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* ============ Text / Time / Number Helpers ============ */
/** টেক্সট ছোট করে দেখানোর জন্য */
function excerpt(?string $text, int $len = 120): string {
    $t = trim(strip_tags((string)$text));
    if (mb_strlen($t) <= $len) return $t;
    $cut = mb_substr($t, 0, $len);
    $pos = mb_strrpos($cut, ' ');
    return rtrim($pos !== false ? mb_substr($cut, 0, $pos) : $cut) . '…';
}
/** "২ দিন আগে" স্টাইলের টাইম */
function timeAgo(?string $datetime): string {
    if ($datetime === null || trim($datetime) === '') return '';
    $ts = strtotime($datetime);
    if ($ts === false) return '';
    $diff = time() - $ts;
    if ($diff < 60)          return 'just now';
    if ($diff < 3600)        return floor($diff / 60) . ' min ago';
    if ($diff < 86400)       return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800)      return floor($diff / 86400) . ' day(s) ago';
    if ($diff < 2592000)     return floor($diff / 604800) . ' week(s) ago';
    if ($diff < 31536000)    return floor($diff / 2592000) . ' month(s) ago';
    return floor($diff / 31536000) . ' year(s) ago';
}
/** 1250 → "1.3K", 2000000 → "2M" */
function formatViews($n): string {
    $n = (int)$n;
    if ($n >= 1000000) return round($n / 1000000, 1) . 'M';
    if ($n >= 1000)    return round($n / 1000, 1) . 'K';
    return (string)$n;
}
/** সিরিজের ডিটেইল পেজ URL */
function seriesUrl(array $m): string {
    return BASE_URL . '/series.php?slug=' . rawurlencode((string)($m['slug'] ?? $m['id']));
}

/* ============ Settings (per-request cache) ============ */
function getSetting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (DB::rows('SELECT setting_key, setting_value FROM settings') as $r) {
                $cache[$r['setting_key']] = (string)$r['setting_value'];
            }
        } catch (Throwable $e) { /* DB না থাকলে চুপ থাকো (install-এর আগে) */ }
    }
    return $cache[$key] ?? $default;
}

function setSetting(string $key, string $value): void {
    DB::q('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
           ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$key, (string)$value]);
}

/* ============ Slug ============ */
function slugify(string $text): string {
    $t = trim($text);
    $t = (string)preg_replace('~[^\pL\d]+~u', '-', $t);
    $t = (string)preg_replace('~[^\x20-\x7E]~', '', $t);   // ASCII না হলে বাদ (বাংলা টাইটেল হলে fallback নিচে)
    $t = strtolower(trim((string)preg_replace('~[^a-z0-9]+~i', '-', $t), '-'));
    return ($t !== '') ? $t : 'item';
}

/* ============ Categories (অটো-মাইগ্রেশন + হেল্পার) ============ */
/**
 * পুরনো ডাটাবেজেও নতুন কলাম/টেবিল অটো তৈরি করে (প্রতি রিকোয়েস্টে একবার)।
 * install.php না চালালেও সাইট নতুন ফিচারসহ চলবে।
 */
function mv_ensure_schema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        DB::q("CREATE TABLE IF NOT EXISTS categories (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(100) NOT NULL,
            slug       VARCHAR(120) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);

        $cols  = DB::rows("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movies' AND COLUMN_NAME IN ('pinned','categories')", []);
        $have  = array_map(function ($r) { return $r['COLUMN_NAME']; }, $cols);
        if (!in_array('pinned', $have, true)) {
            DB::q('ALTER TABLE movies ADD COLUMN pinned TINYINT(1) NOT NULL DEFAULT 0 AFTER trending', []);
        }
        if (!in_array('categories', $have, true)) {
            DB::q("ALTER TABLE movies ADD COLUMN categories VARCHAR(500) NOT NULL DEFAULT '' AFTER genres", []);
        }

        if ((int)DB::val('SELECT COUNT(*) FROM categories', []) === 0) {
            foreach (['Adult', 'Serial', 'Web Series', 'Game'] as $n) {
                DB::q('INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)', [$n, slugify($n)]);
            }
        }
    } catch (Throwable $e) { /* install-এর আগে DB না থাকলে চুপ থাকো */ }
}
mv_ensure_schema();

/** সব ক্যাটাগরি (আইডি অনুযায়ী) */
function categoriesAll(): array {
    try {
        return DB::rows('SELECT * FROM categories ORDER BY id ASC', []);
    } catch (Throwable $e) { return []; }
}
function categoryBySlug(string $slug): ?array {
    try {
        return DB::row('SELECT * FROM categories WHERE slug = ? LIMIT 1', [$slug]);
    } catch (Throwable $e) { return null; }
}
/** ক্যাটাগরি-ভিত্তিক নির্দিষ্ট রঙ (থাম্বনেইল ব্যাজ + টাইলস) */
function categoryColor(string $slug): string {
    static $fixed = [
        'adult'      => '#e11d48',
        'serial'     => '#0ea5e9',
        'web-series' => '#f5a524',
        'game'       => '#22c55e',
    ];
    $s = strtolower(trim($slug));
    if (isset($fixed[$s])) return $fixed[$s];
    $pal = ['#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#ef4444'];
    $h = 0;
    foreach (str_split($s === '' ? 'x' : $s) as $ch) { $h = ($h * 31 + ord($ch)) % 1000003; }
    return $pal[$h % count($pal)];
}
/** মুভির ক্যাটাগরি লিস্ট → [{slug, name, color}] */
function movieCats(array $m): array {
    $map = [];
    foreach (categoriesAll() as $c) { $map[$c['slug']] = (string)$c['name']; }
    $out = [];
    foreach (explode(',', (string)($m['categories'] ?? '')) as $slug) {
        $slug = trim($slug);
        if ($slug === '') continue;
        $out[] = [
            'slug'  => $slug,
            'name'  => $map[$slug] ?? ucwords(str_replace('-', ' ', $slug)),
            'color' => categoryColor($slug),
        ];
    }
    return $out;
}
/** ক্যাটাগরি পেজের URL */
function categoryPageUrl(string $slug): string {
    return BASE_URL . '/category.php?cat=' . rawurlencode($slug);
}
/** ক্যাটাগরি অনুযায়ী আইটেম (পিন করা সবার আগে) */
function itemsByCat(string $catSlug, string $type = '', int $limit = 12, int $offset = 0): array {
    $sql = 'SELECT ' . MV_COLS . ' FROM movies WHERE status = ? AND FIND_IN_SET(?, categories)';
    $p   = ['published', $catSlug];
    if ($type !== '') { $sql .= ' AND type = ?'; $p[] = $type; }
    $sql .= ' ORDER BY pinned DESC, created_at DESC, id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
    return DB::rows($sql, $p);
}
function countItemsByCat(string $catSlug, string $type = ''): int {
    $sql = 'SELECT COUNT(*) FROM movies WHERE status = ? AND FIND_IN_SET(?, categories)';
    $p   = ['published', $catSlug];
    if ($type !== '') { $sql .= ' AND type = ?'; $p[] = $type; }
    return (int)DB::val($sql, $p);
}
/** হোমপেজ টাইলসের জন্য: slug → আইটেম সংখ্যা */
function categoryCounts(): array {
    $out = [];
    foreach (categoriesAll() as $c) {
        $out[$c['slug']] = countItemsByCat((string)$c['slug']);
    }
    return $out;
}

/* ============ Google Drive Helpers ============ */
/** যেকোনো ফরম্যাটের Drive লিংক থেকে FILE_ID বের করা */
function driveFileId(?string $url): string {
    $url = trim((string)$url);
    if ($url === '') return '';
    if (preg_match('~drive\.google\.com/(?:file/d/|uc\?(?:[^#]*&)?id=|open\?(?:[^#]*&)?id=)([A-Za-z0-9_-]{10,})~', $url, $m)) {
        return $m[1];
    }
    if (preg_match('~[?&]id=([A-Za-z0-9_-]{10,})~', $url, $m)) return $m[1];
    if (preg_match('~^[A-Za-z0-9_-]{10,}$~', $url)) return $url;  // শুধু ID পেস্ট করলেও চলবে
    return '';
}

/** iframe embed URL (প্লেয়ারের জন্য) */
function driveEmbedUrl(?string $url): string {
    $id = driveFileId($url);
    return $id !== '' ? 'https://drive.google.com/file/d/' . $id . '/preview' : '';
}

/** সরাসরি ডাউনলোড URL */
function driveDownloadUrl(?string $url): string {
    $id = driveFileId($url);
    return $id !== '' ? 'https://drive.google.com/uc?export=download&id=' . $id : trim((string)$url);
}

/** সরাসরি ভিডিও ফাইল কিনা (mp4/webm/m3u8...) */
function isDirectVideo(?string $url): bool {
    return (bool)preg_match('~\.(mp4|webm|ogv|ogg|m3u8|mkv)(\?|#|$)~i', trim((string)$url));
}

/* ============ URL Builders ============ */
function mediaUrl(?string $path): string {
    $p = trim((string)$path);
    if ($p === '') return '';
    if (preg_match('~^(https?:)?//~i', $p)) return $p;
    return BASE_URL . '/' . ltrim($p, '/');
}
function movieUrl(array $m): string        { return BASE_URL . '/movie.php?slug=' . rawurlencode((string)($m['slug'] ?? $m['id'])); }
function watchMovieUrl(int $id): string    { return BASE_URL . '/watch.php?id=' . $id; }
function watchEpisodeUrl(int $id): string  { return BASE_URL . '/watch.php?ep=' . $id; }
function downloadMovieUrl(int $id): string { return BASE_URL . '/download.php?id=' . $id; }
function downloadEpisodeUrl(int $id): string { return BASE_URL . '/download.php?ep=' . $id; }
function categoryUrl(string $genre): string  { return BASE_URL . '/category.php?genre=' . rawurlencode($genre); }
function searchUrl(string $q): string        { return BASE_URL . '/search.php?q=' . rawurlencode($q); }

/* ============ CSRF Protection ============ */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . esc(csrf_token()) . '">';
}
function csrf_check(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
            http_response_code(419);
            die('Security token invalid — পেজ রিফ্রেশ করে আবার চেষ্টা করুন।');
        }
    }
}

/* ============ Admin Auth ============ */
function adminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}
function currentAdmin(): ?array {
    static $admin = null;
    if (!adminLoggedIn()) return null;
    if ($admin === null) {
        $admin = DB::row('SELECT id, username FROM admin_users WHERE id = ?', [(int)$_SESSION['admin_id']]);
        if (!$admin) { unset($_SESSION['admin_id']); return null; }
    }
    return $admin;
}
function requireAdmin(): void {
    if (!adminLoggedIn() || !currentAdmin()) {
        header('Location: login.php');
        exit;
    }
}

/* ============ Uploads ============ */
function handleUpload(string $field, string $sub = 'posters'): string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return '';
    $file = $_FILES[$field];
    if (!is_uploaded_file($file['tmp_name'])) return '';
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
    if (!in_array($ext, $allowed, true)) return '';
    $dir = UPLOADS_PATH . '/' . $sub;
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    if (!is_dir($dir)) return '';
    $name = $sub . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) return '';
    return 'uploads/' . $sub . '/' . $name;   /* রিলেটিভ পাথ সেভ হয় */
}

/* ============ Ad System (সব কন্ট্রোল Admin Panel-এ) ============ */
function adOn(string $key): bool {
    return getSetting('ad_' . $key . '_on') === '1' && trim(getSetting('ad_' . $key . '_code')) !== '';
}
function adCode(string $key): string {
    return adOn($key) ? getSetting('ad_' . $key . '_code') : '';
}
/** ফ্রন্ট-এন্ড পেজে ad slot বসানোর জন্য: renderAd('header_banner') */
function renderAd(string $key, string $label = 'Advertisement'): void {
    $code = adCode($key);
    if ($code === '') return;
    echo '<div class="ad-slot" data-ad="' . esc($key) . '">';
    if (getSetting('ad_show_label', '1') === '1') {
        echo '<span class="ad-label">' . esc($label) . '</span>';
    }
    echo $code . '</div>';
}
/** Adsterra Direct Link — ১ম ক্লিকে এখানে যাবে */
function directAdLink(): string { return trim(getSetting('ad_direct_link', '')); }
/** কত মিনিটের মধ্যে ২য় ক্লিক দিলে ad ছাড়াই ঢুকবে */
function adGateExpiry(): int { return max(1, (int)getSetting('ad_gate_expiry', '30')); }

/* ============ Extra Servers (JSON) ============ */
function decodeServers(?string $json): array {
    $out = [];
    $arr = json_decode((string)$json, true);
    if (is_array($arr)) {
        foreach ($arr as $s) {
            if (!empty($s['url'])) {
                $out[] = [
                    'name' => (string)($s['name'] ?? 'Server'),
                    'type' => (($s['type'] ?? 'drive') === 'mp4') ? 'mp4' : 'drive',
                    'url'  => (string)$s['url'],
                ];
            }
        }
    }
    return $out;
}

/* ============ Movies / Series / Episodes Queries ============ */
define('MV_COLS', 'id, title, slug, type, poster, backdrop, description, year, genres, categories, quality, language, duration, rating, featured, trending, pinned, views, status, created_at');

function featuredMovies(int $limit = 5): array {
    return DB::rows('SELECT ' . MV_COLS . ' FROM movies WHERE type = ? AND status = ? AND featured = 1 ORDER BY id DESC LIMIT ' . (int)$limit, ['movie', 'published']);
}
function trendingItems(int $limit = 12): array {
    return DB::rows('SELECT ' . MV_COLS . ' FROM movies WHERE status = ? AND trending = 1 ORDER BY views DESC, id DESC LIMIT ' . (int)$limit, ['published']);
}
function latestItems(string $type, int $limit = 12, int $offset = 0): array {
    return DB::rows('SELECT ' . MV_COLS . ' FROM movies WHERE type = ? AND status = ? ORDER BY pinned DESC, created_at DESC, id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset, [$type, 'published']);
}
function countItems(string $type = '', string $genre = ''): int {
    $sql = 'SELECT COUNT(*) FROM movies WHERE status = ?';
    $p   = ['published'];
    if ($type !== '')  { $sql .= ' AND type = ?';    $p[] = $type; }
    if ($genre !== '') { $sql .= ' AND genres LIKE ?'; $p[] = '%' . $genre . '%'; }
    return (int)DB::val($sql, $p);
}
function itemsByGenre(string $genre, string $type = '', int $limit = 12, int $offset = 0): array {
    $sql = 'SELECT ' . MV_COLS . ' FROM movies WHERE status = ? AND genres LIKE ?';
    $p   = ['published', '%' . $genre . '%'];
    if ($type !== '') { $sql .= ' AND type = ?'; $p[] = $type; }
    $sql .= ' ORDER BY pinned DESC, created_at DESC, id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
    return DB::rows($sql, $p);
}
function searchItems(string $q, int $limit = 12, int $offset = 0): array {
    $like = '%' . $q . '%';
    return DB::rows('SELECT ' . MV_COLS . ' FROM movies WHERE status = ? AND (title LIKE ? OR genres LIKE ? OR year LIKE ?) ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset, ['published', $like, $like, $like]);
}
function countSearchItems(string $q): int {
    $like = '%' . $q . '%';
    return (int)DB::val('SELECT COUNT(*) FROM movies WHERE status = ? AND (title LIKE ? OR genres LIKE ? OR year LIKE ?)', ['published', $like, $like, $like]);
}
function movieBySlug(string $slug): ?array {
    return DB::row('SELECT * FROM movies WHERE slug = ? LIMIT 1', [$slug]);
}
function itemById(int $id): ?array {
    return DB::row('SELECT * FROM movies WHERE id = ? LIMIT 1', [$id]);
}
function episodeById(int $id): ?array {
    return DB::row('SELECT e.*, m.title AS series_title, m.slug AS series_slug, m.poster AS series_poster, m.genres, m.backdrop AS series_backdrop
                    FROM episodes e JOIN movies m ON m.id = e.series_id WHERE e.id = ? LIMIT 1', [$id]);
}
function episodesOfSeries(int $seriesId): array {
    return DB::rows('SELECT * FROM episodes WHERE series_id = ? ORDER BY season ASC, episode_number ASC, id ASC', [$seriesId]);
}
function relatedItems(array $movie, int $limit = 6): array {
    $genre = trim((string)explode(',', (string)($movie['genres'] ?? ''))[0]);
    if ($genre === '') return latestItems($movie['type'] ?: 'movie', $limit);
    return itemsByGenre($genre, (string)$movie['type'], $limit);
}
/** সাইটের Genre তালিকা (nav বারে দেখানোর জন্য) */
function genresList(int $limit = 12): array {
    $rows = DB::rows("SELECT genres FROM movies WHERE status = ? AND genres IS NOT NULL AND genres != ''", ['published']);
    $all = [];
    foreach ($rows as $r) {
        foreach (explode(',', (string)$r['genres']) as $g) {
            $g = trim($g);
            if ($g !== '') $all[$g] = true;
        }
    }
    $list = array_keys($all);
    sort($list, SORT_NATURAL | SORT_FLAG_CASE);
    return array_slice($list, 0, $limit);
}
/** ভিউ কাউন্টার (whitelist টেবিল) */
function incrementViews(string $table, int $id): void {
    if (!in_array($table, ['movies', 'episodes'], true) || $id < 1) return;
    DB::q('UPDATE ' . $table . ' SET views = views + 1 WHERE id = ?', [$id]);
}

/* ============ Pagination (frontend) ============ */
function renderPagination(int $page, int $pages): void {
    if ($pages <= 1) return;
    /* বর্তমান GET প্যারাম ধরে রেখে শুধু page বদলাই */
    $qs = $_GET;
    $link = function ($p) use ($qs): string {
        $qs['page'] = $p;
        return esc('?' . http_build_query($qs));
    };
    echo '<nav class="pagination">';
    if ($page > 1)  echo '<a class="pg-btn" href="' . $link($page - 1) . '">‹ Prev</a>';
    $start = max(1, $page - 2);
    $end   = min($pages, $page + 2);
    if ($start > 1) { echo '<a class="pg-btn" href="' . $link(1) . '">1</a>'; if ($start > 2) echo '<span class="pg-dots">…</span>'; }
    for ($i = $start; $i <= $end; $i++) {
        echo ($i === $page) ? '<span class="pg-btn active">' . $i . '</span>' : '<a class="pg-btn" href="' . $link($i) . '">' . $i . '</a>';
    }
    if ($end < $pages) { if ($end < $pages - 1) echo '<span class="pg-dots">…</span>'; echo '<a class="pg-btn" href="' . $link($pages) . '">' . $pages . '</a>'; }
    if ($page < $pages) echo '<a class="pg-btn" href="' . $link($page + 1) . '">Next ›</a>';
    echo '</nav>';
}
