const { chromium } = require('playwright');
const fs = require('fs');
(async () => {
  const [ext, lid, key] = process.argv.slice(2);
  const ctx = await chromium.launchPersistentContext('/tmp/x/chprof-' + Date.now(), {
    channel: 'chromium', headless: true, args: [`--disable-extensions-except=${ext}`, `--load-extension=${ext}`], viewport: { width: 1280, height: 1000 },
  });
  const mock = fs.readFileSync('mock101.html', 'utf8');
  await ctx.route('https://www.101evler.com/**', (r) => r.fulfill({ status: 200, contentType: 'text/html; charset=utf-8', body: mock }));
  let sw = ctx.serviceWorkers()[0] || await ctx.waitForEvent('serviceworker');
  const id = sw.url().split('/')[2];
  // Ayarları yaz (seçenekler sayfasının yerine)
  await sw.evaluate(([k]) => chrome.storage.local.set({ site: 'http://localhost:8080', key: k, lang: 'tr' }), [key]);
  const pop = await ctx.newPage();
  await pop.goto(`chrome-extension://${id}/popup.html`);
  await pop.waitForTimeout(4000); console.log('who:', await pop.textContent('#who'), 'msg:', await pop.textContent('#msg'));
  await pop.waitForSelector('#list li', { timeout: 15000 });
  console.log('popup ilan sayısı', await pop.locator('#list li').count(), '|', await pop.textContent('#who'));
  await pop.fill('#q', String(lid));
  const [page] = await Promise.all([ctx.waitForEvent('page'), pop.click('#list li .btns button:first-child')]);
  await page.waitForSelector('#eia-panel', { timeout: 15000 });
  await page.waitForTimeout(6000);
  console.log('url', page.url(), (await page.content()).slice(0,300)); await page.screenshot({ path: 'shots/ext-panel.png' });
  const v = await page.evaluate(() => {
    const f = document.getElementById('f'), sel = (n) => { const s = f.elements[n]; return s.options[s.selectedIndex].text; };
    return { tip: sel('type'), kat: sel('category'), title: f.title.value, price: f.price.value, cur: sel('currency'), city: sel('city'), dist: sel('district'), rooms: sel('rooms'), bath: sel('bathrooms'), area: f.area.value,
      deed: sel('title_deed'), feats: [...f.querySelectorAll('input[type=checkbox]:checked')].map((c) => c.parentElement.textContent.trim()), files: f.querySelector('input[type=file]').files.length, published: window.published, desc: f.description.value.slice(0, 40) };
  });
  console.log(JSON.stringify(v, null, 1));
  console.log('panel günlüğü:\n - ' + (await page.$$eval('#eia-panel .eia-log li', (l) => l.map((x) => x.textContent))).join('\n - '));
  await page.screenshot({ path: 'shots/ext-panel.png' });
  // Yayınladım düğmesi → kayıt
  await page.click('.eia-done'); await page.waitForTimeout(1500);
  console.log('son satır', await page.$$eval('#eia-panel .eia-log li', (l) => l[l.length - 1].textContent));
  await ctx.close();
})().catch((e) => { console.error('HATA', e.message); process.exit(1); });
