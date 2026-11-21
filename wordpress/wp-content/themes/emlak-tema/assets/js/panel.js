/* AdaEv Panelim: ilan sihirbazı, proje formu, profil, Telegram, ilan listesi işlemleri. Bağımlılık: Leaflet (harita). */
(function () {
  'use strict';
  var el = document.getElementById('ec-data');
  if (!el) return;
  var D = JSON.parse(el.textContent);
  var T = D.t;
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  function api(method, path, body, isForm) {
    var opt = { method: method, headers: { 'X-WP-Nonce': D.nonce }, credentials: 'same-origin' };
    if (body !== undefined) {
      if (isForm) opt.body = body;
      else { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(body); }
    }
    return fetch(D.api + path + (path.indexOf('?') > -1 ? '&' : '?') + 'lang=' + D.lang, opt).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) {
        if (!r.ok) { var e = new Error(j.message || ('HTTP ' + r.status)); e.data = j.data || {}; throw e; }
        return j;
      });
    });
  }
  function msg(node, text, ok) { if (!node) return; node.textContent = text; node.className = 'formmsg ' + (ok ? 'ok' : 'err'); }
  function money(gbp, cur, dec) {
    var v = gbp * (D.rates[cur] || 1);
    return (D.sym[cur] || '') + v.toLocaleString('de-DE', { minimumFractionDigits: dec ? 2 : 0, maximumFractionDigits: dec ? 2 : 0 });
  }
  function opt(v, t, sel) { var o = document.createElement('option'); o.value = v; o.textContent = t; if (sel) o.selected = true; return o; }
  /* Türkçe yazım: "250.000" → 250000, "1.250,50" → 1250.5, "85,5" ve "85.5" → 85.5 */
  function num(v) {
    if (v === '' || v === null || v === undefined) return null;
    var s = String(v).trim().replace(/[\s₺£€$]/g, '');
    if (s.indexOf(',') > -1) s = s.replace(/\./g, '').replace(',', '.');
    else if (/^\d{1,3}(\.\d{3})+$/.test(s)) s = s.replace(/\./g, '');
    var n = parseFloat(s); return isNaN(n) ? null : n;
  }
  function fillCities(sel, value) {
    sel.innerHTML = ''; sel.appendChild(opt('', '—'));
    Object.keys(D.cities).forEach(function (k) { sel.appendChild(opt(k, D.cities[k].name, k === value)); });
  }
  function fillDistricts(list, city) {
    list.innerHTML = '';
    ((D.cities[city] || {}).districts || []).forEach(function (d) { list.appendChild(opt(d, d)); });
  }

  /* ---------- Harita (Leaflet + OpenStreetMap) ---------- */
  function initMap(form, cityName) {
    var box = $('#map', form);
    if (!box || !window.L) return null;
    var lat = num(form.lat.value), lng = num(form.lng.value);
    var c = D.coords[cityName] || [35.25, 33.55];
    var map = L.map(box, { scrollWheelZoom: false }).setView(lat ? [lat, lng] : c, lat ? 15 : (cityName ? 12 : 9));
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    var marker = null;
    function set(ll) {
      form.lat.value = ll.lat.toFixed(5); form.lng.value = ll.lng.toFixed(5);
      if (marker) marker.setLatLng(ll); else marker = L.marker(ll, { draggable: true }).addTo(map).on('dragend', function (e) { set(e.target.getLatLng()); });
    }
    if (lat) set({ lat: lat, lng: lng });
    map.on('click', function (e) { set(e.latlng); });
    return { map: map, center: function (city) { if (!marker && D.coords[city]) map.setView(D.coords[city], 12); }, refresh: function () { setTimeout(function () { map.invalidateSize(); }, 50); } };
  }

  /* ---------- İlanlarım: durum düğmeleri ---------- */
  $$('.myrow [data-status]').forEach(function (b) {
    b.addEventListener('click', function () {
      var row = b.closest('.myrow');
      if (b.dataset.status !== 'published' && !confirm(T.confirm)) return;
      b.disabled = true;
      api('PATCH', 'listings/' + row.dataset.id, { status: b.dataset.status })
        .then(function () { location.reload(); })
        .catch(function (e) { alert(T.errSave + e.message); b.disabled = false; });
    });
  });

  /* ---------- Dil sekmeleri (ilan ve proje formu) ---------- */
  $$('.ltabs').forEach(function (tabs) {
    var scope = tabs.parentNode;
    $$('button', tabs).forEach(function (b) {
      b.addEventListener('click', function () {
        $$('button', tabs).forEach(function (x) { x.classList.toggle('on', x === b); });
        $$('.lpane', scope).forEach(function (p) { p.hidden = p.dataset.l !== b.dataset.l; });
      });
    });
  });

  /* =====================================================================
     İLAN SİHİRBAZI
     ===================================================================== */
  var F = $('#lform');
  if (F) (function () {
    var L0 = D.listing || {};
    var id = parseInt(F.dataset.id, 10) || 0;
    var steps = $$('.wstep', F).map(function (s) { return s.dataset.step; });
    var cur = 0, mapCtl = null, photos = L0.photos || [];
    var lmsg = $('#lmsg');

    // Seçenekleri doldur
    Object.keys(D.types).forEach(function (k) { F.ptype.appendChild(opt(k, D.types[k].name)); });
    function fillSub(val) {
      F.subtype.innerHTML = ''; F.subtype.appendChild(opt('', '—'));
      var sub = (D.types[F.ptype.value] || {}).sub || {};
      Object.keys(sub).forEach(function (k) { F.subtype.appendChild(opt(k, sub[k], k === val)); });
    }
    fillCities(F.city, L0.city || '');
    // Mevcut değerler
    var set = function (n, v) { if (F[n] && v !== undefined && v !== null) F[n].value = v; };
    $$('input[name=mode]', F).forEach(function (r) { r.checked = r.value === (L0.mode || 'sale'); });
    set('ptype', L0.ptype || 'konut'); fillSub(L0.subtype);
    set('price', L0.price_raw ? String(L0.price_raw).replace(/\.0+$/, '') : '');
    set('currency', L0.currency || 'GBP');
    ['area_m2', 'rooms', 'baths', 'district', 'floor', 'total_floors', 'year_built', 'sea_distance_m', 'payment_plan', 'video_url', 'tour_url', 'lat', 'lng'].forEach(function (k) { set(k, L0[k]); });
    ['furnished', 'vat_paid', 'transformer_paid'].forEach(function (k) { if (L0[k] !== undefined && L0[k] !== null) F[k].value = L0[k] ? '1' : '0'; });
    $$('input[name=deed_type]', F).forEach(function (r) { r.checked = r.value === L0.deed_type; });
    $$('input[name=features]', F).forEach(function (c) { c.checked = (L0.features || []).indexOf(c.value) > -1; });
    ['tr', 'en', 'ru'].forEach(function (l) { var t = (L0.i18n || {})[l] || {}; set('title_' + l, t.title || ''); set('desc_' + l, t.description || ''); });
    fillDistricts($('#distlist'), F.city.value);

    function mode() { var r = $('input[name=mode]:checked', F); return r ? r.value : 'sale'; }
    function syncMode() {
      var sale = mode() === 'sale';
      $$('.sale-only', F).forEach(function (n) { n.hidden = !sale; });
      $$('.req-sale', F).forEach(function (n) { n.hidden = !sale; });
      $$('.segbtn', F).forEach(function (l) { l.classList.toggle('on', $('input', l).checked); });
    }
    $$('input[name=mode]', F).forEach(function (r) { r.addEventListener('change', function () { syncMode(); live(); }); });
    F.ptype.addEventListener('change', function () { fillSub(''); });
    F.city.addEventListener('change', function () { fillDistricts($('#distlist'), F.city.value); if (mapCtl) mapCtl.center(F.city.value); live(); });
    syncMode();

    // Canlı fiyat karşılığı + bölge karşılaştırması
    var cmpTimer = null;
    function live() {
      var p = num(F.price.value), c = F.currency.value, eq = $('#equiv'), cmp = $('#cmp');
      if (!p) { eq.textContent = '—'; cmp.textContent = ''; return; }
      var gbp = p / (D.rates[c] || 1);
      eq.textContent = Object.keys(D.rates).filter(function (k) { return k !== c; }).map(function (k) { return money(gbp, k, k === 'TRY'); }).join(' · ');
      clearTimeout(cmpTimer);
      var area = num(F.area_m2.value);
      if (!F.city.value || !area || F.ptype.value !== 'konut' || mode() === 'daily') { cmp.textContent = ''; return; }
      cmpTimer = setTimeout(function () {
        api('GET', 'index/compare?city=' + F.city.value + '&mode=' + mode() + '&ptype=konut&area_m2=' + area + '&price=' + p + '&currency=' + c)
          .then(function (r) {
            if (r.diff === null || r.diff === undefined) { cmp.textContent = ''; return; }
            var city = D.cities[F.city.value].name, n = Math.abs(Math.round(r.diff)), pct = D.lang === 'tr' ? '%' + n : n + '%';
            var t = Math.abs(r.diff) < 3 ? T.equal : (r.diff < 0 ? T.below : T.above);
            cmp.textContent = t.replace('%c', city).replace('%p', pct);
            cmp.className = 'sub cmpline ' + (r.diff < -3 ? 'below' : r.diff > 3 ? 'above' : '');
          }).catch(function () {});
      }, 400);
    }
    ['price', 'currency', 'area_m2'].forEach(function (n) { F[n].addEventListener('input', live); F[n].addEventListener('change', live); });
    live();

    // Veri topla
    function collect() {
      var i18n = {};
      ['tr', 'en', 'ru'].forEach(function (l) {
        var t = F['title_' + l].value.trim();
        if (t) i18n[l] = { title: t, description: F['desc_' + l].value.trim() };
      });
      var deed = mode() === 'sale' ? $('input[name=deed_type]:checked', F) : null; // tapu sadece satılıkta
      var tri = function (n) { return F[n].value === '' ? null : F[n].value === '1'; };
      return {
        mode: mode(), ptype: F.ptype.value, subtype: F.subtype.value || null,
        price: num(F.price.value), currency: F.currency.value,
        area_m2: num(F.area_m2.value), rooms: F.rooms.value || null, baths: num(F.baths.value),
        city: F.city.value, district: F.district.value.trim(), lat: num(F.lat.value), lng: num(F.lng.value),
        deed_type: deed ? deed.value : null, furnished: tri('furnished'),
        vat_paid: mode() === 'sale' ? tri('vat_paid') : null, transformer_paid: mode() === 'sale' ? tri('transformer_paid') : null,
        floor: F.floor.value.trim(), total_floors: num(F.total_floors.value), year_built: num(F.year_built.value), sea_distance_m: num(F.sea_distance_m.value),
        payment_plan: mode() === 'sale' ? F.payment_plan.value.trim() : '',
        features: $$('input[name=features]:checked', F).map(function (c) { return c.value; }),
        video_url: F.video_url.value.trim(), tour_url: F.tour_url.value.trim(),
        i18n: i18n, source: 'panel'
      };
    }

    // Adım doğrulama
    function invalid(node) { node.classList.add('bad'); node.focus(); msg(lmsg, T.required, false); return false; }
    function validate(step) {
      $$('.bad', F).forEach(function (n) { n.classList.remove('bad'); });
      if (step === 'basics') {
        if (!num(F.price.value)) return invalid(F.price);
      }
      if (step === 'location' && !F.city.value) return invalid(F.city);
      if (step === 'details' && mode() === 'sale' && !$('input[name=deed_type]:checked', F)) {
        $('.deedbox', F).classList.add('bad'); $('.deedbox', F).scrollIntoView({ behavior: 'smooth', block: 'center' }); msg(lmsg, T.deedRequired, false); return false;
      }
      if (step === 'text' && !F.title_tr.value.trim() && !F.title_en.value.trim() && !F.title_ru.value.trim()) return invalid(F.title_tr);
      msg(lmsg, '', true);
      return true;
    }

    // Kaydet (yeni → POST, var → PATCH)
    // Benzer / kopya ilan uyarısı (fotoğraf ve açıklama karşılaştırması sunucuda)
    function checkDup() {
      if (!id) return;
      api('GET', 'listings/' + id + '/duplicates').then(function (r) {
        var w = $('#dupwarn'); if (!w) return;
        if (!r.items || !r.items.length) { w.hidden = true; return; }
        w.innerHTML = ''; w.appendChild(document.createTextNode('⚠️ ' + r.message + ' '));
        r.items.slice(0, 3).forEach(function (d) { var a = document.createElement('a'); a.href = d.url; a.target = '_blank'; a.textContent = '#' + d.id + ' ↗'; w.appendChild(a); w.appendChild(document.createTextNode(' ')); });
        w.hidden = false;
      }).catch(function () {});
    }
    function save(status) {
      var body = collect();
      if (status) body.status = status;
      var req = id ? api('PATCH', 'listings/' + id, body) : api('POST', 'listings', Object.assign(body, { status: status || 'draft' }));
      return req.then(function (r) {
        setTimeout(checkDup, 300);
        id = r.id; F.dataset.id = id;
        if (history.replaceState) history.replaceState(null, '', D.panel + (D.panel.indexOf('?') > -1 ? '&' : '?') + 'bolum=ilan&id=' + id);
        return r;
      }).catch(function (e) {
        var f = (e.data && e.data.fields) ? ' (' + Object.keys(e.data.fields).join(', ') + ')' : '';
        msg(lmsg, T.errSave + e.message + f, false);
        throw e;
      });
    }

    function show(i) {
      cur = Math.max(0, Math.min(steps.length - 1, i));
      $$('.wstep', F).forEach(function (s, k) { s.classList.toggle('on', k === cur); });
      $$('.wsteps li', F).forEach(function (li, k) { li.classList.toggle('on', k === cur); li.classList.toggle('done', k < cur); });
      $('#wprev').hidden = cur === 0;
      $('#wnext').hidden = cur === steps.length - 1;
      $('#wpublish').hidden = cur !== steps.length - 1;
      if (steps[cur] === 'location') { if (!mapCtl) mapCtl = initMap(F, F.city.value); else mapCtl.refresh(); }
      if (steps[cur] === 'preview') preview();
      F.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function next() {
      if (!validate(steps[cur])) return;
      // Fotoğraf adımına geçmeden önce taslak olarak kaydet (fotoğraflar ilana bağlanır)
      if (steps[cur + 1] === 'photos' && (!id || true)) {
        var b = $('#wnext'); b.disabled = true;
        save(id ? undefined : 'draft').then(function () { b.disabled = false; renderPhotos(); show(cur + 1); }).catch(function () { b.disabled = false; });
        return;
      }
      show(cur + 1);
    }
    $('#wnext').addEventListener('click', next);
    $('#wprev').addEventListener('click', function () { show(cur - 1); });
    $$('.wsteps li', F).forEach(function (li, k) {
      li.addEventListener('click', function () {
        if (k <= cur) { show(k); return; }
        for (var s = cur; s < k; s++) { if (!validate(steps[s])) { show(s); return; } }
        if (k >= steps.indexOf('photos') && !id) { save('draft').then(function () { renderPhotos(); show(k); }); return; }
        show(k);
      });
    });
    $('#wdraft').addEventListener('click', function () {
      if (!validate('text') && !F.title_tr.value.trim()) { show(steps.indexOf('text')); return; }
      save('draft').then(function () { msg(lmsg, T.saved + ' ✓', true); });
    });
    $('#wpublish').addEventListener('click', function () {
      for (var s = 0; s < steps.length; s++) { if (!validate(steps[s])) { show(s); return; } }
      var b = this; b.disabled = true;
      save('published').then(function (r) {
        msg(lmsg, r.status === 'published' ? T.liveAfter : T.pendingAfter, true);
        setTimeout(function () { location.href = D.panel + (D.panel.indexOf('?') > -1 ? '&' : '?') + 'bolum=ilanlar'; }, 1400);
      }).catch(function () { b.disabled = false; });
    });

    // Fotoğraflar
    var list = $('#photos');
    function renderPhotos() {
      list.innerHTML = '';
      photos.forEach(function (p, i) {
        var li = document.createElement('li');
        li.draggable = true; li.dataset.id = p.id;
        li.innerHTML = '<img src="' + p.small + '" alt="">' + (i === 0 ? '<span class="covertag">' + T.cover + '</span>' : '') +
          '<div class="pbtns"><button type="button" data-a="left" aria-label="◀">◀</button><button type="button" data-a="cover" aria-label="★">★</button><button type="button" data-a="right" aria-label="▶">▶</button><button type="button" data-a="del" aria-label="✕">✕</button></div>';
        list.appendChild(li);
      });
    }
    function saveOrder() { return api('PUT', 'listings/' + id + '/photos/order', { order: photos.map(function (p) { return p.id; }) }); }
    list.addEventListener('click', function (e) {
      var b = e.target.closest('button[data-a]'); if (!b) return;
      var li = b.closest('li'), i = photos.findIndex(function (p) { return String(p.id) === li.dataset.id; });
      var a = b.dataset.a;
      if (a === 'del') {
        if (!confirm(T.confirm)) return;
        api('DELETE', 'listings/' + id + '/photos/' + photos[i].id).then(function () { photos.splice(i, 1); renderPhotos(); });
        return;
      }
      var j = a === 'left' ? i - 1 : a === 'right' ? i + 1 : 0;
      if (j < 0 || j >= photos.length || j === i) return;
      var p = photos.splice(i, 1)[0]; photos.splice(j, 0, p); renderPhotos(); saveOrder();
    });
    var dragId = null;
    list.addEventListener('dragstart', function (e) { var li = e.target.closest('li'); dragId = li && li.dataset.id; });
    list.addEventListener('dragover', function (e) { e.preventDefault(); });
    list.addEventListener('drop', function (e) {
      e.preventDefault();
      var li = e.target.closest('li'); if (!li || !dragId || li.dataset.id === dragId) return;
      var from = photos.findIndex(function (p) { return String(p.id) === dragId; }), to = photos.findIndex(function (p) { return String(p.id) === li.dataset.id; });
      var p = photos.splice(from, 1)[0]; photos.splice(to, 0, p); renderPhotos(); saveOrder();
    });
    // 360° fotoğraflar
    var panos = (L0.panos || []).slice(), plist = $('#panolist');
    function renderPanos() {
      if (!plist) return;
      plist.innerHTML = '';
      panos.forEach(function (p, i) {
        var li = document.createElement('li'); li.dataset.id = p.id;
        li.innerHTML = '<img src="' + p.thumb + '" alt=""><input value="" maxlength="60" placeholder="' + T.panoRoom.replace('%s', i + 1) + '"><button type="button" class="ghost sm" data-pa="del" aria-label="✕">✕</button>';
        $('input', li).value = p.title || '';
        $('input', li).addEventListener('change', function (e) { api('PATCH', 'listings/' + id + '/panos/' + p.id, { title: e.target.value }); p.title = e.target.value; });
        $('button', li).addEventListener('click', function () { if (!confirm(T.confirm)) return; api('DELETE', 'listings/' + id + '/panos/' + p.id).then(function () { panos.splice(panos.indexOf(p), 1); renderPanos(); }); });
        plist.appendChild(li);
      });
    }
    function isPano(file) {
      return new Promise(function (res) {
        var u = URL.createObjectURL(file), im = new Image();
        im.onload = function () { URL.revokeObjectURL(u); res(Math.abs(im.naturalWidth / im.naturalHeight - 2) < 0.06 && im.naturalWidth >= 1600); };
        im.onerror = function () { URL.revokeObjectURL(u); res(false); };
        im.src = u;
      });
    }
    function uploadPanos(files) {
      files = Array.prototype.slice.call(files).filter(function (f) { return /^image\/(jpeg|png|webp)$/.test(f.type); });
      if (!files.length || !id) return Promise.resolve();
      var i = 0;
      return new Promise(function (done) {
        (function nextPano() {
          if (i >= files.length) { msg(lmsg, T.saved + ' ✓', true); done(); return; }
          msg(lmsg, '🌐 ' + T.uploading + ' ' + (i + 1) + '/' + files.length, true);
          var fd = new FormData(); fd.append('pano', files[i++]);
          api('POST', 'listings/' + id + '/panos', fd, true)
            .then(function (p) { panos.push(p); renderPanos(); nextPano(); })
            .catch(function (e) { msg(lmsg, e.message, false); nextPano(); });
        })();
      });
    }
    var pf = $('#panofiles'); if (pf) pf.addEventListener('change', function () { uploadPanos(pf.files); pf.value = ''; });
    renderPanos();

    function upload(files) {
      files = Array.prototype.slice.call(files).filter(function (f) { return /^image\/(jpeg|png|webp)$/.test(f.type); });
      if (!files.length || !id) return;
      // 2:1 oranlı (360°) fotoğrafları normal galeriye değil 360° bölümüne gönder
      Promise.all(files.map(isPano)).then(function (flags) {
        var pn = files.filter(function (f, k) { return flags[k]; });
        var normal = files.filter(function (f, k) { return !flags[k]; });
        (pn.length ? uploadPanos(pn) : Promise.resolve()).then(function () { if (normal.length) uploadNormal(normal); });
      });
    }
    function uploadNormal(files) {
      var i = 0;
      (function nextFile() {
        if (i >= files.length) { msg(lmsg, T.saved + ' ✓', true); checkDup(); return; }
        msg(lmsg, T.uploading + ' ' + (i + 1) + '/' + files.length, true);
        var fd = new FormData(); fd.append('photos[]', files[i++]);
        api('POST', 'listings/' + id + '/photos', fd, true)
          .then(function (r) { (r.added || []).forEach(function (p) { photos.push(p); }); renderPhotos(); nextFile(); })
          .catch(function (e) { msg(lmsg, T.errSave + e.message, false); nextFile(); });
      })();
    }
    $('#photofiles').addEventListener('change', function () { upload(this.files); this.value = ''; });
    var drop = $('#drop');
    ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { if (e.dataTransfer && e.dataTransfer.types.indexOf('Files') > -1) { e.preventDefault(); drop.classList.add('over'); } }); });
    ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.remove('over'); }); });
    drop.addEventListener('drop', function (e) { if (e.dataTransfer.files.length) { e.preventDefault(); upload(e.dataTransfer.files); } });

    // Önizleme
    function preview() {
      var d = collect(), pv = $('#pv');
      var gbp = (d.price || 0) / (D.rates[d.currency] || 1);
      var suffix = d.mode === 'rent' ? T.perMonth : d.mode === 'daily' ? T.perNight : '';
      var title = (d.i18n[D.lang] || d.i18n.tr || d.i18n.en || d.i18n.ru || {}).title || '';
      var rows = [
        [T.mode[d.mode], ((D.types[d.ptype] || {}).sub || {})[d.subtype] || (D.types[d.ptype] || {}).name],
        [D.cities[d.city] ? D.cities[d.city].name : '', d.district],
        [d.area_m2 ? d.area_m2 + ' m²' : '', d.rooms || ''],
        [d.deed_type ? D.deeds[d.deed_type].name : '', '']
      ];
      pv.innerHTML = '';
      var img = document.createElement('div'); img.className = 'pvimg';
      if (photos[0]) img.innerHTML = '<img src="' + photos[0].small + '" alt="">';
      pv.appendChild(img);
      var b = document.createElement('div');
      var h = document.createElement('h3'); h.textContent = title; b.appendChild(h);
      var pr = document.createElement('div'); pr.className = 'price'; pr.textContent = money(gbp, d.currency === 'TRY' ? 'TRY' : d.currency) + suffix; b.appendChild(pr);
      rows.forEach(function (r) { var s = document.createElement('div'); s.className = 'sub'; s.textContent = r.filter(Boolean).join(' · '); if (s.textContent) b.appendChild(s); });
      var n = document.createElement('div'); n.className = 'sub'; n.textContent = photos.length + ' 📷 · ' + Object.keys(d.i18n).join(' / ').toUpperCase(); b.appendChild(n);
      pv.appendChild(b);
    }

    if (id) { renderPhotos(); checkDup(); }
    show(0);
  })();

  /* =====================================================================
     PROJE FORMU
     ===================================================================== */
  var P = $('#pform');
  if (P) (function () {
    var P0 = D.project || {};
    var id = parseInt(P.dataset.id, 10) || 0;
    var pmsg = $('#pmsg');
    var media = P0.media || { photo: [], plan: [], brochure: [] };
    fillCities(P.city, P0.city || '');
    fillDistricts($('#pdistlist'), P.city.value);
    P.city.addEventListener('change', function () { fillDistricts($('#pdistlist'), P.city.value); if (mapCtl) mapCtl.center(P.city.value); });
    var set = function (n, v) { if (P[n] && v !== undefined && v !== null) P[n].value = v; };
    ['name', 'developer', 'district', 'units', 'project_type', 'build_status', 'land_m2', 'price_min_gbp', 'price_max_gbp', 'payment_plan', 'down_payment_pct', 'installments', 'progress', 'video_url', 'lat', 'lng'].forEach(function (k) { set(k, P0[k]); });
    if (P0.delivery_date) set('delivery_date', P0.delivery_date.slice(0, 7));
    ['tr', 'en', 'ru'].forEach(function (l) { set('desc_' + l, (P0.desc || {})[l] || ''); });
    $$('input[name=amenities]', P).forEach(function (c) { c.checked = (P0.amenities || []).indexOf(c.value) > -1; });
    var mapCtl = initMap(P, P.city.value);

    // Daire tipleri tablosu
    var tbody = $('#units tbody');
    function addRow(u) {
      u = u || {};
      var tr = document.createElement('tr');
      tr.innerHTML = '<td><input data-k="type" placeholder="2+1"></td><td class="m2"><input data-k="m2_min" inputmode="numeric" placeholder="85"> – <input data-k="m2_max" inputmode="numeric" placeholder="105"></td><td><input data-k="price_from_gbp" inputmode="numeric" placeholder="120000"></td><td><input data-k="available" inputmode="numeric" placeholder="5"></td><td><button type="button" class="ghost sm" aria-label="✕">✕</button></td>';
      $$('input', tr).forEach(function (i) { if (u[i.dataset.k] !== undefined && u[i.dataset.k] !== null) i.value = u[i.dataset.k]; });
      $('button', tr).addEventListener('click', function () { tr.remove(); calc(); });
      $$('input', tr).forEach(function (i) { i.addEventListener('input', calc); });
      tbody.appendChild(tr);
    }
    (P0.unit_types && P0.unit_types.length ? P0.unit_types : [{}]).forEach(addRow);
    $('#addunit').addEventListener('click', function () { addRow(); });
    function units() {
      return $$('tr', tbody).map(function (tr) {
        var o = {}; $$('input', tr).forEach(function (i) { o[i.dataset.k] = i.dataset.k === 'type' ? i.value.trim() : num(i.value); }); return o;
      }).filter(function (u) { return u.type; });
    }

    // Taksit hesabı ve ilerleme
    function calc() {
      var down = num(P.down_payment_pct.value), n = num(P.installments.value);
      var prices = units().map(function (u) { return u.price_from_gbp; }).filter(Boolean);
      var price = prices.length ? Math.min.apply(null, prices) : num(P.price_min_gbp.value);
      var out = $('#calc');
      if (!price || down === null || !n) { out.textContent = ''; return; }
      out.textContent = T.calc.replace('%1', money(price, 'GBP')).replace('%2', money(price * down / 100, 'GBP')).replace('%3', money(price * (100 - down) / 100 / n, 'GBP'));
    }
    ['down_payment_pct', 'installments', 'price_min_gbp'].forEach(function (k) { P[k].addEventListener('input', calc); });
    calc();
    var pv = $('#progv'); function prog() { pv.textContent = P.progress.value + '%'; } P.progress.addEventListener('input', prog); prog();

    function collect() {
      var d = {
        name: P.name.value.trim(), developer: P.developer.value.trim(), city: P.city.value, district: P.district.value.trim(),
        delivery_date: P.delivery_date.value, units: num(P.units.value), project_type: P.project_type.value, build_status: P.build_status.value, land_m2: num(P.land_m2.value), price_min_gbp: num(P.price_min_gbp.value), price_max_gbp: num(P.price_max_gbp.value),
        payment_plan: P.payment_plan.value.trim(), down_payment_pct: num(P.down_payment_pct.value), installments: num(P.installments.value),
        progress: num(P.progress.value), video_url: P.video_url.value.trim(), lat: num(P.lat.value), lng: num(P.lng.value),
        amenities: $$('input[name=amenities]:checked', P).map(function (c) { return c.value; }), unit_types: units()
      };
      ['tr', 'en', 'ru'].forEach(function (l) { d['desc_' + l] = P['desc_' + l].value.trim(); });
      return d;
    }
    $$('[data-save]', P).forEach(function (b) {
      b.addEventListener('click', function () {
        if (!P.name.value.trim()) { P.name.classList.add('bad'); P.name.focus(); msg(pmsg, T.required, false); return; }
        if (!P.city.value) { P.city.classList.add('bad'); P.city.focus(); msg(pmsg, T.required, false); return; }
        var d = collect(); d.status = b.dataset.save;
        b.disabled = true;
        (id ? api('PATCH', 'projects/' + id, d) : api('POST', 'projects', d)).then(function (r) {
          id = r.id; P.dataset.id = id;
          if (history.replaceState) history.replaceState(null, '', D.panel + (D.panel.indexOf('?') > -1 ? '&' : '?') + 'bolum=proje&id=' + id);
          msg(pmsg, (r.status === 'pending' ? T.pendingAfter : r.status === 'published' ? T.liveAfter : T.saved) + ' ✓', true);
          showMedia();
        }).catch(function (e) { msg(pmsg, T.errSave + e.message, false); }).finally(function () { b.disabled = false; });
      });
    });

    // Medya (kayıttan sonra)
    function showMedia() {
      if (!id) return;
      $('#pmedia-wait').hidden = true; $('#pmedia-body').hidden = false;
      var cov = $('#coverprev');
      cov.innerHTML = P0.cover ? '<img src="' + P0.cover.small + '" alt="">' : '';
      ['photo', 'plan'].forEach(function (k) {
        var ul = $('[data-list=' + k + ']', P); ul.innerHTML = '';
        (media[k] || []).forEach(function (m) {
          var li = document.createElement('li'); li.innerHTML = '<img src="' + m.small + '" alt=""><div class="pbtns"><button type="button" aria-label="✕">✕</button></div>';
          $('button', li).addEventListener('click', function () { if (confirm(T.confirm)) api('DELETE', 'projects/' + id + '/media/' + m.id).then(function () { media[k] = media[k].filter(function (x) { return x.id !== m.id; }); showMedia(); }); });
          ul.appendChild(li);
        });
      });
      var fl = $('[data-list=brochure]', P); fl.innerHTML = '';
      (media.brochure || []).forEach(function (m) {
        var li = document.createElement('li'); var a = document.createElement('a'); a.href = m.url; a.target = '_blank'; a.textContent = '📄 ' + (m.title || 'PDF'); li.appendChild(a);
        var x = document.createElement('button'); x.type = 'button'; x.className = 'ghost sm'; x.textContent = '✕';
        x.addEventListener('click', function () { if (confirm(T.confirm)) api('DELETE', 'projects/' + id + '/media/' + m.id).then(function () { media.brochure = media.brochure.filter(function (y) { return y.id !== m.id; }); showMedia(); }); });
        li.appendChild(x); fl.appendChild(li);
      });
    }
    $$('#pmedia input[type=file]').forEach(function (inp) {
      inp.addEventListener('change', function () {
        var files = Array.prototype.slice.call(inp.files), kind = inp.dataset.kind, i = 0;
        inp.value = '';
        (function nextFile() {
          if (i >= files.length) { msg(pmsg, T.saved + ' ✓', true); showMedia(); return; }
          msg(pmsg, T.uploading + ' ' + (i + 1) + '/' + files.length, true);
          var fd = new FormData();
          var f = files[i++];
          if (kind === 'cover') {
            fd.append('cover', f);
            api('POST', 'projects/' + id + '/cover', fd, true).then(function (r) { P0.cover = r.cover; nextFile(); }).catch(function (e) { msg(pmsg, T.errSave + e.message, false); });
          } else {
            fd.append('file', f); fd.append('kind', kind); fd.append('title', f.name.replace(/\.[a-z]+$/i, ''));
            api('POST', 'projects/' + id + '/media', fd, true).then(function (r) { (media[kind] = media[kind] || []).push(r); nextFile(); }).catch(function (e) { msg(pmsg, T.errSave + e.message, false); nextFile(); });
          }
        })();
      });
    });
    showMedia();
  })();

  /* =====================================================================
     PROFİL
     ===================================================================== */
  var PF = $('#profileform');
  if (PF) {
    PF.addEventListener('submit', function (e) {
      e.preventDefault();
      var d = { socials: {} };
      $$('input,select,textarea', PF).forEach(function (i) {
        if (!i.name || i.type === 'file') return;
        var m = i.name.match(/^socials\[(\w+)\]$/);
        if (m) d.socials[m[1]] = i.value.trim(); else d[i.name] = i.value.trim();
      });
      var b = $('button[type=submit]', PF); b.disabled = true;
      api('PATCH', 'me', d).then(function (r) { if (r.slug) PF.slug.value = r.slug; msg($('.formmsg', PF), T.saved + ' ✓', true); })
        .catch(function (er) { msg($('.formmsg', PF), T.errSave + er.message, false); }).finally(function () { b.disabled = false; });
    });
    $('#logofile').addEventListener('change', function () {
      if (!this.files[0]) return;
      var fd = new FormData(); fd.append('logo', this.files[0]);
      msg($('.formmsg', PF), T.uploading, true);
      api('POST', 'me/logo', fd, true).then(function (r) { $('#logoprev').innerHTML = '<img src="' + r.logo.small + '" alt="">'; msg($('.formmsg', PF), T.saved + ' ✓', true); })
        .catch(function (er) { msg($('.formmsg', PF), T.errSave + er.message, false); });
      this.value = '';
    });
  }

  /* =====================================================================
     TELEGRAM
     ===================================================================== */
  var TG = $('#tgbox');
  if (TG) {
    $$('input[name=telegram_opt_in]', TG).forEach(function (r) {
      r.addEventListener('change', function () {
        $('#tgmore').hidden = r.value !== '1';
        api('PATCH', 'me', { telegram_opt_in: r.value === '1' ? 1 : 0 }).then(function () { msg($('.formmsg', TG), T.saved + ' ✓', true); })
          .catch(function (er) { msg($('.formmsg', TG), T.errSave + er.message, false); });
      });
    });
    var c = $('#tgconnect') || $('#tgrelink');
    if (c) c.addEventListener('click', function () {
      api('POST', 'me/telegram-code').then(function (r) {
        var out = $('#tgout');
        if (r.link) { out.innerHTML = ''; var a = document.createElement('a'); a.href = r.link; a.target = '_blank'; a.rel = 'noopener'; a.className = 'btn'; a.textContent = '✈ t.me/' + TG.dataset.bot; out.appendChild(a); var s = document.createElement('p'); s.textContent = T.tgHint; out.appendChild(s); window.open(r.link, '_blank'); }
        else out.textContent = T.tgSoon;
      });
    });
  }

  /* Talepler: durum değiştir */
  $$('[data-lead-st]').forEach(function (b) {
    b.addEventListener('click', function () {
      var row = b.closest('[data-lead]'); b.disabled = true;
      api('PATCH', 'me/leads/' + row.dataset.lead, { status: b.dataset.leadSt })
        .then(function () { if (b.dataset.leadSt === 'spam') row.remove(); else location.reload(); })
        .catch(function (e) { alert(T.errSave + e.message); b.disabled = false; });
    });
  });

  /* Sosyal medya ayarları ve "Paylaş" */
  var SB = $('#socialbox');
  if (SB) $('#sosave', SB).addEventListener('click', function () {
    var m = $('.formmsg', SB);
    api('PATCH', 'me/social', { auto: $('[name=auto]', SB).checked, lang: $('[name=lang]', SB).value, sign: $('[name=sign]', SB).value })
      .then(function () { msg(m, T.saved + ' ✓', true); }).catch(function (e) { msg(m, T.errSave + e.message, false); });
  });
  $$('[data-share]').forEach(function (b) {
    b.addEventListener('click', function () {
      b.disabled = true;
      api('POST', 'listings/' + b.dataset.share + '/share').then(function (r) { alert(r.message); }).catch(function (e) { alert(e.message); b.disabled = false; });
    });
  });
})();
