/* Müsait günler takvimi: güne dokununca aç/kapat. */
(function () {
	var input = document.getElementById('fr-days');
	if (!input) return;
	var state = {};
	try { state = JSON.parse(input.value) || {}; } catch (e) { state = {}; }
	document.querySelectorAll('.fr-day:not([disabled])').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var date = btn.dataset.date;
			var weekly = btn.dataset.weekly === '1';
			var nowOpen = !btn.classList.contains('is-open');
			btn.classList.toggle('is-open', nowOpen);
			btn.classList.toggle('is-closed', !nowOpen);
			btn.setAttribute('aria-pressed', nowOpen ? 'true' : 'false');
			if (nowOpen === weekly) { delete state[date]; } else { state[date] = nowOpen ? 'open' : 'closed'; }
			input.value = JSON.stringify(state);
			if (!nowOpen && btn.classList.contains('has-booking')) {
				window.alert('Bu günde alınmış randevu var. Günü kapatmak mevcut randevuları iptal etmez; gerekirse "Randevularım" ekranından iptal edin.');
			}
		});
	});
	window.addEventListener('beforeunload', function () {});
})();
