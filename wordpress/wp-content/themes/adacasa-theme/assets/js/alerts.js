/* AdaCasa uyarıları: "Fiyat düşerse haber ver", "Bu aramayı kaydet", favoriye eklerken fiyat uyarısı önerisi.
   Kanal: bu cihaza anlık bildirim (Web Push) ya da e-posta (onay bağlantısıyla). Bağımlılık yok. */
(function () {
  'use strict';
  var C = window.AEA || {}, T = C.t || {}, api = (window.AE && AE.api) || '/api/v1/';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var LS = 'ec_push';
  function store(v) { try { if (v) localStorage.setItem(LS, JSON.stringify(v)); return JSON.parse(localStorage.getItem(LS) || 'null'); } catch (e) { return null; } }
  function req(method, path, body) {
    return fetch(api + path, { method: method, headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: body ? JSON.stringify(body) : undefined })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw j; return j; }); });
  }
  function toast(t) { var d = document.createElement('div'); d.className = 'toast'; d.setAttribute('role', 'status'); d.textContent = t; document.body.appendChild(d); setTimeout(function () { d.remove(); }, 3200); }
  var standalone = window.matchMedia && matchMedia('(display-mode: standalone)').matches;
  var ios = /iphone|ipad|ipod/i.test(navigator.userAgent);
  var pushOk = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && window.isSecureContext && !(ios && !standalone);

  function b64ToBytes(s) {
    s = s.replace(/-/g, '+').replace(/_/g, '/'); while (s.length % 4) s += '=';
    var raw = atob(s), out = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
  }
  /** Bu cihazı bildirim için kaydet; {id,k} döner */
  function ensurePush(asAgent) {
    if (!pushOk) return Promise.reject({ message: T.noPush });
    return Notification.requestPermission().then(function (p) {
      if (p !== 'granted') throw { message: T.denied };
      return Promise.all([navigator.serviceWorker.register('/?ec_pwa=sw', { scope: '/' }).then(function () { return navigator.serviceWorker.ready; }), req('GET', 'push/key')]);
    }).then(function (x) {
      var reg = x[0], key = x[1].key;
      return reg.pushManager.getSubscription().then(function (s) {
        return s || reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64ToBytes(key) });
      });
    }).then(function (sub) {
      return req('POST', 'push/sub', { sub: sub.toJSON(), lang: (window.AE && AE.lang) || 'tr', agent: asAgent ? 1 : 0 });
    }).then(function (r) { store(r); return r; });
  }
  window.ecEnsurePush = ensurePush;

  /* ---- Pencere: bu cihaz ya da e-posta ---- */
  var ov;
  function dialog(kind, data, btn) {
    if (!ov) {
      ov = document.createElement('div');
      ov.className = 'ov alov'; ov.hidden = true; ov.setAttribute('role', 'dialog'); ov.setAttribute('aria-modal', 'true');
      ov.innerHTML = '<div class="sheet"><button type="button" class="ovx" aria-label="×">✕</button><h2 class="alh"></h2><p class="sub alp"></p>'
        + '<button type="button" class="btn alpush">🔔 ' + T.thisDevice + '</button>'
        + '<p class="sub alios" hidden>' + T.iosHint + '</p>'
        + '<form class="alform"><label class="vh" for="al-mail">E-posta</label><div class="row"><input id="al-mail" type="email" required placeholder="' + T.emailPh + '" autocomplete="email"><button class="ghost" type="submit">' + T.byEmail + '</button></div>'
        + '<input type="text" name="website" tabindex="-1" autocomplete="off" class="hp" aria-hidden="true"><p class="sub">' + T.privacy + '</p></form><p class="almsg" role="status"></p></div>';
      document.body.appendChild(ov);
      ov.addEventListener('click', function (e) { if (e.target === ov || e.target.closest('.ovx')) ov.hidden = true; });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') ov.hidden = true; });
    }
    $('.alh', ov).textContent = kind === 'price' ? T.priceH : T.searchH;
    $('.alp', ov).textContent = kind === 'price' ? T.priceP : T.searchP;
    $('.alpush', ov).hidden = !pushOk;
    $('.alios', ov).hidden = !(ios && !standalone);
    $('.almsg', ov).textContent = '';
    var payload = Object.assign({ kind: kind, lang: (window.AE && AE.lang) || 'tr' }, data);
    function done(r) {
      if (btn) setOn(btn, r.token);
      $('.almsg', ov).textContent = r.channel === 'email' ? T.checkMail : T.okPush;
      setTimeout(function () { ov.hidden = true; }, r.channel === 'email' ? 3500 : 1500);
    }
    function fail(e) { $('.almsg', ov).textContent = (e && e.message) || T.err; }
    $('.alpush', ov).onclick = function () {
      $('.almsg', ov).textContent = '…';
      ensurePush().then(function (s) { return req('POST', 'alerts', Object.assign({ sub: s.id, k: s.k }, payload)); }).then(done, fail);
    };
    $('.alform', ov).onsubmit = function (e) {
      e.preventDefault();
      var hp = $('.hp', ov).value;
      req('POST', 'alerts', Object.assign({ email: $('#al-mail').value, website: hp }, payload)).then(done, fail);
    };
    ov.hidden = false;
    setTimeout(function () { (pushOk ? $('.alpush', ov) : $('#al-mail')).focus(); }, 30);
  }

  function setOn(btn, token) {
    btn.classList.add('on'); btn.dataset.token = token; btn.setAttribute('aria-pressed', 'true');
    var l = $('.allbl', btn); if (l) l.textContent = btn.dataset.alertPrice ? T.priceOn : T.searchOn;
  }
  function setOff(btn) {
    btn.classList.remove('on'); delete btn.dataset.token; btn.setAttribute('aria-pressed', 'false');
    var l = $('.allbl', btn); if (l) l.textContent = btn.dataset.alertPrice ? T.priceBtn : T.searchBtn;
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-alert-price],[data-alert-search]');
    if (!b) return;
    e.preventDefault();
    if (b.dataset.token) {
      if (!confirm(T.offQ)) return;
      req('DELETE', 'alerts/' + b.dataset.token).then(function () { setOff(b); toast(T.offDone); });
      return;
    }
    if (b.dataset.alertPrice) dialog('price', { listing_id: Number(b.dataset.alertPrice) }, b);
    else {
      var q = {}; try { q = JSON.parse(b.dataset.q || '{}'); } catch (x) {}
      dialog('search', { query: q, url: b.dataset.url || location.href, label: b.dataset.label || document.title }, b);
    }
  });

  /* Favoriye ekleyince: "Fiyat düşerse haber verelim mi?" */
  document.addEventListener('click', function (e) {
    var f = e.target.closest('[data-fav]');
    if (!f) return;
    setTimeout(function () {
      if (!f.classList.contains('on')) return;
      var id = Number(f.dataset.fav), have = $('[data-alert-price="' + id + '"].on');
      if (have || document.querySelector('.alprompt')) return;
      var p = document.createElement('div');
      p.className = 'toast alprompt'; p.setAttribute('role', 'status');
      p.innerHTML = '<span>' + T.favQ + '</span> <button type="button" class="btn sm">🔔 ' + T.yes + '</button><button type="button" class="linkbtn" aria-label="×">×</button>';
      document.body.appendChild(p);
      var t = setTimeout(function () { p.remove(); }, 7000);
      p.addEventListener('click', function (ev) {
        if (ev.target.closest('.btn')) { clearTimeout(t); p.remove(); dialog('price', { listing_id: id }, $('[data-alert-price="' + id + '"]')); }
        else if (ev.target.closest('.linkbtn')) { clearTimeout(t); p.remove(); }
      });
    }, 0);
  });

  /* Doğal dille arama */
  document.addEventListener('submit', function (e) {
    var f = e.target.closest('[data-ai-search]');
    if (!f) return;
    e.preventDefault();
    var q = f.q.value.trim(), m = f.querySelector('.aimsg'), b = f.querySelector('button');
    if (q.length < 4) { f.q.focus(); return; }
    b.disabled = true; m.textContent = T.aiWait || '…';
    req('POST', 'ai-search', { q: q, lang: (window.AE && AE.lang) || 'tr' })
      .then(function (r) { try { sessionStorage.setItem('ec_aiq', q); } catch (x) {} location.href = r.url; })
      .catch(function (x) { m.textContent = (x && x.message) || T.err; b.disabled = false; });
  });

  /* Emlak talep formu (yalnızca yapay zekâ aramasından sonra gösterilir) */
  var rov = document.getElementById('reqov');
  if (rov) {
    var rf = $('form', rov), aiq = '';
    try { aiq = sessionStorage.getItem('ec_aiq') || ''; } catch (x) {}
    if (aiq && !rf.message.value) rf.message.value = aiq;
    var rclose = function () { rov.hidden = true; };
    rov.addEventListener('click', function (e) { if (e.target === rov || e.target.closest('.ovx')) rclose(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !rov.hidden) rclose(); });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('[data-req-open]')) return;
      e.preventDefault(); rov.hidden = false;
      setTimeout(function () { (rf.message.value ? rf.elements.name : rf.message).focus(); }, 30);
    });
    rf.addEventListener('input', function (e) { var l = e.target.closest('.bad'); if (l) l.classList.toggle('bad', false); if (!$('.bad', rf) && !rf.classList.contains('sent')) $('.rqmsg', rf).textContent = ''; });
    rf.addEventListener('change', function (e) { var l = e.target.closest('.bad'); if (l) l.classList.toggle('bad', false); });
    rf.addEventListener('submit', function (e) {
      e.preventDefault();
      var el = rf.elements, msg = $('.rqmsg', rf), btn = $('button[type=submit]', rf);
      $$('.bad', rf).forEach(function (x) { x.classList.toggle('bad', false); });
      var miss = ['message', 'name', 'phone'].filter(function (n) { return !el[n].value.trim(); });
      if (!el.consent.checked) miss.push('consent');
      if (miss.length) { miss.forEach(function (n) { (el[n].closest('label') || el[n]).classList.add('bad'); }); msg.textContent = T.rqFill; return; }
      var filters = {}; try { filters = JSON.parse(rf.dataset.filters || '{}'); } catch (x) {}
      var ts = rf.querySelector('[name="cf-turnstile-response"]');
      var body = { name: el.name.value, phone: el.phone.value, email: el.email.value, message: el.message.value, timing: el.timing.value,
        contact: (rf.querySelector('[name=contact]:checked') || {}).value, consent: 1, website: el.website.value, ai_q: aiq, filters: filters,
        url: rf.dataset.url || location.href, lang: (window.AE && AE.lang) || 'tr', 'cf-turnstile-response': ts ? ts.value : '' };
      btn.disabled = true; msg.textContent = '…';
      req('POST', 'requests', body).then(function (r) {
        msg.textContent = r.count ? T.rqOk.replace('%s', r.count) : T.rqOk0;
        rf.classList.add('sent'); try { sessionStorage.setItem('ec_aiq', ''); } catch (x) {}
        $$('[data-req-open]').forEach(function (b) { b.disabled = true; });
        setTimeout(rclose, 4500);
      }, function (x) {
        btn.disabled = false;
        if (x && x.code === 'ec_too_many') msg.textContent = T.rqMany;
        else if (x && x.data && x.data.fields) { Object.keys(x.data.fields).forEach(function (n) { if (el[n]) (el[n].closest('label') || el[n]).classList.add('bad'); }); msg.textContent = T.rqFill; }
        else msg.textContent = (x && x.message) || T.err;
        if (window.turnstile) { try { turnstile.reset(); } catch (y) {} }
      });
    });
  }

  /* Sayfa açılınca: bu cihazdaki açık uyarıları işaretle */
  var me = store();
  if (me && ($('[data-alert-price]') || $('[data-alert-search]'))) {
    req('GET', 'alerts?sub=' + me.id + '&k=' + me.k).then(function (r) {
      $$('[data-alert-price]').forEach(function (b) { var t = r.price && r.price[b.dataset.alertPrice]; if (t) setOn(b, t); });
      $$('[data-alert-search]').forEach(function (b) {
        var u = (b.dataset.url || '').split('#')[0];
        (r.search || []).forEach(function (s) { if (s.url === u) setOn(b, s.token); });
      });
    }).catch(function () {});
  }
})();
