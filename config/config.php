<?php
/**
 * ============================================================
 *  MovieVerse — Professional Movie & Web Series CMS
 *  Configuration  (config/config.php)
 *
 *  ✅ XAMPP localhost এবং cPanel হোস্টিং — দুই জায়গাতেই চলে।
 *  ✅ হোস্টিংয়ে আপলোড করার সময় শুধু নিচের Database
 *     সেটিংগুলো বদলালেই হবে, বাকি সব অটো-ডিটেক্ট হয়।
 * ============================================================
 */

/* ---------- ১) Database Settings (XAMPP ডিফল্ট) ---------- */
define('DB_HOST', 'localhost');
define('DB_NAME', 'movie_site');
define('DB_USER', 'root');
define('DB_PASS', '');

/* ---------- ২) Session ---------- */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ---------- ৩) Auto Base URL (কোথায় রাখলেন সেটাই ধরে নেয়) ---------- */
$mvProtocol = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
$mvHost     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$mvDocRoot  = str_replace('\\', '/', rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));
$mvProject  = str_replace('\\', '/', dirname(__DIR__));
$mvRel      = ($mvDocRoot !== '') ? trim(str_replace($mvDocRoot, '', $mvProject), '/') : '';

/* DOCUMENT_ROOT মিললো না (যেমন: php -S বা অস্বাভাবিক সেটআপ) → SCRIPT_NAME থেকে হিসাব */
if ($mvDocRoot === '' || $mvRel === $mvProject) {
    $scriptDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
    $mvRel     = trim((string)preg_replace('~/admin$~', '', $scriptDir), '/');
}

define('BASE_URL',    $mvProtocol . '://' . $mvHost . ($mvRel !== '' ? '/' . $mvRel : ''));
define('ADMIN_URL',   BASE_URL . '/admin');
define('ASSETS_URL',  BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');
define('UPLOADS_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');

/* ---------- ৪) General ---------- */
date_default_timezone_set('Asia/Dhaka');
mb_internal_encoding('UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');   /* ⚠️ সাইট লাইভ করার সময় এটা '0' করে দিবেন */
