const { chromium } = require('playwright');
const F = 'file:///home/user/pwa/wordpress/wp-content/themes/adacasa-theme/assets/fonts/';
const L = {
  tr: { h: 'Bu alana reklam verebilirsiniz', s: 'Kuzey Kıbrıs’ta ev arayanlara doğrudan ulaşın', c: 'Bilgi ve fiyat' },
  en: { h: 'Advertise here', s: 'Reach people looking for property in North Cyprus', c: 'Rates & info' },
  ru: { h: 'Здесь может быть ваша реклама', s: 'Обращайтесь напрямую к тем, кто ищет жильё на Северном Кипре', c: 'Цены и условия' },
};
const S = { '970x250': 'big', '728x90': 'bar', '320x100': 'mob', '300x250': 'box' };
const logo = require('fs').readFileSync('logo-mark.svg', 'utf8');
const css = `
@font-face{font-family:Cor;src:url(${F}cormorant-garamond-latin-600-normal.woff2)}@font-face{font-family:Cor;src:url(${F}cormorant-garamond-latin-ext-600-normal.woff2);unicode-range:U+0100-024F,U+1E00-1EFF,U+2000-206F}@font-face{font-family:Cor;src:url(${F}cormorant-garamond-cyrillic-600-normal.woff2);unicode-range:U+0400-04FF}
@font-face{font-family:Int;font-weight:400;src:url(${F}inter-latin-400-normal.woff2)}@font-face{font-family:Int;font-weight:400;src:url(${F}inter-latin-ext-400-normal.woff2);unicode-range:U+0100-024F,U+2000-206F}@font-face{font-family:Int;font-weight:400;src:url(${F}inter-cyrillic-400-normal.woff2);unicode-range:U+0400-04FF}
@font-face{font-family:Int;font-weight:600;src:url(${F}inter-latin-600-normal.woff2)}@font-face{font-family:Int;font-weight:600;src:url(${F}inter-latin-ext-600-normal.woff2);unicode-range:U+0100-024F,U+2000-206F}@font-face{font-family:Int;font-weight:600;src:url(${F}inter-cyrillic-600-normal.woff2);unicode-range:U+0400-04FF}
*{margin:0;box-sizing:border-box}body{background:#14284b}
.k{position:relative;overflow:hidden;color:#fff;font-family:Int;background:radial-gradient(120% 140% at 100% 0%,#1f3a66 0,#14284b 55%,#0e1d38 100%);display:flex;align-items:center}
.k:before{content:"";position:absolute;inset:6px;border:1px solid rgba(217,189,131,.55);border-radius:6px;pointer-events:none}
.lg{flex:none;display:flex;align-items:center;justify-content:center}.lg svg{width:100%;height:100%}
.h{font-family:Cor;font-weight:600;color:#fff;line-height:1.05}.s{color:#cdd6e6;line-height:1.3}
.c{font-weight:600;color:#14284b;background:#d9bd83;border-radius:999px;white-space:nowrap;display:inline-block}
.m{color:#d9bd83;font-weight:600}.w{color:#9fb0cc}
.big{padding:0 56px;gap:40px}.big .lg{width:96px;height:96px}.big .h{font-size:46px}.big .s{font-size:19px;margin-top:10px}.big .r{margin-left:auto;text-align:center}.big .c{font-size:17px;padding:12px 24px}.big .m{font-size:17px;margin-top:10px}
.bar{padding:0 22px;gap:16px}.bar .lg{width:46px;height:46px}.bar .h{font-size:27px}.bar .s{font-size:12.5px;margin-top:3px}.bar .r{margin-left:auto;text-align:center}.bar .c{font-size:12px;padding:6px 14px}.bar .m{font-size:12px;margin-top:5px}
.mob{padding:0 14px;gap:10px}.mob .lg{width:40px;height:40px}.mob .h{font-size:21px}.mob .s{display:none}.mob .m{font-size:12px;margin-top:5px}.mob .r{display:none}.mob .c{display:none}
.box{flex-direction:column;justify-content:center;text-align:center;padding:16px;gap:9px}.box .lg{width:48px;height:48px}.box .h{font-size:28px}.box .s{font-size:13.5px;margin-top:8px}.box .c{font-size:13px;padding:8px 18px}.box .m{font-size:13px;margin-top:8px}
`;
(async () => {
  const b = await chromium.launch(); const p = await b.newPage({ deviceScaleFactor: 2 });
  for (const [lang, t] of Object.entries(L)) for (const [sz, cls] of Object.entries(S)) {
    const [w, h] = sz.split('x').map(Number);
    const mid = cls === 'mob' ? `<div><div class="h">${t.h}</div><div class="m">bilgi@adacasa.com</div></div>`
      : cls === 'box' ? `<div><div class="h">${t.h}</div><div class="s">${t.s}</div></div><div><span class="c">${t.c}</span><div class="m">bilgi@adacasa.com</div></div>`
      : `<div><div class="h">${t.h}</div><div class="s">${t.s}</div></div><div class="r"><span class="c">${t.c} →</span><div class="m">bilgi@adacasa.com</div></div>`;
    await p.setViewportSize({ width: w, height: h });
    require('fs').writeFileSync(__dirname+'/t.html',`<html lang="${lang}"><style>${css}</style><div class="k ${cls}" style="width:${w}px;height:${h}px"><div class="lg">${logo}</div>${mid}</div></html>`); await p.goto('file://'+__dirname+'/t.html');
    await p.evaluate(() => document.fonts.ready); const fl = await p.evaluate(() => [...document.fonts].filter(f=>f.status==='loaded').length); if(!fl) console.log('FONT YOK');
    const of = await p.evaluate(() => [...document.querySelectorAll('.h,.s,.c,.m')].filter(e => e.offsetParent && (e.scrollWidth > e.clientWidth + 1 || e.getBoundingClientRect().right > innerWidth - 8 || e.getBoundingClientRect().bottom > innerHeight - 6)).map(e => e.className));
    await p.screenshot({ path: `reklam-${sz}-${lang}.png` });
    console.log(lang, sz, of.length ? 'TAŞMA: ' + of.join(',') : 'ok');
  }
  await b.close();
})();
