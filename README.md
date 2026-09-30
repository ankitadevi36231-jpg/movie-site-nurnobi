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

### B. ফ্রি হোস্টিংয়ে — InfinityFree (সবচেয়ে সহজ, PHP 8.3 + MySQL + SSL ফ্রি)

**B১) অ্যাকাউন্ট:** infinityfree.com → Sign Up → ইমেইল ভেরিফাই → **Create Account** → সাবডোমেইন দিন (যেমন `movienurnobi`) → শেষে `*.infinityfreeapp.com` পাবেন

**B২) ডাটাবেজ** (কন্ট্রোল প্যানেল → **MySQL Databases**):
1. **Create Database** (নাম: `movie`) → হবে কিছু এমন: `if0_123456789_movie`
2. **Create Username** (ইউজার + পাসওয়ার্ড দিন)
3. **Add User to Database** → সব প্রিভিলেজ অনুমোদন
4. 📝 এই **৪টা** কপি করুন: **DB Name, DB User, DB Password, MySQL Host** (সেই পেজেই `SQL Host` দেখাবে, কিছু এমন `sql123.infinityfree.com` — এটা `localhost` **নয়**!)

**B৩) ফাইল আপলোড** (প্যানেল → **Online File Manager**):
1. `htdocs/` ফোল্ডারে ঢুকুন
2. `movie-site-deploy.zip` **Upload** করুন → ZIP সিলেক্ট → **Extract** → ফাইলগুলো `htdocs/`-এ সরাসরি বসবে (ভেতরের কোনো সাব-ফোল্ডারে নয়)

**B৪) config এডিট:** File Manager → `config/config.php` → Edit → **লাইন ১৪-১৭**-এর ৪টা `define`-এর **ভ্যালু** বদলান (`define(...)` অক্ষত রাখুন):
```php
define('DB_HOST', 'sql123.infinityfree.com');  // B২-এর MySQL Host
define('DB_NAME', 'if0_123456789_movie');
define('DB_USER', 'if0_123456789_user');
define('DB_PASS', 'আপনার_পাসওয়ার্ড');
```
> ⚠️ **InfinityFree নোট:** DB User আলাদা করে তৈরির দরকার নেই — **Client Area → Manage → "MySQL Details" → Show** এ ৪টাই পাবেন: Host (`sql###.infinityfree.com` — localhost না!), Username (`if0_123456789`), Password (**হোস্টিং অ্যাকাউন্টের পাস** — ক্লায়েন্ট এরিয়ার লগইন পাস না!), DB Name (prefix সহ)।



**B৫) ইনস্টল:** ব্রাউজারে `https://YOUR-SUBDOMAIN/install.php` → অ্যাডমিন ইউজার/পাস → 🚀 Install → শেষে **install.php ডিলিট**

**B৬) চালু:** `/admin/login.php` → Change Password (ডিফল্ট `admin123` বদলান) → Site Settings-এ লোগো/নাম → মুভি যোগ

> টিপস: প্যানেলে PHP **8.1+** সিলেক্ট (PHP Configuration), SSL চাইলে প্যানেলের SSL অপশন অন করুন, সাইট নিষ্ক্রিয় থাকলে পেইড হোস্টে তোলেন।

### B-বিকল্প: অন্য যেকোনো cPanel হোস্ট (000webhost / AwardSpace / পেইড)
1. প্যানেল → MySQL Databases → DB + user তৈরি (`public_html`-এর হোস্টে)
2. ZIP আপলোড → Extract → `public_html`-এ
3. `config/config.php`-তে ৪টা DB ভ্যালু বসান (উপরের মতোই)
4. `/install.php` চালান → শেষে ডিলিট

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
