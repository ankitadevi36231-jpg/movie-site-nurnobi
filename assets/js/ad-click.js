/* ============================================================
   MovieVerse — Ad Gate System (Double Click)
   নিয়ম:
   ১ম ক্লিক  → Adsterra Direct Link নতুন ট্যাবে খোলে
   ফিরে এসে ২য় ক্লিক → আসল কনটেন্ট (Watch / Download) খোলে
   Admin Panel থেকে direct link ও expiry (মিনিট) সেট করা যায়।
   ============================================================ */
(function () {
  'use strict';
  var body = document.body;
  var directLink = (body.getAttribute('data-direct-link') || '').trim();
  var expiryMin = parseInt(body.getAttribute('data-ad-expiry') || '30', 10);
  var expiry = expiryMin * 60 * 1000;

  function hash(s) {
    var h = 5381;
    for (var i = 0; i < s.length; i++) { h = ((h << 5) + h + s.charCodeAt(i)) >>> 0; }
    return h;
  }
  function keyOf(target) { return 'mv_gate_' + hash(target); }

  function toast(msg) {
    var t = document.getElementById('mv-toast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'mv-toast';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(t._h);
    t._h = setTimeout(function () { t.classList.remove('show'); }, 4200);
  }

  function openLink(url, newTab) {
    if (newTab) { window.open(url, '_blank', 'noopener'); }
    else { window.location.href = url; }
  }

  document.addEventListener('click', function (e) {
    var el = e.target && e.target.closest ? e.target.closest('[data-gate]') : null;
    if (!el) return;
    var target = el.getAttribute('data-target');
    if (!target) return;
    var newTab = el.getAttribute('data-newtab') === '1';
    var k = keyOf(target);

    var saved = null;
    try { saved = JSON.parse(localStorage.getItem(k) || 'null'); } catch (err) { saved = null; }
    var fresh = saved && saved.t && (Date.now() - saved.t) < expiry;

    if (!fresh) {
      /* ---- ১ম ক্লিক: Ad খুলুন, ফ্ল্যাগ সেট করুন ---- */
      try { localStorage.setItem(k, JSON.stringify({ t: Date.now() })); } catch (err) {}
      if (directLink) {
        window.open(directLink, '_blank', 'noopener');
        toast('🔗 আপনার লিংক প্রস্তুত হচ্ছে… ফিরে এসে আবার একবার ক্লিক করুন!');
      } else {
        toast('👉 নিশ্চিত করতে আবার একবার ক্লিক করুন!');
      }
      e.preventDefault();
      return;
    }

    /* ---- ২য় ক্লিক: আসল কনটেন্ট ---- */
    try { localStorage.removeItem(k); } catch (err) {}
    e.preventDefault();
    openLink(target, newTab);
  });
})();
