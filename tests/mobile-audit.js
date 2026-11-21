/**
 * Mobil düzen denetimi: her sayfayı telefon genişliklerinde (360, 390) açık ve koyu temada açar ve şunları arar:
 *  - yatay taşma (sayfa ekrandan geniş),
 *  - ekranın dışına taşan öğeler (kaydırılabilir kutuların içindekiler hariç),
 *  - anormal dar metin blokları (uzun metin, ekranın %45'inden dar sütunda),
 *  - JavaScript hataları.
 * Kullanım: node tests/mobile-audit.js <site> [kullanıcı] [şifre] [ekran-klasörü]
 *   ör. node tests/mobile-audit.js http://localhost:8080 admin admin /tmp/shots
 */
const { chromium } = require('playwright');
const [site, user, pass, shots] = process.argv.slice(2);

(async () => {
  const b = await chromium.launch();
  const get = async (path) => (await (await fetch(site + path)).text());
  const first = (html, re) => (html.match(re) || [])[1];
  const L = (await (await fetch(site + '/wp-json/emlak/v1/listings?per_page=40')).json()).items;
  const withPano = L.find((x) => x.pano_count) || L[0];
  const pj = first(await get('/tr/projeler/'), /href="([^"]*\/tr\/proje\/[^"]+)"/);
  const of = first(await get('/tr/firmalar/'), /href="([^"]*\/tr\/ofis\/[^"]+)"/);
  const post = first(await get('/blog/'), /<a class="cardlink" href="([^"]+)"/);
  const pub = [
    ['home', '/tr/'], ['home-en', '/en/'], ['home-ru', '/ru/'], ['satilik', '/tr/satilik/'], ['kiralik-girne', '/tr/kiralik/girne/'],
    ['liste', '/tr/satilik/?gorunum=liste'], ['harita', '/tr/satilik/?gorunum=harita'], ['gunluk', '/tr/gunluk-kiralik/'],
    ['ilan', L[0].url], ['ilan-360', withPano.url], ['ilan-ru', L[1].url.replace('/tr/', '/ru/').replace('/ilan/', '/obyavlenie/')],
    ['projeler', '/tr/projeler/'], ['proje', pj], ['firmalar', '/tr/firmalar/'], ['ofis', of], ['endeks', '/tr/fiyat-endeksi/'],
    ['sss', '/tr/sss/'], ['ilanver', '/tr/ilan-ver/'], ['kayit', '/tr/kayit/'], ['giris', '/tr/giris/'], ['favoriler', '/tr/favoriler/'],
    ['blog', '/blog/'], ['yazi', post], ['404', '/tr/yok-boyle-sayfa/'], ['yasal-tr', '/tr/yasal/gizlilik/'], ['yasal-en', '/en/legal/cookies/'], ['yasal-ru', '/ru/pravo/usloviya/'],
  ].filter((x) => x[1]);
  const priv = ['ozet', 'ilanlar', 'ilan', 'projeler', 'proje', 'talepler', 'ekip', 'paket', 'sosyal', 'profil', 'telegram']
    .map((t) => ['panel-' + t, '/tr/panelim/?bolum=' + t]).concat([['onay', '/tr/onay/']]);

  const audit = async (ctx, name, path, w, scheme) => {
    const p = await ctx.newPage();
    const errs = [];
    p.on('pageerror', (e) => errs.push('JS: ' + e.message.slice(0, 120)));
    await p.setViewportSize({ width: w, height: 800 });
    await p.emulateMedia({ colorScheme: scheme });
    await p.goto(path.startsWith('http') ? path : site + path, { waitUntil: 'load' }).catch((e) => errs.push('yüklenemedi ' + e.message.slice(0, 80)));
    await p.waitForTimeout(400);
    const r = await p.evaluate((vw) => {
      const out = [];
      const inScroller = (el) => { for (let e = el.parentElement; e && e !== document.body; e = e.parentElement) { const cs = getComputedStyle(e); if (/(auto|scroll|hidden)/.test(cs.overflowX) || cs.position === 'fixed') return true; } return false; };
      const vis = (el) => { const cs = getComputedStyle(el); return cs.display !== 'none' && cs.visibility !== 'hidden' && el.offsetParent !== null; };
      const lbl = (el) => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).slice(0, 2).join('.') : '');
      for (const el of document.querySelectorAll('body *')) {
        if (!vis(el) || el.closest('.ov[hidden], [hidden], .vh, .skip, svg')) continue;
        const rc = el.getBoundingClientRect();
        if (rc.width === 0 || rc.height === 0) continue;
        if (rc.right > vw + 2 && !inScroller(el)) out.push('taşıyor: ' + lbl(el) + ' sağ=' + Math.round(rc.right));
        const txt = (el.childNodes.length && [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim().length > 25)) ? el.textContent.trim() : '';
        if (txt.length > 60 && rc.width < vw * 0.45 && !inScroller(el) && !el.closest('.card,.grid,.idxstrip,.thumbs,.cc-in,.ordtable,table,.chips,.gv-tabs,.secnav,.navin,.tabs,.fgroups,.dgrid .row')) out.push('dar metin: ' + lbl(el) + ' ' + Math.round(rc.width) + 'px "' + txt.slice(0, 30) + '"');
      }
      return { sw: document.documentElement.scrollWidth, issues: [...new Set(out)].slice(0, 6) };
    }, w);
    if (r.sw > w + 1) r.issues.unshift('YATAY TAŞMA: sayfa ' + r.sw + 'px');
    if (shots && w === 390 && scheme === 'light') await p.screenshot({ path: `${shots}/m-${name}.png`, fullPage: true });
    await p.close();
    return [...errs, ...r.issues];
  };

  let bad = 0;
  const run = async (ctx, list) => {
    for (const [name, path] of list) for (const w of [360, 390]) for (const sc of ['light', 'dark']) {
      const iss = await audit(ctx, name, path, w, sc);
      if (iss.length) { bad++; console.log(`✘ ${name} ${w}px ${sc}:\n   ` + iss.join('\n   ')); }
    }
  };
  const anon = await b.newContext({ deviceScaleFactor: 1, isMobile: true, hasTouch: true });
  await run(anon, pub);
  if (user) {
    const ctx = await b.newContext({ deviceScaleFactor: 1, isMobile: true, hasTouch: true });
    const p = await ctx.newPage();
    await p.goto(site + '/wp-login.php'); await p.fill('#user_login', user); await p.fill('#user_pass', pass); await p.click('#wp-submit'); await p.waitForLoadState();
    await p.close();
    await run(ctx, priv);
    // Açılır pencereler: menü ve galeri
    const q = await ctx.newPage(); await q.setViewportSize({ width: 390, height: 844 });
    await q.goto(withPano.url); await q.$eval('[data-gv="g"]', (x) => x.click()); await q.waitForTimeout(500);
    const gsw = await q.evaluate(() => [...document.querySelectorAll('#gv *')].filter((e) => e.getBoundingClientRect().right > innerWidth + 2 && !e.closest('.gv-tabs')).length);
    console.log(gsw ? '✘ galeri penceresinde taşan öğe: ' + gsw : '✔ galeri penceresi');
    if (shots) await q.screenshot({ path: `${shots}/m-galeri.png` });
    await q.goto(site + '/tr/'); await q.click('.burger'); await q.waitForTimeout(300);
    if (shots) await q.screenshot({ path: `${shots}/m-menu.png` });
  }
  console.log(`\n${bad ? bad + ' sorunlu sayfa/görünüm' : 'Sorun bulunmadı'} (${(pub.length + (user ? priv.length : 0)) * 4} görünüm tarandı)`);
  await b.close();
})();
