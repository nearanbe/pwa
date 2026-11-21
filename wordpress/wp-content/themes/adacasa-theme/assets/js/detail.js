/* İlan ve proje detayı (0.4): tam ekran galeri, sabit başlık + bölüm menüsü, harita ve yakın yerler,
   mesaj formu, hatalı ilan bildirimi, kişisel not, bağlantı kopyalama, taksit hesabı. Bağımlılık: Leaflet (harita). */
(function () {
  'use strict';
  var AE = window.AE || {}, D = window.AED || { t: {} }, T = D.t || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); if (v === null) localStorage.removeItem(k); else localStorage.setItem(k, v); } catch (e) { return null; } }
  function toast(t) { var d = document.createElement('div'); d.className = 'toast'; d.setAttribute('role', 'status'); d.textContent = t; document.body.appendChild(d); setTimeout(function () { d.remove(); }, 2400); }
  function el(tag, cls, html) { var e = document.createElement(tag); if (cls) e.className = cls; if (html !== undefined) e.innerHTML = html; return e; }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  /* ---------- Açılır pencereler: [data-open="id"], [data-close], Esc ---------- */
  var lastFocus = null;
  function openOv(ov) { lastFocus = document.activeElement; ov.hidden = false; document.body.classList.add('noscroll'); var f = $('input,textarea,button', ov); if (f) f.focus(); }
  function closeOv(ov) { ov.hidden = true; document.body.classList.remove('noscroll'); if (lastFocus) lastFocus.focus(); }
  document.addEventListener('click', function (e) {
    var o = e.target.closest('[data-open]');
    if (o) { var ov = document.getElementById(o.dataset.open); if (ov) { e.preventDefault(); openOv(ov); } return; }
    var c = e.target.closest('[data-close]');
    if (c) { closeOv(c.closest('.ov')); return; }
    if (e.target.classList && e.target.classList.contains('ov') && !e.target.classList.contains('lb')) closeOv(e.target);
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var ov = $('.ov:not([hidden]):not(.lb)'); if (ov) closeOv(ov); } });

  /* ---------- Tam ekran galeri ---------- */
  var lbData = (function () { var n = $('#lb-data'); try { return n ? JSON.parse(n.textContent) : []; } catch (e) { return []; } })();
  var lb = null, lbI = 0, lbTimer = null, lbList = lbData;
  function lbBuild() {
    lb = el('div', 'ov lb');
    lb.setAttribute('role', 'dialog'); lb.setAttribute('aria-modal', 'true'); lb.setAttribute('aria-label', T.gallery || 'Gallery');
    lb.innerHTML =
      '<div class="lb-top"><span class="lb-count" aria-live="polite"></span><span class="lb-btns">' +
      '<button type="button" class="lb-play" aria-label="' + esc(T.slideshow) + '" title="' + esc(T.slideshow) + '">▶</button>' +
      '<button type="button" class="lb-grid" aria-label="' + esc(T.allPhotos) + '" title="' + esc(T.allPhotos) + '">▦</button>' +
      '<button type="button" class="lb-x" aria-label="' + esc(T.close) + '">✕</button></span></div>' +
      '<div class="lb-main"><button type="button" class="lb-prev" aria-label="‹">‹</button><figure class="lb-fig"><img alt=""></figure><button type="button" class="lb-next" aria-label="›">›</button></div>' +
      '<div class="lb-side" hidden></div>';
    document.body.appendChild(lb);
    $('.lb-x', lb).addEventListener('click', lbClose);
    $('.lb-prev', lb).addEventListener('click', function () { lbGo(lbI - 1); });
    $('.lb-next', lb).addEventListener('click', function () { lbGo(lbI + 1); });
    $('.lb-play', lb).addEventListener('click', lbToggle);
    $('.lb-grid', lb).addEventListener('click', function () { var s = $('.lb-side', lb); s.hidden = !s.hidden; lb.classList.toggle('withside', !s.hidden); });
    $('.lb-side', lb).addEventListener('click', function (e) { var b = e.target.closest('button[data-i]'); if (b) { lbGo(+b.dataset.i); if (window.innerWidth < 800) { $('.lb-side', lb).hidden = true; lb.classList.remove('withside'); } } });
    var x0 = null;
    lb.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) { if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0; x0 = null; if (Math.abs(dx) > 40) lbGo(lbI + (dx < 0 ? 1 : -1)); });
    document.addEventListener('keydown', function (e) {
      if (!lb || lb.hidden) return;
      if (e.key === 'Escape') lbClose();
      if (e.key === 'ArrowLeft') lbGo(lbI - 1);
      if (e.key === 'ArrowRight') lbGo(lbI + 1);
      if (e.key === ' ') { e.preventDefault(); lbToggle(); }
    });
  }
  function lbSide() {
    $('.lb-side', lb).innerHTML = lbList.map(function (p, i) { return '<button type="button" data-i="' + i + '"><img src="' + esc(p.s || p.l) + '" alt="" loading="lazy"></button>'; }).join('');
  }
  function lbGo(i) {
    if (!lbList.length) return;
    lbI = (i + lbList.length) % lbList.length;
    var img = $('.lb-fig img', lb); img.src = lbList[lbI].l;
    $('.lb-count', lb).textContent = (lbI + 1) + ' / ' + lbList.length;
    $$('.lb-side button', lb).forEach(function (b, k) { b.classList.toggle('on', k === lbI); if (k === lbI) b.scrollIntoView({ block: 'nearest' }); });
    var multi = lbList.length > 1;
    $('.lb-prev', lb).hidden = $('.lb-next', lb).hidden = $('.lb-play', lb).hidden = $('.lb-grid', lb).hidden = !multi;
    var nx = new Image(); nx.src = lbList[(lbI + 1) % lbList.length].l; // bir sonrakini önceden yükle
  }
  function lbToggle() {
    var b = $('.lb-play', lb);
    if (lbTimer) { clearInterval(lbTimer); lbTimer = null; b.textContent = '▶'; return; }
    lbTimer = setInterval(function () { lbGo(lbI + 1); }, reduce ? 6000 : 3500); b.textContent = '❚❚';
  }
  function lbOpen(list, i) {
    if (!lb) lbBuild();
    lbList = list; lbSide(); lastFocus = document.activeElement;
    lb.hidden = false; document.body.classList.add('noscroll');
    if (window.innerWidth >= 1000 && list.length > 1) { $('.lb-side', lb).hidden = false; lb.classList.add('withside'); }
    lbGo(i || 0); $('.lb-x', lb).focus();
  }
  function lbClose() { if (lbTimer) lbToggle(); lb.hidden = true; document.body.classList.remove('noscroll'); if (lastFocus) lastFocus.focus(); }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-lightbox]');
    if (b && lbData.length) { var cur = $('#gallery .gslide.on'); var i = cur ? $$('#gallery .gslide').indexOf(cur) : 0; lbOpen(lbData, Math.max(0, i)); return; }
    var g = e.target.closest('#gallery .gslide img');
    if (g && lbData.length) { lbOpen(lbData, $$('#gallery .gslide img').indexOf(g)); return; }
    var p = e.target.closest('[data-plan]');
    if (p) { var all = $$('[data-plan]').map(function (x) { return { l: x.dataset.plan, s: (x.querySelector('img') || {}).src }; }); var uniq = []; all.forEach(function (x) { if (!uniq.some(function (u) { return u.l === x.l; })) uniq.push(x); }); lbOpen(uniq, uniq.map(function (u) { return u.l; }).indexOf(p.dataset.plan)); }
  });

  /* ---------- Sabit başlık ve bölüm menüsü ---------- */
  var sh = $('#stickhead'), head = $('#dhead');
  if (sh && head && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (en) { var show = !en[0].isIntersecting && en[0].boundingClientRect.top < 0; sh.hidden = !show; document.body.classList.toggle('has-stick', show); }, { threshold: 0 }).observe(head);
    var links = $$('.secnav a', sh), secs = links.map(function (a) { return document.getElementById(a.getAttribute('href').slice(1)); });
    var spy = new IntersectionObserver(function (en) {
      en.forEach(function (x) { if (x.isIntersecting) { var i = secs.indexOf(x.target); links.forEach(function (a, k) { a.classList.toggle('on', k === i); }); } });
    }, { rootMargin: '-40% 0px -55% 0px' });
    secs.forEach(function (s) { if (s) spy.observe(s); });
  }

  /* ---------- Harita (görünür olunca yüklenir) ---------- */
  var map = null, nbLayer = null, leafletP = null;
  function loadLeaflet() {
    if (window.L) return Promise.resolve();
    if (leafletP) return leafletP;
    leafletP = new Promise(function (res, rej) {
      var c = document.createElement('link'); c.rel = 'stylesheet'; c.href = D.leaflet.css; document.head.appendChild(c);
      var s = document.createElement('script'); s.src = D.leaflet.js; s.onload = res; s.onerror = rej; document.head.appendChild(s);
    });
    return leafletP;
  }
  function initMap(box) {
    if (box.dataset.done) return;
    if (!window.L) { loadLeaflet().then(function () { initMap(box); }); return; }
    box.dataset.done = '1';
    var ll = [parseFloat(box.dataset.lat), parseFloat(box.dataset.lng)], approx = box.dataset.approx === '1';
    map = L.map(box, { scrollWheelZoom: false }).setView(ll, approx ? 12 : 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
    if (approx) L.circle(ll, { radius: 1500, color: '#b8893f', weight: 1, fillOpacity: .12 }).addTo(map);
    else L.marker(ll, { title: box.dataset.label }).addTo(map);
    nbLayer = L.layerGroup().addTo(map);
  }
  /* ---------- Yakındaki yerler ---------- */
  function initNearby(box) {
    if (box.dataset.done) return; box.dataset.done = '1';
    fetch(AE.api + 'nearby?lat=' + box.dataset.lat + '&lng=' + box.dataset.lng + '&lang=' + AE.lang)
      .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
      .then(function (d) {
        var cats = (d.categories || []).filter(function (c) { return c.items.length; });
        var wait = $('.nb-wait', box);
        if (!cats.length) { wait.textContent = T.nearbyNone; return; }
        wait.remove();
        var tabs = el('div', 'nb-tabs'), lists = el('div', 'nb-lists');
        cats.forEach(function (c, i) {
          var b = el('button', i ? '' : 'on', '<span aria-hidden="true">' + c.icon + '</span> ' + esc(c.name) + ' <small>' + c.items.length + '</small>');
          b.type = 'button'; b.dataset.k = c.key; tabs.appendChild(b);
          var ul = el('ul', 'nb-list'); ul.dataset.k = c.key; if (i) ul.hidden = true;
          c.items.forEach(function (it) { var li = el('li', '', '<button type="button" data-ll="' + it.lat + ',' + it.lng + '"><span>' + esc(it.name) + '</span><b>' + esc(it.dist) + '</b></button>'); ul.appendChild(li); });
          lists.appendChild(ul);
        });
        box.appendChild(tabs); box.appendChild(lists);
        box.appendChild(el('p', 'sub', esc(T.nearbySource.replace('%s', (d.radius / 1000).toLocaleString(AE.lang) + ' km')) + ' · © OpenStreetMap'));
        tabs.addEventListener('click', function (e) { var b = e.target.closest('button'); if (!b) return; $$('button', tabs).forEach(function (x) { x.classList.toggle('on', x === b); }); $$('.nb-list', lists).forEach(function (u) { u.hidden = u.dataset.k !== b.dataset.k; }); });
        lists.addEventListener('click', function (e) {
          var b = e.target.closest('button[data-ll]'); if (!b || !map) return;
          var ll = b.dataset.ll.split(',').map(Number);
          nbLayer.clearLayers(); L.circleMarker(ll, { radius: 8, color: '#14284b', fillColor: '#d9bd83', fillOpacity: 1 }).bindTooltip(b.querySelector('span').textContent).addTo(nbLayer).openTooltip();
          map.fitBounds(L.latLngBounds([ll, map.getCenter()]).pad(.3));
          map.getContainer().scrollIntoView({ block: 'nearest', behavior: reduce ? 'auto' : 'smooth' });
        });
      })
      .catch(function () { var w = $('.nb-wait', box); if (w) w.textContent = T.nearbyFail; });
  }
  var lazy = $$('.dmap, .nearby');
  if (lazy.length) {
    var go = function (n) { n.classList.contains('dmap') ? initMap(n) : initNearby(n); };
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (en) { en.forEach(function (x) { if (x.isIntersecting) { go(x.target); io.unobserve(x.target); } }); }, { rootMargin: '300px' });
      lazy.forEach(function (n) { io.observe(n); });
    } else lazy.forEach(go);
  }

  /* ---------- Mesaj gönder ---------- */
  $$('form.leadform').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var m = $('.formmsg', f), btn = $('button[type=submit]', f);
      var d = { name: f.name.value.trim(), phone: f.phone.value.trim(), email: f.email.value.trim(), message: f.message.value.trim(), consent: f.consent.checked ? 1 : 0, website: f.website.value };
      var ts = $('[name="cf-turnstile-response"]', f); if (ts) d['cf-turnstile-response'] = ts.value;
      $$('.bad', f).forEach(function (x) { x.classList.remove('bad'); });
      if (!d.name) { f.name.classList.add('bad'); f.name.focus(); m.className = 'formmsg err'; m.textContent = T.leadName; return; }
      if (!d.phone && !d.email) { f.phone.classList.add('bad'); f.phone.focus(); m.className = 'formmsg err'; m.textContent = T.leadContact; return; }
      if (!d.consent) { f.consent.focus(); m.className = 'formmsg err'; m.textContent = T.leadConsent; return; }
      btn.disabled = true; m.className = 'formmsg'; m.textContent = '…';
      fetch(AE.api + f.dataset.lead + '/lead?lang=' + AE.lang, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(d) })
        .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw j; return j; }); })
        .then(function () { f.reset(); m.className = 'formmsg ok'; m.textContent = T.leadOk; btn.disabled = false; })
        .catch(function (j) { m.className = 'formmsg err'; m.textContent = (j && j.message) ? j.message : T.leadErr; btn.disabled = false; if (window.turnstile) try { window.turnstile.reset(); } catch (x) {} });
    });
  });

  /* ---------- Hatalı ilan bildir ---------- */
  $$('form.reportform').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var m = $('.formmsg', f), r = $('input[name=reason]:checked', f);
      if (!r) { m.className = 'formmsg err'; m.textContent = T.reportReason; return; }
      fetch(AE.api + 'listings/' + f.dataset.report + '/report', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ reason: r.value, note: f.note.value, email: f.email.value, website: f.website.value }) })
        .then(function (x) { return x.json().then(function (j) { if (!x.ok) throw j; }); })
        .then(function () { m.className = 'formmsg ok'; m.textContent = T.reportOk; setTimeout(function () { closeOv(f.closest('.ov')); f.reset(); m.textContent = ''; }, 1600); })
        .catch(function (j) { m.className = 'formmsg err'; m.textContent = (j && j.message) || T.leadErr; });
    });
  });

  /* ---------- Bana özel not (bu tarayıcıda saklanır) ---------- */
  var nb = $('[data-note]'), nbox = $('#mynote');
  if (nb && nbox) {
    var key = 'ae_note_' + nb.dataset.note, ta = $('textarea', nbox);
    var has = function () { nb.classList.toggle('on', !!(store(key) || '').trim()); };
    ta.value = store(key) || ''; has(); if (ta.value) nbox.hidden = false;
    nb.addEventListener('click', function () { nbox.hidden = !nbox.hidden; if (!nbox.hidden) ta.focus(); });
    var t = null; ta.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { var v = ta.value.trim(); store(key, v ? ta.value : null); has(); }, 300); });
  }

  /* ---------- Bağlantıyı kopyala ---------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]'); if (!b) return;
    var u = b.dataset.copy;
    if (navigator.clipboard) navigator.clipboard.writeText(u).then(function () { toast(AE.msg && AE.msg.copied || 'OK'); });
    else { var i = el('input'); i.value = u; document.body.appendChild(i); i.select(); document.execCommand('copy'); i.remove(); toast(AE.msg && AE.msg.copied || 'OK'); }
  });

  /* ---------- Taksit hesabı (proje) ---------- */
  var ic = $('#instcalc');
  if (ic) {
    var rates = JSON.parse(ic.dataset.rates || '{}');
    var curSel = function () { var c = document.getElementById('cur'); return c ? c.value : 'GBP'; };
    var money = function (gbp) { var c = curSel(); return (AE.sym[c] || '') + Math.round(gbp * (rates[c] || 1)).toLocaleString('de-DE'); };
    var calc = function () {
      var price = parseFloat(ic.price.value) || 0, down = Math.min(100, Math.max(0, parseFloat(ic.down.value) || 0)), n = Math.max(0, parseInt(ic.months.value, 10) || 0);
      var dp = price * down / 100, rest = price - dp;
      $('[data-o=down]', ic).textContent = money(dp);
      $('[data-o=monthly]', ic).textContent = n ? money(rest / n) + ' × ' + n : '—';
      $('[data-o=total]', ic).textContent = money(price);
    };
    ic.addEventListener('input', calc); ic.addEventListener('change', calc);
    var cs = document.getElementById('cur'); if (cs) cs.addEventListener('change', calc);
    ic.addEventListener('submit', function (e) { e.preventDefault(); });
    calc();
  }

  /* ---------- 360° tur (Pannellum; düğmeye basınca yüklenir, sayfayı yavaşlatmaz) ---------- */
  var pb = $('#panobox');
  if (pb) {
    var list = JSON.parse(pb.dataset.panos || '[]'), viewer = null, cur = 0, pnP = null;
    var loadPn = function () {
      if (window.pannellum) return Promise.resolve();
      if (pnP) return pnP;
      pnP = new Promise(function (res, rej) {
        var c = document.createElement('link'); c.rel = 'stylesheet'; c.href = D.pannellum.css; document.head.appendChild(c);
        var s = document.createElement('script'); s.src = D.pannellum.js; s.onload = res; s.onerror = rej; document.head.appendChild(s);
      });
      return pnP;
    };
    var show = function (i) {
      cur = i;
      $$('.panorooms button', pb).forEach(function (b, k) { b.classList.toggle('on', k === i); b.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
      if (!viewer) return;
      viewer.loadScene('s' + i);
    };
    var start = function () {
      var box = $('#panoview', pb), btn = $('#panostart', pb);
      btn.disabled = true; btn.querySelector('span').textContent = T.panoLoading;
      loadPn().then(function () {
        var scenes = {};
        list.forEach(function (p, i) { scenes['s' + i] = { type: 'equirectangular', panorama: p.u, title: p.t, hfov: 100, autoLoad: true }; });
        box.innerHTML = '';
        viewer = window.pannellum.viewer(box, {
          default: { firstScene: 's' + cur, sceneFadeDuration: 600, autoLoad: true, autoRotate: reduce ? 0 : -2, autoRotateInactivityDelay: 4000, compass: false, showZoomCtrl: true, showFullscreenCtrl: true, orientationOnByDefault: false },
          scenes: scenes
        });
      }).catch(function () { btn.disabled = false; btn.querySelector('span').textContent = T.panoFail; });
    };
    $('#panostart', pb).addEventListener('click', start);
    $$('.panorooms button', pb).forEach(function (b) { b.addEventListener('click', function () { var i = +b.dataset.i; if (!viewer) { cur = i; $('#panostart', pb).style.backgroundImage = 'url(' + list[i].th + ')'; start(); } show(i); }); });
  }
})();
