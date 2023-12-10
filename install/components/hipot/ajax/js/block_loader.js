/**
 * скрипт отслеживания попадания динамического блока в viewport
 * @author hipot studio
 */
$(function (){
	const options = {
		root: null,
		rootMargin: '0px',
		threshold: 0
	};
	// @see https://habr.com/ru/post/494670/
	const observer = new IntersectionObserver((entries, observer) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				// when block is show
				const block = entry.target, $block = $(block);

				BX.ajax.runComponentAction('hipot:ajax', 'loadDynamicBlock', {
					mode: 'class',
					signedParameters: $block.data('block-params'),
					data: {
						'blockName' : $block.data('block'),
						'lang'      : BX.message('LANGUAGE_ID')
					},
					method: 'POST'
				}).then(function(response){
					if (response.status === 'success') {
						$block.fadeOut(0).html( response.data.HTML ).fadeIn(200);
					} else {
						console.log(response);
					}
				});

				observer.unobserve(block);
			}
		});
	}, options);

	const arr = document.querySelectorAll("*[data-decomposed-block]");
	arr.forEach(i => {
		observer.observe(i)
	});
});
