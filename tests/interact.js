const { chromium } = require('playwright');
(async () => {
  const [L, P] = process.argv.slice(2);
  const b = await chromium.launch();
  const p = await b.newPage({ viewport: { width: 1280, height: 900 } });
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(L);
  // lightbox
  await p.click('.gfull');
  console.log('lightbox open', await p.isVisible('.ov.lb'), await p.textContent('.lb-count'));
  await p.keyboard.press('ArrowRight');
  console.log('after right', await p.textContent('.lb-count'));
  console.log('side grid buttons', await p.locator('.lb-side button').count());
  await p.click('.lb-play'); await p.waitForTimeout(3700); console.log('slideshow', await p.textContent('.lb-count'));
  await p.keyboard.press('Escape');
  console.log('lightbox closed', await p.isHidden('.ov.lb'));
  // sticky
  await p.mouse.wheel(0, 1500); await p.waitForTimeout(500);
  console.log('sticky visible', await p.isVisible('#stickhead'));
  await p.screenshot({ path: 'shots/sticky.png' });
  // note
  await p.click('[data-note]'); await p.fill('#note-in', 'Pazartesi ara'); await p.waitForTimeout(500);
  await p.reload(); console.log('note persisted', await p.inputValue('#note-in'));
  // lead: validation then success
  await p.click('.leadform button[type=submit]');
  console.log('lead validation', await p.textContent('.leadform .formmsg'));
  await p.fill('.leadform [name=name]', 'Test Alıcı'); await p.fill('.leadform [name=phone]', '+90 533 111 2233');
  await p.check('.leadform [name=consent]'); await p.click('.leadform button[type=submit]');
  await p.waitForTimeout(1500); console.log('lead result', await p.textContent('.leadform .formmsg'));
  // report
  await p.click('[data-open=reportov]'); await p.check('input[name=reason][value=price]'); await p.click('.reportform button[type=submit]');
  await p.waitForTimeout(1200); console.log('report', await p.textContent('.reportform .formmsg'));
  // project calc + plan lightbox
  await p.goto(P);
  await p.fill('#instcalc [name=down]', '50'); console.log('calc monthly', await p.textContent('[data-o=monthly]'));
  await p.click('.uplan'); console.log('plan lb', await p.textContent('.lb-count'));
  await p.keyboard.press('Escape');
  await p.fill('.leadform [name=name]', 'Proje Alıcı'); await p.fill('.leadform [name=email]', 'a@b.co'); await p.check('.leadform [name=consent]'); await p.click('.leadform button[type=submit]');
  await p.waitForTimeout(1500); console.log('project lead', await p.textContent('.leadform .formmsg'));
  // print media
  await p.goto(L); await p.emulateMedia({ media: 'print' }); await p.screenshot({ path: 'shots/print.png', fullPage: true });
  console.log('errors', errs.join(' | ') || 'none');
  await b.close();
})();
