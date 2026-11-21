/* 101evler / hangiev sayfasında çalışır. Aktarım işi varsa sağ altta bir panel açar, formu doldurur,
   fotoğrafları ekler, SMS kodu ve captcha durumunu izler. Formu ASLA göndermez. */
(function () {
  'use strict';
  const send = (type, extra) => new Promise((res) => chrome.runtime.sendMessage(Object.assign({ type }, extra || {}), (r) => res(r || { ok: false, error: chrome.runtime.lastError && chrome.runtime.lastError.message })));
  let job = null, panel = null, logEl = null, reported = false;

  function log(t, cls) { const li = document.createElement('li'); li.textContent = t; if (cls) li.className = cls; logEl.appendChild(li); logEl.scrollTop = 1e6; }

  function buildPanel() {
    panel = document.createElement('div');
    panel.id = 'eia-panel';
    panel.innerHTML = '<div class="eia-h"><b>İlan Aktarıcı</b><button type="button" class="eia-min" title="Küçült">–</button></div>' +
      '<div class="eia-body"><p class="eia-t"></p><ol class="eia-log"></ol>' +
      '<div class="eia-act"><button type="button" class="eia-fill">Formu doldur</button><button type="button" class="eia-photos">Fotoğrafları ekle</button>' +
      '<button type="button" class="eia-sms" hidden>SMS kodunu Telegram’dan iste</button><button type="button" class="eia-done">Yayınladım ✓</button></div>' +
      '<p class="eia-note">Bilgileri kontrol edin. <b>“Yayınla” düğmesine siz basın.</b> Doğrulama (captcha) çıkarsa siz tamamlayın.</p></div>';
    document.documentElement.appendChild(panel);
    logEl = panel.querySelector('.eia-log');
    panel.querySelector('.eia-t').textContent = '#' + job.listing.id + ' ' + job.listing.title + ' → ' + job.site.name;
    panel.querySelector('.eia-min').onclick = () => panel.classList.toggle('eia-small');
    panel.querySelector('.eia-fill').onclick = () => runFill(true);
    panel.querySelector('.eia-photos').onclick = addPhotos;
    panel.querySelector('.eia-sms').onclick = askSms;
    panel.querySelector('.eia-done').onclick = () => markPublished(location.href, true);
  }

  async function runFill(onlyEmpty) {
    const r = await window.EIA.fill(document, job.listing, job.site, { lang: job.lang, onlyEmpty });
    if (r.filled.length) log('Dolduruldu: ' + r.filled.join(', '), 'ok');
    if (r.features) log(r.features + ' özellik işaretlendi', 'ok');
    if (r.nomatch.length) log('Seçenek bulunamadı (elle seçin): ' + r.nomatch.join(', '), 'warn');
    if (r.missing.length) log('Bu sayfada yok: ' + r.missing.join(', ') + (r.filled.length ? '' : ' — form birkaç adımlıysa sonraki adımda “Formu doldur”a tekrar basın'), 'muted');
    if (!reported && r.filled.length) { reported = true; send('report', { listing_id: job.listing.id, site: job.siteKey, status: 'filled' }); }
    return r;
  }

  async function toJpeg(blob) {
    // Bazı siteler WebP kabul etmez: tarayıcıda JPEG'e çevir
    const bmp = await createImageBitmap(blob);
    const c = document.createElement('canvas'); c.width = bmp.width; c.height = bmp.height;
    c.getContext('2d').drawImage(bmp, 0, 0);
    return new Promise((res) => c.toBlob(res, 'image/jpeg', 0.9));
  }

  async function addPhotos() {
    const input = window.EIA.find(document, job.site.fields.photos);
    if (!input) { log('Fotoğraf yükleme alanı bulunamadı; bu adımda yoksa sonraki adımda tekrar deneyin.', 'warn'); return; }
    const urls = (job.listing.photos || []).map((p) => p.large);
    if (!urls.length) { log('İlanın fotoğrafı yok.', 'muted'); return; }
    log(urls.length + ' fotoğraf indiriliyor…');
    const r = await send('photos', { urls });
    if (!r.ok) { log('Fotoğraflar indirilemedi: ' + r.error, 'warn'); return; }
    const files = [];
    for (let i = 0; i < r.data.length; i++) {
      const p = r.data[i];
      const bin = Uint8Array.from(atob(p.data), (c) => c.charCodeAt(0));
      const jpg = await toJpeg(new Blob([bin], { type: p.type }));
      files.push(new File([jpg], 'foto-' + String(i + 1).padStart(2, '0') + '.jpg', { type: 'image/jpeg' }));
    }
    const multi = input.multiple;
    window.EIA.putFiles(input, multi ? files : files.slice(0, 1));
    log((multi ? files.length : 1) + ' fotoğraf eklendi' + (multi ? '' : ' (alan tek dosya alıyor; diğerlerini sitenin “ekle” düğmesiyle ekleyin)'), 'ok');
  }

  async function askSms() {
    const b = panel.querySelector('.eia-sms'); b.disabled = true;
    const r = await send('smsRequest', { site: job.siteKey });
    if (!r.ok) { log(r.error, 'warn'); b.disabled = false; return; }
    log('Telegram’a kod sorusu gönderildi. Kodu bota yazın…');
    const until = Date.now() + 5 * 60 * 1000;
    const tick = async () => {
      const c = await send('smsCode');
      if (c.ok && c.data && c.data.code) {
        const el = window.EIA.find(document, job.site.sms);
        if (el) { el.focus(); const set = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set; set.call(el, c.data.code); el.dispatchEvent(new Event('input', { bubbles: true })); el.dispatchEvent(new Event('change', { bubbles: true })); }
        log('SMS kodu yazıldı: ' + c.data.code + ' — onay düğmesine siz basın.', 'ok'); b.disabled = false; return;
      }
      if (Date.now() < until) setTimeout(tick, 3000); else { log('Kod gelmedi (5 dk).', 'warn'); b.disabled = false; }
    };
    tick();
  }

  async function markPublished(url, manual) {
    const m = job.site.success && job.site.success.id_regex ? url.match(new RegExp(job.site.success.id_regex)) : null;
    const r = await send('report', { listing_id: job.listing.id, site: job.siteKey, status: 'published', remote_url: url, remote_id: m ? m[1] : undefined });
    log(r.ok ? 'Kaydedildi: ilan ' + job.site.name + '’de yayında.' : 'Kaydedilemedi: ' + r.error, r.ok ? 'ok' : 'warn');
    if (r.ok && manual) setTimeout(() => send('endJob'), 1500);
  }

  function watch() {
    // SMS kodu alanı ve captcha görünürse haber ver
    const obs = new MutationObserver(() => {
      const sms = window.EIA.find(document, job.site.sms);
      const btn = panel.querySelector('.eia-sms');
      if (sms && window.EIA.visible(sms) && btn.hidden) { btn.hidden = false; log('SMS doğrulama kodu isteniyor.', 'warn'); }
      if (!panel.dataset.cap && document.querySelector('iframe[src*="recaptcha"], iframe[src*="hcaptcha"], iframe[src*="challenges.cloudflare.com"], .g-recaptcha, .h-captcha, .cf-turnstile')) {
        panel.dataset.cap = '1'; log('Doğrulama (captcha) var: lütfen kendiniz tamamlayın.', 'warn');
      }
    });
    obs.observe(document.body, { childList: true, subtree: true });
  }

  (async function init() {
    const r = await send('getJob');
    if (!r.ok || !r.data) return;
    job = r.data;
    buildPanel();
    // Başarı sayfasına gelindiyse (ilan yayınlandı) kaydet
    const ok = (job.site.success && job.site.success.url_contains || []).some((s) => location.href.indexOf(s) > -1) && location.href !== job.site.new_url;
    if (ok && job.started) { markPublished(location.href, false); return; }
    if (!job.started) send('markStarted');
    log('Sayfa yüklendi, form aranıyor…');
    await new Promise((res) => setTimeout(res, 1200)); // sitenin kendi betikleri formu kursun
    const rep = await runFill(false);
    if (rep.filled.length && window.EIA.find(document, job.site.fields.photos)) await addPhotos();
    if (!rep.filled.length) log('Form bulunamadı. Giriş yapmanız gerekiyorsa giriş yapıp ilan verme sayfasında “Formu doldur”a basın.', 'warn');
    watch();
  })();
})();
