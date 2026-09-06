(function () {
	'use strict';

	var loadedMetrica = false,
		metricaId = 0,
		timerId;

	// Для бота Яндекса грузим Метрику сразу без "отложки",
	// чтобы в панели Метрики были зелёные кружочки
	// при проверке корректности установки счётчика.
	if (navigator.userAgent.indexOf('YandexMetrika') > -1) {
		loadMetrica();
	} else {
		window.addEventListener('scroll', loadMetrica, {passive: true});
		window.addEventListener('touchstart', loadMetrica, {passive: true});
		document.addEventListener('mouseenter', loadMetrica);
		document.addEventListener('click', loadMetrica);
		document.addEventListener('DOMContentLoaded', loadFallback);
	}

	function loadFallback() {
		timerId = setTimeout(loadMetrica, 1000);
	}

	function loadMetrica(e) {
		if (e && e.type) {
			console.log(e.type);
		} else {
			console.log('DOMContentLoaded');
		}

		if (loadedMetrica) {
			return;
		}

		// Yandex.Maetrika counter
		(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)}; m[i].l=1*new Date(); for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }} k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)}) (window, document, "script", "https://cdn.jsdelivr.net/npm/yandex-metrica-watch/tag.js", "ym");
		ym(metricaId, "init", { clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:true, trackHash:true });
		// /Yandex.Metrika counter

		loadedMetrica = true;
		clearTimeout(timerId);

		window.removeEventListener('scroll', loadMetrica);
		window.removeEventListener('touchstart', loadMetrica);
		document.removeEventListener('mouseenter', loadMetrica);
		document.removeEventListener('click', loadMetrica);
		document.removeEventListener('DOMContentLoaded', loadFallback);
	}
})();