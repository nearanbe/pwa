/* Arka plan: site API'siyle konuşur, aktarım işini (hangi ilan, hangi site) sekmeye bağlar,
   fotoğrafları indirir (sayfanın CORS kısıtına takılmamak için), SMS kodunu Telegram üzerinden ister. */
'use strict';

async function settings() {
  const s = await chrome.storage.local.get(['site', 'key', 'lang']);
  return { site: (s.site || '').replace(/\/+$/, ''), key: s.key || '', lang: s.lang || 'tr' };
}

async function api(path, opt = {}) {
  const s = await settings();
  if (!s.site || !s.key) throw new Error('Önce ayarlardan site adresini ve API anahtarını girin.');
  const r = await fetch(s.site + '/api/v1/' + path, {
    method: opt.method || 'GET',
    headers: Object.assign({ Authorization: 'Bearer ' + s.key }, opt.body ? { 'Content-Type': 'application/json' } : {}),
    body: opt.body ? JSON.stringify(opt.body) : undefined,
  });
  const j = await r.json().catch(() => ({}));
  if (!r.ok) throw new Error(j.message || ('HTTP ' + r.status));
  return j;
}

function b64(buf) {
  let s = '';
  const a = new Uint8Array(buf);
  for (let i = 0; i < a.length; i += 0x8000) s += String.fromCharCode.apply(null, a.subarray(i, i + 0x8000));
  return btoa(s);
}

const handlers = {
  async me() { return api('me'); },
  async listings() {
    const [items, syn] = await Promise.all([api('me/listings'), api('ext/syndication')]);
    const bySite = {};
    (syn.items || []).forEach((x) => { (bySite[x.listing_id] = bySite[x.listing_id] || {})[x.site] = x; });
    return items.map((it) => Object.assign(it, { syndication: bySite[it.id] || {} }));
  },
  async start({ listingId, site }) {
    const [data, cfg, s] = await Promise.all([api('ext/listing/' + listingId), api('ext/config'), settings()]);
    const sc = cfg.sites[site];
    if (!sc) throw new Error('Bilinmeyen site: ' + site);
    const tab = await chrome.tabs.create({ url: sc.new_url, active: true });
    await chrome.storage.session.set({ ['job_' + tab.id]: { listing: data, siteKey: site, site: sc, lang: s.lang, at: Date.now() } });
    return { tabId: tab.id };
  },
  async getJob(_, sender) {
    const k = 'job_' + sender.tab.id;
    const o = await chrome.storage.session.get(k);
    return o[k] || null;
  },
  async markStarted(_, sender) {
    const k = 'job_' + sender.tab.id;
    const o = await chrome.storage.session.get(k);
    if (o[k]) { o[k].started = true; await chrome.storage.session.set(o); }
    return true;
  },
  async endJob(_, sender) { await chrome.storage.session.remove('job_' + sender.tab.id); return true; },
  async photos({ urls }) {
    const out = [];
    for (const u of urls) {
      try {
        const r = await fetch(u);
        if (!r.ok) continue;
        const type = r.headers.get('content-type') || 'image/webp';
        out.push({ name: u.split('/').pop().split('?')[0], type, data: b64(await r.arrayBuffer()) });
      } catch (e) { /* tek fotoğraf hatası tüm işi durdurmasın */ }
    }
    return out;
  },
  async report(body) { return api('ext/syndication', { method: 'POST', body }); },
  async smsRequest({ site }) { return api('ext/sms-request', { method: 'POST', body: { site } }); },
  async smsCode() { return api('ext/sms-code'); },
};

chrome.runtime.onMessage.addListener((msg, sender, reply) => {
  const h = handlers[msg && msg.type];
  if (!h) return false;
  h(msg, sender).then((data) => reply({ ok: true, data }), (e) => reply({ ok: false, error: e.message || String(e) }));
  return true; // cevap eşzamansız
});

chrome.tabs.onRemoved.addListener((tabId) => chrome.storage.session.remove('job_' + tabId));
