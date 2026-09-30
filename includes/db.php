<?php
/**
 * MovieVerse — Database Layer (PDO / MySQL)
 * সব কুয়েরি prepared statement দিয়ে হয় → SQL Injection safe।
 */
require_once __DIR__ . '/../config/config.php';

class DB
{
    /** @var PDO|null */
    private static $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                self::fatal($e->getMessage());
            }
        }
        return self::$pdo;
    }

    /** কুয়েরি রান করে PDOStatement ফেরত দেয় */
    public static function q(string $sql, array $params = []): PDOStatement
    {
        $st = self::conn()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    /** সব রো (array of assoc) */
    public static function rows(string $sql, array $params = []): array
    {
        return self::q($sql, $params)->fetchAll();
    }

    /** প্রথম রো বা null */
    public static function row(string $sql, array $params = []): ?array
    {
        $r = self::rows($sql, $params);
        return $r ? $r[0] : null;
    }

    /** একটাই ভ্যালু (যেমন COUNT) */
    public static function val(string $sql, array $params = [])
    {
        $v = self::q($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** ডেটাবেজ কানেকশন ব্যর্থ হলে বাংলায় ফ্রেন্ডলি মেসেজ */
    private static function fatal(string $msg): void
    {
        http_response_code(500);
        $msg  = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
        $link = defined('BASE_URL') ? htmlspecialchars(BASE_URL . '/install.php', ENT_QUOTES, 'UTF-8') : 'install.php';
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Database Error</title></head>';
        echo '<body style="margin:0;font-family:Segoe UI,Arial,sans-serif;background:#0b0f17;color:#e5e9f0;display:flex;align-items:center;justify-content:center;min-height:100vh">';
        echo '<div style="text-align:center;max-width:580px;padding:24px">';
        echo '<div style="font-size:46px">⚙️</div>';
        echo '<h2 style="color:#fff;margin:10px 0">ডেটাবেজ কানেক্ট হচ্ছে না!</h2>';
        echo '<p style="color:#9aa4b2;line-height:1.8">XAMPP Control Panel থেকে <b style="color:#fff">MySQL</b> <b style="color:#4ade80">Start</b> করুন, তারপর ';
        echo '<a href="' . $link . '" style="color:#4f9cff">install.php</a> খুলে ইনস্টল করুন।</p>';
        echo '<p style="color:#5b6470;font-size:12px;word-break:break-word">' . $msg . '</p>';
        echo '</div></body></html>';
        exit;
    }
}
