$(function (){
	window.setTimeout(() => {
		if ($.fn.mailme) {
			$(".mailme").mailme();
		}
	}, 150);

	if ($.fn.Lazy) {
		$('.lazy').Lazy({
			'bind' : 'event'
		});
	}
});