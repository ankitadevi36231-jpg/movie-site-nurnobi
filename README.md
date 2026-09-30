# 🎬 MovieVerse — Movie & Web Series CMS

পেশাদার মুভি/ওয়েব সিরিজ সাইট — PHP 8 + MySQL/MariaDB, অ্যাডমিন প্যানেলসহ।

## ✨ ফিচার
- 🎥 মুভি ও ওয়েব সিরিজ (এপিসোড) ম্যানেজমেন্ট
- 📂 কাস্টম ক্যাটাগরি (Adult, Serial, Web Series, Game + নিজের মতো যোগ/ডিলিট)
- 📌 পিন করা কনটেন্ট লিস্টে সবার উপরে
- 🖼 অ্যাডমিন থেকে লোগো আপলোড, থাম্বনেইল ব্যাজ, রঙিন নেভবার
- ▶ Google Drive প্লেয়ার + এক্সট্রা সার্ভার + ডাউনলোড লিংক
- 💰 Adsterra অ্যাড গেট (ডাবল-ক্লিক) সিস্টেম
- 🔐 CSRF সুরক্ষিত অ্যাডমিন লগইন

## 🚀 ইনস্টল

### A. লোকালে (XAMPP বা PHP বাইনারি)
1. PHP + MySQL চালু রাখুন
2. `config/config.php`-তে DB সেটিংস (ডিফল্ট: root / খালি পাস)
3. ব্রাউজারে `/install.php` খুলুন → অ্যাডমিন তৈরি
4. সাইট: `/` — অ্যাডমিন: `/admin/login.php`

### B. ফ্রি হোস্টিংয়ে (InfinityFree / 000webhost / যেকোনো cPanel)
1. অ্যাকাউন্ট খুলে **MySQL ডাটাবেজ + ইউজার তৈরি** করুন (প্যানেলেই, PHP থেকে নয়)
2. `movie-site-deploy.zip` **File Manager-এ আপলোড → Extract** (`public_html/`-এ)
3. `config/config.php` এডিট করে `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` বসান
   - হোস্টিংয়ে `DB_HOST` সাধারণত `localhost` নয় — প্যানেলে যা দেখায় সেটাই (যেমন `sql123.infinityfree.com`)
4. ব্রাউজারে `https://YOUR-DOMAIN/install.php` খুলুন → অ্যাডমিন ইউজার/পাস দিন
5. ✅ শেষ হলে **`install.php` মুছে ফেলুন** (নিরাপত্তার জন্য)

> PHP **8.1+** সিলেক্ট করুন (প্যানেলে থাকলে)। প্রয়োজনীয় এক্সটেনশন: `pdo_mysql, mbstring, openssl, curl, fileinfo` — সাধারণ হোস্টিংয়ে ডিফল্ট থাকে।

### C. Vercel-এ?
Vercel-এ **PHP/MySQL চলে না** — সেখানে শুধু ব্যাখ্যা পেজ দেখাবে (`vercel.json`)। লাইভ করতে PHP হোস্টিং লাগবে।

## 📁 ফোল্ডার
```
admin/      — অ্যাডমিন প্যানেল
includes/   — হেল্পার, হেডার/ফুটার, কার্ড
assets/     — CSS / JS
config/     — config.php (DB + BASE_URL অটো)
uploads/    — পোস্টার, লোগো
install.php — ওয়ান-ক্লিক ইনস্টলার
```

## 🔑 ডিফল্ট ডেমো লগইন
`admin` / `admin123` (ডেমো — লাইভ করার সময় অবশ্যই বদলাবেন, Admin → Change Password)
