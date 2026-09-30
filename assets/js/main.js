/* MovieVerse — UI Scripts */
(function () {
  'use strict';

  /* Mobile nav */
  var toggle = document.getElementById('navToggle');
  var nav = document.getElementById('mainNav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () { nav.classList.toggle('open'); });
  }

  /* Hero slider autoplay */
  var slides = document.querySelectorAll('.hero-slide');
  if (slides.length > 1) {
    var idx = 0;
    var dots = document.querySelectorAll('.hero-dots button');
    var timer = setInterval(function () { show(idx + 1); }, 6000);
    function show(i) {
      slides[idx].classList.remove('active');
      if (dots[idx]) dots[idx].classList.remove('active');
      idx = (i + slides.length) % slides.length;
      slides[idx].classList.add('active');
      if (dots[idx]) dots[idx].classList.add('active');
    }
    dots.forEach(function (d, i) {
      d.addEventListener('click', function () {
        clearInterval(timer);
        show(i);
        timer = setInterval(function () { show(idx + 1); }, 6000);
      });
    });
  }

  /* Watch page server switcher */
  var serverBar = document.getElementById('serverBar');
  if (serverBar) {
    var servers = window.MV_SERVERS || [];
    window.mvLoadServer = function (i, btn) {
      var s = servers[i];
      if (!s) return;
      document.querySelectorAll('.server-btn').forEach(function (b) { b.classList.remove('active'); });
      if (btn) btn.classList.add('active');
      var box = document.getElementById('playerInner');
      if (!box) return;
      box.innerHTML = '';
      if (s.type === 'mp4') {
        var v = document.createElement('video');
        v.controls = true; v.playsInline = true; v.preload = 'metadata';
        v.src = s.url;
        box.appendChild(v);
      } else {
        var f = document.createElement('iframe');
        f.className = 'drive-frame';
        f.allow = 'autoplay; fullscreen';
        f.allowFullscreen = true;
        f.setAttribute('frameborder', '0');
        f.setAttribute('scrolling', 'no');
        f.src = 'https://drive.google.com/file/d/' + encodeURIComponent(s.fileId) + '/preview';
        box.appendChild(f);
      }
    };
  }

  /* Download countdown */
  var dlBox = document.getElementById('dlBox');
  if (dlBox) {
    var sec = parseInt(dlBox.getAttribute('data-timer') || '10', 10);
    var el = document.getElementById('dlTimer');
    var t = setInterval(function () {
      sec--;
      if (el) el.textContent = sec > 0 ? sec : '0';
      if (sec <= 0) { clearInterval(t); dlBox.classList.add('ready'); }
    }, 1000);
  }

  /* Confirm dialogs (admin delete ইত্যাদি) */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f && f.getAttribute && f.getAttribute('data-confirm')) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    }
  });
})();
