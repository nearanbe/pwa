const { chromium } = require('playwright');
(async () => {
  const b = await chromium.launch();
  const out = process.argv[2];
  const urls = JSON.parse(process.argv[3]);
  for (const [name, url, w] of urls) {
    const p = await b.newPage({ viewport: { width: w, height: 900 }, deviceScaleFactor: 1 });
    const errs = [];
    p.on('console', m => { if (m.type() === 'error') errs.push(m.text()); });
    p.on('pageerror', e => errs.push('PAGEERR ' + e.message));
    await p.goto(url, { waitUntil: 'load' });
    await p.waitForTimeout(800);
    await p.screenshot({ path: `${out}/${name}.png`, fullPage: true });
    console.log(name, errs.filter(e => !/tile.openstreetmap|fonts.g|ERR_TUNNEL|net::/.test(e)).join(' | ') || 'ok');
    await p.close();
  }
  await b.close();
})();
