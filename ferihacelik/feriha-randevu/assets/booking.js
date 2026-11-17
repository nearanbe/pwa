/* Feriha Randevu — ön yüz formu (bağımlılık yok). */
(function () {
	document.querySelectorAll('.fr-booking').forEach(init);

	function init(root) {
		var cfg = JSON.parse(root.dataset.config);
		var form = root.querySelector('.fr-form');
		var daysEl = root.querySelector('.fr-cal__days');
		var titleEl = root.querySelector('.fr-cal__title');
		var prevBtn = root.querySelector('.fr-cal__prev');
		var nextBtn = root.querySelector('.fr-cal__next');
		var slotsEl = root.querySelector('.fr-slots');
		var slotsMsg = root.querySelector('.fr-slots-msg');
		var errEl = root.querySelector('.fr-error');
		var sumEl = root.querySelector('.fr-summary');
		var doneEl = root.querySelector('.fr-done');
		var submit = root.querySelector('.fr-submit');
		var y = cfg.year, m = cfg.month, selDate = '', selTime = '';
		var minKey = cfg.year * 12 + cfg.month;
		var maxKey = +cfg.maxMonth.slice(0, 4) * 12 + +cfg.maxMonth.slice(5, 7);

		function get(action, params) {
			var q = new URLSearchParams(params); q.set('action', action);
			return fetch(cfg.ajax + '?' + q.toString(), { credentials: 'same-origin' }).then(function (r) { return r.json(); });
		}
		function pad(n) { return (n < 10 ? '0' : '') + n; }
		function human(ymd) {
			var p = ymd.split('-'), d = new Date(+p[0], +p[1] - 1, +p[2]);
			return +p[2] + ' ' + cfg.months[+p[1] - 1] + ' ' + p[0] + ' ' + cfg.days[(d.getDay() + 6) % 7];
		}
		function showError(msg) { errEl.textContent = msg; errEl.hidden = !msg; if (msg) errEl.scrollIntoView({ behavior: 'smooth', block: 'center' }); }

		function renderMonth() {
			var key = y * 12 + m;
			prevBtn.disabled = key <= minKey;
			nextBtn.disabled = key >= maxKey;
			titleEl.textContent = cfg.months[m - 1] + ' ' + y;
			daysEl.innerHTML = '<p class="fr-loading">Yükleniyor…</p>';
			get('fr_month', { y: y, m: m }).then(function (res) {
				daysEl.innerHTML = '';
				var first = new Date(y, m - 1, 1), offset = (first.getDay() + 6) % 7;
				for (var i = 0; i < offset; i++) daysEl.appendChild(document.createElement('span'));
				var n = new Date(y, m, 0).getDate(), any = false;
				for (var d = 1; d <= n; d++) {
					var ymd = y + '-' + pad(m) + '-' + pad(d);
					var count = res.success ? (res.data[ymd] || 0) : 0;
					var b = document.createElement('button');
					b.type = 'button';
					b.textContent = d;
					b.className = 'fr-day' + (count ? ' is-free' : '') + (ymd === selDate ? ' is-selected' : '');
					b.disabled = !count;
					b.dataset.date = ymd;
					b.setAttribute('aria-label', human(ymd) + (count ? ' — müsait' : ' — dolu / kapalı'));
					if (count) any = true;
					b.addEventListener('click', onDay);
					daysEl.appendChild(b);
				}
				if (!any) {
					var p = document.createElement('p');
					p.className = 'fr-empty';
					p.textContent = 'Bu ayda boş gün kalmadı. Sonraki aya bakabilirsiniz.';
					daysEl.appendChild(p);
				}
			}).catch(function () { daysEl.innerHTML = '<p class="fr-empty">Takvim yüklenemedi. Sayfayı yenileyin.</p>'; });
		}

		function onDay(e) {
			selDate = e.currentTarget.dataset.date; selTime = '';
			daysEl.querySelectorAll('.is-selected').forEach(function (x) { x.classList.remove('is-selected'); });
			e.currentTarget.classList.add('is-selected');
			slotsEl.innerHTML = ''; slotsMsg.textContent = 'Saatler yükleniyor…'; slotsMsg.hidden = false;
			updateSummary();
			get('fr_slots', { d: selDate }).then(function (res) {
				var list = res.success ? res.data : [];
				if (!list.length) { slotsMsg.textContent = 'Bu günde boş saat kalmadı. Başka bir gün seçin.'; return; }
				slotsMsg.textContent = human(selDate) + ' için uygun saatler:';
				list.forEach(function (t) {
					var b = document.createElement('button');
					b.type = 'button'; b.className = 'fr-slot'; b.textContent = t;
					b.setAttribute('role', 'radio'); b.setAttribute('aria-checked', 'false');
					b.addEventListener('click', function () {
						selTime = t;
						slotsEl.querySelectorAll('.fr-slot').forEach(function (x) { x.classList.remove('is-selected'); x.setAttribute('aria-checked', 'false'); });
						b.classList.add('is-selected'); b.setAttribute('aria-checked', 'true');
						updateSummary();
						var next = root.querySelector('[data-step="4"]');
						if (next && window.innerWidth < 782) next.scrollIntoView({ behavior: 'smooth', block: 'start' });
					});
					slotsEl.appendChild(b);
				});
				if (window.innerWidth < 782) root.querySelector('[data-step="3"]').scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
		}

		function updateSummary() {
			if (!selDate) { sumEl.hidden = true; return; }
			var type = form.querySelector('[name=type]:checked').value === 'online' ? 'Online' : 'Yüz yüze';
			sumEl.hidden = false;
			sumEl.textContent = 'Seçiminiz: ' + human(selDate) + (selTime ? ', saat ' + selTime : ' (saat seçilmedi)') + ' · ' + type + ' · ' + form.service.value;
		}

		prevBtn.addEventListener('click', function () { if (--m < 1) { m = 12; y--; } renderMonth(); });
		nextBtn.addEventListener('click', function () { if (++m > 12) { m = 1; y++; } renderMonth(); });
		form.addEventListener('change', updateSummary);

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			showError('');
			if (!selDate || !selTime) return showError('Lütfen takvimden bir gün ve saat seçin.');
			if (form.name.value.trim().length < 3) return showError('Lütfen adınızı ve soyadınızı yazın.');
			if (form.phone.value.replace(/\D/g, '').length < 7) return showError('Lütfen geçerli bir telefon numarası yazın.');
			if (!form.consent.checked) return showError('Devam etmek için KVKK onay kutusunu işaretleyin.');
			var fd = new FormData(form);
			fd.set('action', 'fr_book'); fd.set('date', selDate); fd.set('time', selTime);
			submit.disabled = true; submit.textContent = 'Gönderiliyor…';
			fetch(cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (res) {
				if (!res.success) {
					showError(res.data || 'Bir sorun oluştu.');
					submit.disabled = false; submit.textContent = 'Randevu talebini gönder';
					if (/müsait değil|başka biri/.test(res.data || '')) { renderMonth(); slotsEl.innerHTML = ''; selTime = ''; }
					return;
				}
				var d = res.data, html = '<div class="fr-done__icon" aria-hidden="true">✓</div><h3>' + esc(d.message || 'Teşekkürler!') + '</h3>';
				if (d.when) html += '<p><strong>' + esc(d.when) + '</strong> · ' + esc(d.type) + '</p>';
				html += '<p>' + (d.email ? 'Bilgilendirme e-postanıza gönderildi. ' : '') + 'Sorularınız için bize her zaman ulaşabilirsiniz.</p>';
				if (cfg.wa) html += '<p><a class="fr-wa wp-element-button" href="' + cfg.wa + '" target="_blank" rel="noopener">WhatsApp ile yazın</a></p>';
				form.hidden = true; doneEl.innerHTML = html; doneEl.hidden = false; doneEl.focus();
				doneEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}).catch(function () {
				showError('Bağlantı hatası. Lütfen tekrar deneyin.');
				submit.disabled = false; submit.textContent = 'Randevu talebini gönder';
			});
		});

		function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
		renderMonth();
	}
})();
