/* Döviz çevirici: parantez içindeki ikinci fiyata (.money2) veya [data-conv] düğmesine tıklayınca açılır.
   Kurlar sayfaya AE.rates olarak gelir (1 GBP = x), kaynak ve tarih AE.rateInfo'dadır. */
(function () {
  'use strict';
  var AE = window.AE;
  if (!AE || !AE.rates) return;
  var CURS = ['GBP', 'EUR', 'USD', 'TRY'];
  var NAMES = { GBP: '£ GBP', EUR: '€ EUR', USD: '$ USD', TRY: '₺ TRY' };
  var pop = null, anchor = null;

  function fmt(v, dec) { return v.toLocaleString('de-DE', { minimumFractionDigits: dec, maximumFractionDigits: dec }); }
  /* "250.000" → 250000, "1.250,50" → 1250.5, "1,5" → 1.5, "1.5" → 1.5 (Türkçe yazım: nokta binlik, virgül ondalık) */
  function parse(s) {
    s = String(s).trim().replace(/[\s₺£€$]/g, '');
    if (s.indexOf(',') > -1) s = s.replace(/\./g, '').replace(',', '.');
    else if (/^\d{1,3}(\.\d{3})+$/.test(s)) s = s.replace(/\./g, '');
    var n = parseFloat(s); return isNaN(n) ? 0 : n;
  }
  function selected() { var c = document.getElementById('cur'); return c && CURS.indexOf(c.value) > -1 ? c.value : 'GBP'; }

  function build() {
    pop = document.createElement('div');
    pop.className = 'convpop';
    pop.setAttribute('role', 'dialog');
    pop.setAttribute('aria-label', AE.msg.convTitle);
    pop.innerHTML =
      '<div class="convhead"><b>⇄ ' + AE.msg.convTitle + '</b><button type="button" class="convx" aria-label="' + AE.msg.close + '">✕</button></div>' +
      '<div class="convin"><label class="vh" for="conv-amt">' + AE.msg.convAmount + '</label><input id="conv-amt" inputmode="decimal" autocomplete="off">' +
      '<select id="conv-cur" aria-label="' + AE.msg.convAmount + '">' + CURS.map(function (c) { return '<option value="' + c + '">' + NAMES[c] + '</option>'; }).join('') + '</select></div>' +
      '<ul class="convres"></ul><p class="convrate"></p><p class="convnote"></p>';
    document.body.appendChild(pop);
    pop.querySelector('.convx').addEventListener('click', close);
    pop.querySelector('#conv-amt').addEventListener('input', render);
    pop.querySelector('#conv-cur').addEventListener('change', render);
    pop.addEventListener('click', function (e) {
      var li = e.target.closest('li[data-c]');
      if (!li) return;
      // Bir satıra tıklayınca o para birimine geç (tutarı koru)
      var amt = pop.querySelector('#conv-amt'), from = pop.querySelector('#conv-cur');
      amt.value = fmt(parseFloat(li.dataset.v), li.dataset.c === 'TRY' ? 2 : 0); from.value = li.dataset.c; render();
    });
  }

  function render() {
    var amt = parse(pop.querySelector('#conv-amt').value), from = pop.querySelector('#conv-cur').value;
    var gbp = amt / (AE.rates[from] || 1);
    pop.querySelector('.convres').innerHTML = CURS.map(function (c) {
      var v = gbp * (AE.rates[c] || 1);
      return '<li data-c="' + c + '" data-v="' + v + '" class="' + (c === from ? 'on' : '') + '"><span>' + NAMES[c] + '</span><b>' + (AE.sym[c] || '') + fmt(v, 2) + '</b></li>';
    }).join('');
    var r = AE.rates;
    pop.querySelector('.convrate').textContent = '1 £ = ' + fmt(r.EUR, 4) + ' € · ' + fmt(r.USD, 4) + ' $ · ' + fmt(r.TRY, 4) + ' ₺';
    var info = AE.rateInfo || {};
    if (info.live && info.official) {
      // Gün içi (piyasa) kuru kullanılıyor; resmi TCMB kuru ayrıca gösterilir
      var o = info.official;
      pop.querySelector('.convrate').textContent = info.live + ' (' + info.liveSource + '): ' + pop.querySelector('.convrate').textContent;
      pop.querySelector('.convnote').textContent = info.officialLabel + ': 1 £ = ' + fmt(o.TRY, 4) + ' ₺ · ' + AE.msg.convNote;
    } else {
      pop.querySelector('.convnote').textContent = [info.source, info.date, AE.msg.convNote].filter(Boolean).join(' · ');
    }
  }

  function place() {
    if (!anchor) return;
    if (window.innerWidth < 600) { pop.classList.add('sheet'); pop.style.left = pop.style.top = ''; return; }
    pop.classList.remove('sheet');
    var r = anchor.getBoundingClientRect(), w = pop.offsetWidth, h = pop.offsetHeight;
    var left = Math.min(Math.max(8, r.left + window.scrollX + r.width / 2 - w / 2), window.scrollX + document.documentElement.clientWidth - w - 8);
    var top = r.bottom + window.scrollY + 8;
    if (r.bottom + h + 16 > window.innerHeight && r.top - h - 8 > 0) top = r.top + window.scrollY - h - 8;
    pop.style.left = left + 'px'; pop.style.top = top + 'px';
  }

  function open(el, gbp) {
    if (!pop) build();
    anchor = el;
    var cur = selected();
    // Parantezdeki tutara tıklandıysa onun para biriminden, değilse seçili para biriminden başla
    var from = el.classList.contains('money2') ? (cur === 'TRY' ? 'GBP' : 'TRY') : cur;
    var v = gbp * (AE.rates[from] || 1);
    pop.querySelector('#conv-cur').value = from;
    pop.querySelector('#conv-amt').value = fmt(v, from === 'TRY' ? 2 : 0);
    render();
    pop.hidden = false;
    place();
    pop.querySelector('#conv-amt').focus();
    pop.querySelector('#conv-amt').select();
  }
  function close() { if (pop) { pop.hidden = true; if (anchor) anchor.focus(); anchor = null; } }

  document.addEventListener('click', function (e) {
    var t = e.target.closest('.money2, [data-conv]');
    if (t) {
      e.preventDefault(); e.stopPropagation();
      if (pop && !pop.hidden && anchor === t) { close(); return; }
      open(t, parseFloat(t.dataset.gbp || t.dataset.conv) || 0);
      return;
    }
    if (pop && !pop.hidden && !pop.contains(e.target)) close();
  }, true);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && pop && !pop.hidden) close();
    if ((e.key === 'Enter' || e.key === ' ') && e.target.classList && e.target.classList.contains('money2')) { e.preventDefault(); e.target.click(); }
  });
  window.addEventListener('resize', function () { if (pop && !pop.hidden) place(); });
})();
