/* Feriha Çelik teması — küçük yardımcılar (bağımlılık yok). */
(function () {
	var header = document.querySelector('.fc-header');
	if (!header) return;
	var onScroll = function () {
		header.classList.toggle('is-scrolled', window.scrollY > 8);
	};
	window.addEventListener('scroll', onScroll, { passive: true });
	onScroll();
})();
