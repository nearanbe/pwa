'use strict';
const $ = (s) => document.querySelector(s);
const send = (type, extra) => new Promise((res) => chrome.runtime.sendMessage(Object.assign({ type }, extra || {}), (r) => res(r || { ok: false })));
const ST = { published: 'Yayında', pending: 'Onayda', draft: 'Taslak', rejected: 'Reddedildi', sold: 'Satıldı', passive: 'Pasif' };
const SITES = [['101evler', '101evler'], ['hangiev', 'hangiev']];
let all = [];

function render() {
  const q = $('#q').value.trim().toLocaleLowerCase('tr');
  const ul = $('#list'); ul.innerHTML = '';
  all.filter((it) => !q || String(it.id).includes(q) || it.title.toLocaleLowerCase('tr').includes(q)).slice(0, 80).forEach((it) => {
    const li = document.createElement('li');
    const img = it.cover ? `<img src="${it.cover.small}" alt="">` : '<div class="noimg"></div>';
    li.innerHTML = img + `<div><div class="t"></div><div class="sub">#${it.id} · <span class="st">${ST[it.status] || it.status}</span></div></div><div class="btns"></div>`;
    li.querySelector('.t').textContent = it.title;
    const btns = li.querySelector('.btns');
    SITES.forEach(([key, name]) => {
      const b = document.createElement('button');
      const syn = it.syndication[key];
      b.textContent = syn && syn.status === 'published' ? name + ' ✓' : name + '’e aktar';
      if (syn && syn.status === 'published') { b.className = 'done'; b.title = syn.remote_url || ''; }
      b.disabled = !['published', 'pending'].includes(it.status);
      b.onclick = async () => {
        if (syn && syn.status === 'published' && !confirm(name + '’de zaten var. Yine de yeni ilan formu açılsın mı?')) return;
        b.disabled = true; $('#msg').textContent = 'Açılıyor…';
        const r = await send('start', { listingId: it.id, site: key });
        $('#msg').textContent = r.ok ? '' : r.error; b.disabled = false;
        if (r.ok) window.close();
      };
      btns.appendChild(b);
    });
    ul.appendChild(li);
  });
}

(async () => {
  const me = await send('me');
  if (!me.ok) { $('#who').textContent = me.error; $('#msg').innerHTML = '<a href="options.html" target="_blank">Ayarları aç →</a>'; return; }
  $('#who').textContent = me.data.name;
  const r = await send('listings');
  if (!r.ok) { $('#msg').textContent = r.error; return; }
  all = r.data; render();
  $('#q').addEventListener('input', render);
})();
