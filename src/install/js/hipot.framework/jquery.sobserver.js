/**
 * Плагин простого нахождения попадания блока в displayPort и выполнения method для него
 * @version 1.0.0-dev
 * @author info@hipot-studio.com
 * @see https://habr.com/ru/post/494670/
 */
(function($) {
	/**
	 * @param {method:function} optionsRaw
	 * @returns {*}
	 * @example
	 * if ($.fn.sObserver) {
	 * 		$("*[data-observer-block-bg]").sObserver({
	 * 			'method' : function ($block) {
	 * 				$block.addClass('filled');
	 * 			}
	 * 		});
	 * 	}
	 */
	$.fn.sObserver = function(optionsRaw){
		const options = jQuery.extend({
			root: null,
			rootMargin: '0px',
			threshold: 0,
			method: function ($block) {
				console.info('sObserver', $block);
			}
		}, optionsRaw);

		const observer = new IntersectionObserver((entries, observer) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					// when block is show
					const block = entry.target, $block = $(block);

					options.method($block);

					observer.unobserve(block);
				}
			});
		}, options);

		return this.each(function(){
			observer.observe(this);
		});
	}
})(jQuery);