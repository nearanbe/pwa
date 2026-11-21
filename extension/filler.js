/* Form doldurma motoru. Sitenin formunu bilmeden çalışır: önce ayardaki CSS seçicilerine, bulamazsa
   alanın etiket (label) metnine bakar. Değeri, sitenin kendi JavaScript'i fark etsin diye "gerçek kullanıcı"
   gibi yazar (input/change olayları). Hiçbir zaman formu göndermez ve "Yayınla"ya basmaz. */
(function (root) {
  'use strict';
  function norm(s) {
    return String(s || '').toLocaleLowerCase('tr').replace(/[ıİ]/g, 'i').replace(/[şŞ]/g, 's').replace(/[ğĞ]/g, 'g').replace(/[üÜ]/g, 'u').replace(/[öÖ]/g, 'o').replace(/[çÇ]/g, 'c').replace(/[²]/g, '2').replace(/[^a-z0-9+]+/g, ' ').trim();
  }
  function visible(el) { return !!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length)) || (el && el.type === 'file'); }

  /** Etiket metninden alan bulur: <label for>, label içindeki alan, aynı kutudaki ilk alan, placeholder, aria-label */
  function byLabel(doc, labels, tags) {
    tags = tags || 'input:not([type=hidden]):not([type=checkbox]):not([type=radio]),select,textarea';
    var wanted = labels.map(norm);
    var match = function (t) { t = norm(t); return t && wanted.some(function (w) { return t === w || t.indexOf(w) === 0 || (w.length > 3 && t.indexOf(w) > -1); }); };
    var fields = Array.prototype.slice.call(doc.querySelectorAll(tags));
    for (var i = 0; i < fields.length; i++) {
      var f = fields[i];
      if (match(f.getAttribute('placeholder')) || match(f.getAttribute('aria-label')) || match(f.getAttribute('name'))) return f;
    }
    var lbls = Array.prototype.slice.call(doc.querySelectorAll('label, .form-label, .label, dt, th, legend, span, div'));
    for (var j = 0; j < lbls.length; j++) {
      var l = lbls[j];
      if (l.children.length > 3) continue;
      var own = l.childNodes.length ? Array.prototype.map.call(l.childNodes, function (n) { return n.nodeType === 3 ? n.textContent : (n.tagName === 'SPAN' || n.tagName === 'B' || n.tagName === 'STRONG' ? n.textContent : ''); }).join(' ') : l.textContent;
      if (!match(own.replace(/\*/g, ''))) continue;
      if (l.htmlFor) { var t = doc.getElementById(l.htmlFor); if (t) return t; }
      var inside = l.querySelector(tags); if (inside) return inside;
      var box = l.parentElement;
      for (var k = 0; k < 3 && box; k++, box = box.parentElement) { var near = box.querySelector(tags); if (near) return near; }
    }
    return null;
  }

  function find(doc, spec, tags) {
    if (!spec) return null;
    for (var i = 0; i < (spec.selectors || []).length; i++) {
      try { var el = doc.querySelector(spec.selectors[i]); if (el) return el; } catch (e) {}
    }
    return spec.labels ? byLabel(doc, spec.labels, tags) : null;
  }

  /** React/Vue gibi kütüphanelerin değeri görmesi için yerel setter kullanılır */
  function setValue(el, v) {
    var proto = el.tagName === 'TEXTAREA' ? HTMLTextAreaElement.prototype : el.tagName === 'SELECT' ? HTMLSelectElement.prototype : HTMLInputElement.prototype;
    var setter = Object.getOwnPropertyDescriptor(proto, 'value').set;
    el.focus && el.focus();
    setter.call(el, v);
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    el.blur && el.blur();
  }

  /** Seçim kutusunda metne / değere göre seçenek seçer. candidates: denenecek metinler */
  function choose(el, candidates) {
    var opts = Array.prototype.slice.call(el.options || []);
    var c = candidates.map(norm).filter(Boolean);
    var hit = null;
    c.some(function (w) { hit = opts.find(function (o) { return norm(o.textContent) === w || norm(o.value) === w; }); return !!hit; });
    if (!hit) c.some(function (w) { hit = opts.find(function (o) { var t = norm(o.textContent); return t && (t.indexOf(w) === 0 || (w.length > 2 && t.indexOf(w) > -1)); }); return !!hit; });
    if (!hit) return false;
    setValue(el, hit.value);
    return true;
  }

  function fillOne(el, value, spec) {
    if (!el || value === null || value === undefined || value === '') return 'skip';
    var cands = [String(value)];
    if (spec && spec.map && spec.map[value]) cands = spec.map[value].concat(cands);
    if (el.tagName === 'SELECT') return choose(el, cands) ? 'ok' : 'nomatch';
    if (el.type === 'radio' || el.type === 'checkbox') return 'skip';
    if (spec && spec.map && spec.map[value]) value = spec.map[value][0];
    setValue(el, String(value));
    return 'ok';
  }

  /** Özellik onay kutuları: Türkçe adlarına göre işaretler */
  function checkFeatures(doc, labels) {
    var n = 0;
    var boxes = Array.prototype.slice.call(doc.querySelectorAll('input[type=checkbox]'));
    labels.forEach(function (lbl) {
      var w = norm(lbl);
      var cb = boxes.find(function (b) {
        var l = b.id && doc.querySelector('label[for="' + CSS.escape(b.id) + '"]');
        var t = norm((l && l.textContent) || (b.closest('label') && b.closest('label').textContent) || (b.parentElement && b.parentElement.textContent) || b.value);
        return t && (t === w || t.indexOf(w) === 0 || w.indexOf(t) === 0);
      });
      if (cb && !cb.checked) { cb.click(); n++; }
    });
    return n;
  }

  /** Fotoğrafları dosya alanına yerleştirir (File nesneleri) */
  function putFiles(input, files) {
    var dt = new DataTransfer();
    files.forEach(function (f) { dt.items.add(f); });
    input.files = dt.files;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    return files.length;
  }

  function wait(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }

  /**
   * İlanı formda doldurur. data: /ext/listing cevabı; site: /ext/config sites[x]; lang: metin dili
   * onlyEmpty: çok adımlı formlarda dolu alanlara dokunma. @return Promise<{filled:[],missing:[],nomatch:[]}>
   */
  function fill(doc, data, site, opt) {
    opt = opt || {};
    var lang = opt.lang || 'tr';
    var t = (data.i18n && (data.i18n[lang] || data.i18n.tr)) || { title: data.title, description: data.description };
    var values = {
      title: t.title, description: t.description, price: Math.round(data.price), currency: data.currency, mode: data.mode,
      subtype: data.subtype, city: data.city, district: data.district, rooms: data.rooms, baths: data.baths, area_m2: data.area_m2,
      deed_type: data.deed_type, floor: data.floor, total_floors: data.total_floors, year_built: data.year_built,
      furnished: data.furnished === null || data.furnished === undefined ? null : (data.furnished ? '1' : '0')
    };
    var rep = { filled: [], missing: [], nomatch: [] };
    var order = Object.keys(site.fields).filter(function (k) { return k !== 'photos'; });
    // "after" bağımlılığı olan alanlar (ör. bölge şehirden sonra yüklenir) sona
    order.sort(function (a, b) { return (site.fields[a].after ? 1 : 0) - (site.fields[b].after ? 1 : 0); });
    var chain = Promise.resolve();
    order.forEach(function (k) {
      chain = chain.then(function () {
        var spec = site.fields[k];
        if (values[k] === null || values[k] === undefined || values[k] === '') return;
        var go = function () {
          var el = find(doc, spec);
          if (!el) { rep.missing.push(k); return; }
          if (opt.onlyEmpty && el.value && el.tagName !== 'SELECT') return;
          var r = fillOne(el, values[k], spec);
          if (r === 'ok') { rep.filled.push(k); el.classList.add('eia-filled'); } else if (r === 'nomatch') rep.nomatch.push(k);
        };
        return spec.after ? wait(spec.wait || 1200).then(go) : go();
      });
    });
    return chain.then(function () {
      var feats = Object.keys(data.feature_labels || {}).map(function (k) { return data.feature_labels[k][lang] || data.feature_labels[k].tr; });
      rep.features = feats.length ? checkFeatures(doc, feats) : 0;
      return rep;
    });
  }

  root.EIA = { fill: fill, find: find, byLabel: byLabel, putFiles: putFiles, choose: choose, norm: norm, visible: visible };
})(typeof window !== 'undefined' ? window : this);
