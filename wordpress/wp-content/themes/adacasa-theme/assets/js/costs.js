/* Toplam maliyet hesaplayıcı (ilan sayfası, satılık). Kurallar sunucudan gelir (AECOST.r), hesap tarayıcıda yapılır. */
(function () {
  'use strict';
  var box = document.getElementById('maliyet');
  if (!box || !window.AECOST) return;
  var C = AECOST, R = C.r, T = C.t;
  C.price = Number(C.price) || 0; C.area = Number(C.area) || 0;
  var rates = (window.AE && AE.rates) || { GBP: 1 }, sym = (window.AE && AE.sym) || { GBP: '£' };
  function cur() { var m = document.cookie.match(/(?:^|; )ec_cur=([A-Z]{3})/); return m && rates[m[1]] ? m[1] : 'GBP'; }
  function money(gbp) {
    var c = cur(), v = Math.round(gbp * (rates[c] || 1));
    return (sym[c] || '') + v.toLocaleString('tr-TR');
  }
  function range(a, b) { return Math.round(a) === Math.round(b) ? money(a) : money(a) + ' – ' + money(b); }
  var f = box.querySelector('form');
  function calc() {
    var who = f.who.value, nth = Math.max(1, parseInt(f.nth.value, 10) || 1), isNew = f.seller.value === 'new';
    var price = C.price, rows = [], lo = 0, hi = 0;
    function add(label, a, b, note) { b = b === undefined ? a : b; rows.push([label, range(a, b), note || '']); lo += a; hi += b; }
    var tbl = R.deed[who] || R.deed.foreign, pct = tbl[Math.min(nth, tbl.length) - 1];
    add(T.deed + ' (%' + String(pct).replace('.', ',') + ')', price * pct / 100, price * pct / 100, R.promo ? T.promoNote : T.deedNote);
    if (isNew) {
      var big = C.area && C.area >= R.vat_limit_m2, v = big ? R.vat_large : R.vat_small;
      add(T.vat + ' (%' + v + ')', price * v / 100, price * v / 100, T.vatNote);
    }
    var tryRate = rates.TRY || 0, priceTry = tryRate ? price * tryRate : 0;
    var stamp = priceTry > R.stamp_limit_try ? (R.stamp_limit_try * R.stamp / 100 + (priceTry - R.stamp_limit_try) * R.stamp_high / 100) / tryRate : price * R.stamp / 100;
    add(T.stamp + ' (%' + String(R.stamp).replace('.', ',') + ')', stamp, stamp, T.stampNote);
    add(T.lawyer, R.lawyer_gbp[0] * (1 + R.lawyer_vat / 100), R.lawyer_gbp[1] * (1 + R.lawyer_vat / 100), T.lawyerNote);
    if (isNew) {
      add(T.trafo, R.trafo_gbp[0], R.trafo_gbp[1], T.trafoNote);
      add(T.util, R.utilities_gbp[0], R.utilities_gbp[1]);
    }
    if (who !== 'kktc' && tryRate) add(T.permit, R.permit_try / tryRate, R.permit_try / tryRate, T.permitNote);
    var h = '<table class="ctbl"><tbody>';
    rows.forEach(function (r) { h += '<tr><td>' + r[0] + (r[2] ? '<small>' + r[2] + '</small>' : '') + '</td><td>' + r[1] + '</td></tr>'; });
    h += '</tbody><tfoot><tr><th>' + T.extra + '</th><th>' + range(lo, hi) + '</th></tr><tr><th>' + T.total + '</th><th>' + range(price + lo, price + hi) + '</th></tr></tfoot></table>';
    box.querySelector('.cout').innerHTML = h;
  }
  f.addEventListener('change', calc);
  document.addEventListener('ec:currency', calc);
  var curSel = document.querySelector('select[name="currency"], #cur');
  if (curSel) curSel.addEventListener('change', function () { setTimeout(calc, 50); });
  calc();
})();
