'use strict';
const $ = (s) => document.querySelector(s);
chrome.storage.local.get(['site', 'key', 'lang']).then((s) => { $('#site').value = s.site || ''; $('#key').value = s.key || ''; $('#lang').value = s.lang || 'tr'; });
$('#save').onclick = async () => {
  let site = $('#site').value.trim().replace(/\/+$/, '');
  if (site && !/^https?:\/\//.test(site)) site = 'https://' + site;
  const origin = new URL(site).origin + '/*';
  // Site adresine erişim izni (bir kez sorulur)
  const granted = await chrome.permissions.request({ origins: [origin] }).catch(() => false);
  if (!granted) { $('#out').textContent = 'Siteye erişim izni verilmedi.'; return; }
  await chrome.storage.local.set({ site, key: $('#key').value.trim(), lang: $('#lang').value });
  chrome.runtime.sendMessage({ type: 'me' }, (r) => { $('#out').textContent = r && r.ok ? '✅ Bağlandı: ' + r.data.name : '⛔ ' + ((r && r.error) || 'Bağlanılamadı'); });
};
