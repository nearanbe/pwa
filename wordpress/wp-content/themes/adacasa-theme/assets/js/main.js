/* AdaCasa arayüz betikleri: slider, galeri, para birimi, favoriler, arama, kayıt formu. Bağımlılık yok. */
(function () {
  'use strict';
  var AE = window.AE || { rates: { GBP: 1 }, sym: { GBP: '£' }, msg: {}, api: '/api/v1/', lang: 'tr' };
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function setCookie(k, v) { document.cookie = k + '=' + encodeURIComponent(v) + ';path=/;max-age=31536000;SameSite=Lax'; }
  function getCookie(k) { var m = document.cookie.match('(?:^|; )' + k + '=([^;]*)'); return m ? decodeURIComponent(m[1]) : ''; }
  function store(k, v) { try { if (v === undefined) { var x = localStorage.getItem(k); return x ? JSON.parse(x) : null; } localStorage.setItem(k, JSON.stringify(v)); } catch (e) { return null; } }
  function toast(t) { var d = document.createElement('div'); d.className = 'toast'; d.setAttribute('role', 'status'); d.textContent = t; document.body.appendChild(d); setTimeout(function () { d.remove(); }, 2200); }

  /* ---- Para birimi ---- */
  function fmt2(gbp, cur) { var v = gbp * (AE.rates[cur] || 1); return (AE.sym[cur] || '') + v.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  function fmt(gbp, cur) { var v = gbp * (AE.rates[cur] || 1); return (AE.sym[cur] || '') + Math.round(v).toLocaleString('de-DE'); }
  var cur = $('#cur');
  if (cur) cur.addEventListener('change', function () {
    setCookie('ec_cur', cur.value);
    $$('.money[data-gbp]').forEach(function (el) { el.textContent = fmt(parseFloat(el.dataset.gbp), cur.value); });
    var c2 = cur.value === 'TRY' ? 'GBP' : 'TRY';
    $$('.money2[data-gbp]').forEach(function (el) { el.textContent = '(' + fmt2(parseFloat(el.dataset.gbp), c2) + ')'; });
    var h = $('#f-cur'); if (h) h.value = cur.value;
  });

  /* ---- Önbellekli sayfa: seçilen para birimini uygula ---- */
  var savedCur = getCookie('ec_cur');
  if (cur && savedCur && cur.value !== savedCur && Array.prototype.some.call(cur.options, function (o) { return o.value === savedCur; })) {
    cur.value = savedCur; cur.dispatchEvent(new Event('change'));
  }

  /* ---- Favoriler (çerez: sunucu da okuyabilsin) ---- */
  function favs() { return getCookie('ec_favs').split(',').filter(Boolean).map(Number); }
  favs().forEach(function (id) { $$('[data-fav="' + id + '"]').forEach(function (x) { x.classList.add('on'); x.setAttribute('aria-pressed', 'true'); }); });
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-fav]');
    if (!b) return;
    e.preventDefault(); e.stopPropagation();
    var id = Number(b.dataset.fav), f = favs(), on = f.indexOf(id) === -1;
    f = on ? f.concat(id) : f.filter(function (x) { return x !== id; });
    setCookie('ec_favs', f.slice(-60).join(','));
    $$('[data-fav="' + id + '"]').forEach(function (x) { x.classList.toggle('on', on); x.setAttribute('aria-pressed', on ? 'true' : 'false'); });
  });

  /* ---- Ortak kaydırıcı: hero slider ve ilan galerisi ---- */
  function slider(root, opt) {
    var slides = $$(opt.slide, root);
    if (slides.length < 2) return;
    var dots = opt.dots ? $$(opt.dots) : [];
    var i = 0, timer = null, paused = false;
    function go(n) {
      i = (n + slides.length) % slides.length;
      slides.forEach(function (s, k) { s.classList.toggle('on', k === i); s.setAttribute('aria-hidden', k === i ? 'false' : 'true'); });
      dots.forEach(function (d, k) { d.classList.toggle('on', k === i); });
      if (opt.onChange) opt.onChange(i);
    }
    function play() { stop(); if (opt.auto && !reduce && !paused) timer = setInterval(function () { go(i + 1); }, opt.auto); }
    function stop() { if (timer) clearInterval(timer); timer = null; }
    var host = opt.host || root;
    var p = $('.sarrow.prev', host), n = $('.sarrow.next', host);
    if (p) p.addEventListener('click', function () { go(i - 1); play(); });
    if (n) n.addEventListener('click', function () { go(i + 1); play(); });
    dots.forEach(function (d) { d.addEventListener('click', function () { go(Number(d.dataset.go)); play(); }); });
    host.addEventListener('mouseenter', function () { paused = true; stop(); });
    host.addEventListener('mouseleave', function () { paused = false; play(); });
    host.addEventListener('focusin', function () { paused = true; stop(); });
    host.addEventListener('focusout', function () { paused = false; play(); });
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : play(); });
    var x0 = null;
    host.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
    host.addEventListener('touchend', function (e) {
      if (x0 === null) return;
      var dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 40) { go(i + (dx < 0 ? 1 : -1)); play(); }
    });
    if (opt.keys) document.addEventListener('keydown', function (e) {
      if (e.target.closest('input,select,textarea') || $('.ov:not([hidden])')) return;
      if (e.key === 'ArrowLeft') go(i - 1); if (e.key === 'ArrowRight') go(i + 1);
    });
    go(0); play();
    return { go: go };
  }
  var hero = $('.hero');
  if (hero) slider($('#slides'), { slide: '.slide', dots: '.sdots button', auto: 6000, host: hero });
  var gal = $('#gallery');
  if (gal) {
    var thumbs = $$('#thumbs button');
    var g = slider(gal, {
      slide: '.gslide', keys: true,
      onChange: function (k) {
        var c = $('.gi', gal); if (c) c.textContent = k + 1;
        thumbs.forEach(function (t, j) { t.classList.toggle('on', j === k); });
        if (thumbs[k]) thumbs[k].scrollIntoView({ block: 'nearest', inline: 'nearest' });
      }
    });
    thumbs.forEach(function (t) { t.addEventListener('click', function () { g && g.go(Number(t.dataset.go)); }); });
  }

  /* ---- Arama kutusu ---- */
  var sb = $('#sbox');
  if (sb) {
    var actions = JSON.parse(sb.dataset.actions || '{}'), subs = JSON.parse(sb.dataset.subs || '{}'), dists = JSON.parse(sb.dataset.dists || '{}');
    var opt = function (v, t) { var o = document.createElement('option'); o.value = v; o.textContent = t; return o; };
    $$('.tabs button', sb).forEach(function (b) {
      b.addEventListener('click', function () {
        if (b.dataset.m === 'project') { location.href = actions.project; return; }
        $$('.tabs button', sb).forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-selected', x === b ? 'true' : 'false'); });
        sb.action = actions[b.dataset.m];
        // Tapu türü sadece satılıkta (kiracıyı ilgilendirmez)
        $$('.sale-only', sb).forEach(function (n) { var on = b.dataset.m === 'sale'; n.hidden = !on; n.disabled = !on; if (!on) n.value = ''; });
      });
    });
    var pt = $('#f-ptype', sb), st = $('#f-sub', sb), city = $('#f-city', sb), dist = $('#f-dist', sb);
    pt.addEventListener('change', function () {
      var first = st.options[0].textContent; st.innerHTML = ''; st.appendChild(opt('', first));
      (subs[pt.value] || []).forEach(function (s) { st.appendChild(opt(s[0], s[1])); });
    });
    city.addEventListener('change', function () {
      var first = dist.options[0].textContent; dist.innerHTML = ''; dist.appendChild(opt('', first));
      (dists[city.value] || []).forEach(function (d) { dist.appendChild(opt(d, d)); });
    });
    sb.addEventListener('submit', function (e) {
      e.preventDefault();
      // Şehir adres yoluna yazılır: /tr/satilik/girne/?ptype=... (Google için temiz link)
      var url = sb.action.replace(/\/?$/, '/') + (city.value ? city.value + '/' : '');
      var q = [];
      $$('select,input', sb).forEach(function (el) {
        if (!el.name || el.name === 'city' || !el.value || el.disabled) return;
        if (el.type === 'checkbox' && !el.checked) return;
        if (el.name === 'currency' && !$('#f-min', sb).value && !$('#f-max', sb).value) return;
        if (el.name === 'ptype' && el.value === 'konut' && !st.value) return;
        q.push(encodeURIComponent(el.name) + '=' + encodeURIComponent(el.value));
      });
      url += q.length ? '?' + q.join('&') : '';
      var label = [$('.tabs button.on', sb).textContent, st.value ? st.selectedOptions[0].textContent : pt.selectedOptions[0].textContent, city.value ? city.selectedOptions[0].textContent : '', dist.value].filter(Boolean).join(' · ');
      var r = (store('ae_recent') || []).filter(function (x) { return x.u !== url; });
      store('ae_recent', [{ t: label, u: url }].concat(r).slice(0, 3));
      location.href = url;
    });
    var rec = $('#recent', sb), list = store('ae_recent') || [];
    if (rec && list.length) {
      rec.textContent = rec.dataset.label;
      list.forEach(function (x) { var a = document.createElement('a'); a.href = x.u; a.textContent = x.t; rec.appendChild(a); });
    }
  }

  /* ---- İlan numarasıyla git ---- */
  var ino = $('#ino');
  if (ino) ino.addEventListener('submit', function (e) {
    e.preventDefault();
    var n = $('#ino-in').value.replace(/\D/g, '');
    if (!n) return;
    fetch(AE.api + 'listings/' + n + '?lang=' + AE.lang).then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) { if (d && d.url) location.href = d.url; else alert(AE.msg.notFoundNo); })
      .catch(function () { alert(AE.msg.notFoundNo); });
  });

  /* ---- İlan detay: telefon ve paylaş ---- */
  var tel = $('#telbtn');
  if (tel) tel.addEventListener('click', function () {
    var t = tel.dataset.tel;
    if (!t) { tel.textContent = AE.msg.noPhone; return; }
    if (tel.dataset.shown) { location.href = 'tel:' + t; return; }
    tel.textContent = t; tel.dataset.shown = '1';
  });
  var share = $('#sharebtn');
  if (share) share.addEventListener('click', function () {
    var u = share.dataset.url;
    if (navigator.share) { navigator.share({ title: share.dataset.title, url: u }).catch(function () {}); return; }
    if (navigator.clipboard) navigator.clipboard.writeText(u).then(function () { toast(AE.msg.copied); });
  });

  /* ---- Hızlı linkler ---- */
  $$('#qtabs button').forEach(function (b) {
    b.addEventListener('click', function () {
      $$('#qtabs button').forEach(function (x) { x.classList.toggle('on', x === b); });
      $$('.qgrid').forEach(function (g) { g.hidden = g.dataset.qg !== b.dataset.q; });
    });
  });

  /* ---- Arama sonuçları: filtre paneli ---- */
  var sf = $('#sform');
  if (sf) {
    var sfSubs = JSON.parse(sf.dataset.subs || '{}'), sfDists = JSON.parse(sf.dataset.dists || '{}');
    var mkOpt = function (v, t) { var o = document.createElement('option'); o.value = v; o.textContent = t; return o; };
    var sfPt = $('#sf-ptype', sf), sfSt = $('#sf-sub', sf), sfCity = $('#sf-city', sf), sfDist = $('#sf-dist', sf);
    sfPt.addEventListener('change', function () { var first = sfSt.options[0].textContent; sfSt.innerHTML = ''; sfSt.appendChild(mkOpt('', first)); (sfSubs[sfPt.value] || []).forEach(function (s) { sfSt.appendChild(mkOpt(s[0], s[1])); }); });
    sfCity.addEventListener('change', function () { var first = sfDist.options[0].textContent; sfDist.innerHTML = ''; sfDist.appendChild(mkOpt('', first)); (sfDists[sfCity.value] || []).forEach(function (d) { sfDist.appendChild(mkOpt(d, d)); }); });
    sf.addEventListener('submit', function (e) {
      e.preventDefault();
      // Şehir adres yoluna yazılır (/tr/satilik/girne/), boş alanlar gönderilmez
      var url = sf.dataset.base.replace(/\/?$/, '/') + (sfCity.value ? sfCity.value + '/' : ''), q = [];
      var hasPrice = sf.min.value || sf.max.value;
      $$('input,select', sf).forEach(function (el) {
        if (!el.name || el.name === 'city' || !el.value) return;
        if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
        if (el.name === 'currency' && !hasPrice) return;
        if (el.name === 'ptype' && el.value === 'konut' && !sfSt.value) return;
        q.push(encodeURIComponent(el.name).replace('%5B%5D', '[]') + '=' + encodeURIComponent(el.value));
      });
      location.href = url + (q.length ? '?' + q.join('&') : '');
    });
    // Telefonda: filtre çekmecesi
    var drawer = $('#sfilter'), bd = $('.sf-backdrop');
    var openSf = function () { drawer.classList.add('open'); bd.hidden = false; document.body.classList.add('noscroll'); var f = $('select,input', drawer); if (f) f.focus(); };
    var closeSf = function () { drawer.classList.remove('open'); bd.hidden = true; document.body.classList.remove('noscroll'); };
    $$('[data-sf-open]').forEach(function (b) { b.addEventListener('click', openSf); });
    $$('[data-sf-close]').forEach(function (b) { b.addEventListener('click', closeSf); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && drawer.classList.contains('open')) closeSf(); });
  }

  /* ---- Harita görünümü ---- */
  var rm = $('#resmap');
  if (rm && window.L) {
    var pts = JSON.parse(rm.dataset.pts || '[]');
    var map = L.map(rm, { scrollWheelZoom: false }).setView([35.25, 33.5], 9);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
    var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
    var bounds = pts.map(function (p) { return [p.lat, p.lng]; }), layer = L.layerGroup().addTo(map);
    // Yakın ilanları grupla (uzaktan bakınca fiyat etiketleri üst üste binmesin)
    var draw = function () {
      layer.clearLayers();
      var groups = [];
      pts.forEach(function (p) {
        var pt = map.latLngToLayerPoint([p.lat, p.lng]);
        var g = groups.find(function (x) { return x.pt.distanceTo(pt) < 56; });
        if (g) g.items.push(p); else groups.push({ pt: pt, items: [p] });
      });
      groups.forEach(function (g) {
        if (g.items.length === 1) {
          var p = g.items[0];
          L.marker([p.lat, p.lng], { icon: L.divIcon({ className: 'pricepin', html: '<span>' + esc(p.p) + '</span>', iconSize: null }), title: p.t }).addTo(layer)
            .bindPopup('<a class="mpop" href="' + esc(p.u) + '">' + (p.i ? '<img src="' + esc(p.i) + '" alt="">' : '') + '<b>' + esc(p.p) + '</b><span>' + esc(p.t) + '</span></a>');
        } else {
          var b = L.latLngBounds(g.items.map(function (p) { return [p.lat, p.lng]; }));
          L.marker(b.getCenter(), { icon: L.divIcon({ className: 'clusterpin', html: '<span>' + g.items.length + '</span>', iconSize: null }), title: g.items.length + '' }).addTo(layer)
            .on('click', function () { map.fitBounds(b.pad(0.4), { maxZoom: 17 }); });
        }
      });
    };
    map.on('zoomend', draw);
    if (bounds.length) map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
    draw();
  }

  /* ---- Şehir kartı: "kiralık" sayısına tıklayınca kiralık listesine git ---- */
  document.addEventListener('click', function (e) {
    var s = e.target.closest('.citycard [data-href]');
    if (s) { e.preventDefault(); location.href = s.dataset.href; }
  });

  /* ---- AdSense alanları ---- */
  if (window.adsbygoogle || document.querySelector('ins.adsbygoogle')) {
    $$('ins.adsbygoogle').forEach(function () { try { (window.adsbygoogle = window.adsbygoogle || []).push({}); } catch (e) {} });
  }
})();
