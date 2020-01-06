/**
 * custom welmood order
 * @version 1.0
 * @author max, hipot studio
 */
$(document).ready(function () {

	/**
	 * сериализует форму в объект JSON
	 * @usage $('form').serializeJSON();
	 */
	$.fn.serializeJSON = function () {
		var json = {};
		$.map($(this).serializeArray(), function (n, i) {
			json[n['name']] = n['value'];
		});
		return json;
	};

	if ($.styler) {
		$('.tabcontent form input[type="checkbox"], .tabcontent form select, .tabcontent form input[type="radio"]').styler();
	}

	$.mask.definitions['~'] = '[+-]';
	$('input[name=\"PHONE\"], .phone_mask').mask("+7 (999) 999 99 99 ?999999", {placeholder : "_"});

	$('.tablinks').click(function () {
		var orderName = $(this).attr('data-open');
		$(".tablinks").not(this).removeClass('active');
		if (!$(this).hasClass('active')) {
			$(this).addClass('active');
		}

		$('.tabcontent').each(function () {
			if ($(this).attr('ID') == orderName) {
				if (!$(this).hasClass('active')) {
					$(this).addClass('active');
				}
			} else {
				$(this).removeClass('active');
			}
		});
	});

	$('.comment').click(function () {
		if ($(".text-comm").is(':visible')) {
			$(".text-comm").slideUp(500);
		} else if ($(".text-comm").is(':hidden')) {
			$(".text-comm").slideDown(500);
		}
	});
	$('.delivery-cost-frm').click(function () {
		var $manBlock = $(".delivery_simple_calc");
		if ($manBlock.is(':visible')) {
			$manBlock.slideUp(500);
		} else if ($manBlock.is(':hidden')) {
			$manBlock.slideDown(500);
			refreshPostage();
		}
	});

	// form fuka!
	$('input[type=\"submit\"]').click(function () {
		if ($(this).prop('clicked')) {
			return false;
		}

		var formName = $(this).data('form');
		var form = $('form[name="' + formName + '"]');
		var reqFlds = $(form).find('.required');
		var check = true;
		var successUrl = '';

		if ($(reqFlds).size() > 0) {
			$(reqFlds).removeClass('error');
			$(reqFlds).each(function () {
				if ($.trim( $(this).find('input').val() ) == '') {
					$(this).addClass('error');
					check = false;
				}
			});
		}

		if (!check) {
			return false;
		} else {
			$(this).fadeTo(180, 0.5).prop('clicked', true);

			var data = $(form).serializeJSON();
			if (formName == 'fast_form') {
				data['fast_form'] = 'Y';
			} else {
				var interval = setInterval(function(){
					$("select[name=\"CITY=\"]").val( $(".jq-selectbox__select-text:first").text() );
					$(".adress", $("select[name=\"CITY=\"]").val());
				}, 50);
			}

			//var url = location.href;
			var url = '/local/components/wellmood/custom.order/templates/.default/ajax/order.php';

			$.ajax({
				url: url,
				type: 'POST',
				data: data,
				dataType: 'html',
				success: function (resp) {

					try {
						eval('var resp = ' + resp + ';');
					} catch (e) {
						$.fancybox('<h1>Что-то пошло не так при отправке формы: <small><code>'+resp+'</code></small>' + '</h1>');
						return;
					}

					if ($.trim(resp['ERROR']) == '' && resp['ORDER_ID']) {

						//Формируем url ответа, в зависимости от того
						//какая форма была заполнена
						if (formName == 'full_form') {
							successUrl = location.href+'?ORDER_ID='+resp['ORDER_ID'];
						} else if (formName == 'fast_form') {
							successUrl = location.href+'?ORDER_ID='+resp['ORDER_ID']+'&fast_order=Y';
						}
						location.href = successUrl;
						//resp = resp['ANSWER'];
					}
					/*$.fancybox('<h1>' + resp + '</h1>');
					console.log(resp);*/
				},
				error: function (jqXHR, textStatus, errorThrown) {
					$.fancybox('error: ' + textStatus + ' ' + errorThrown);
					//console.log(jqXHR, textStatus, errorThrown);
				}
			});


			$(this).fadeTo(0, 1).prop('clicked', false);

			// no post
			return false;
		}
	});


	$('.delivery_simple_calc').hide();

	$(".order-right form").submit(function(){
		return false;
	});

});


function refreshPostage() {
	var container = $('.postage').show();
	var location = $('#LOCATION').val();

	if ($(container).length) {
		$(container).html('<img src="' + $('.item,.order-right').data('templatefolder') + '/images/ajax-loader.gif" alt=" " title=" " />');

		$.ajax({
			type: "POST",
			url: '/ajax/postage.php',
			data: {'TF_LOCATION_SELECTED_CITY': location},
			success: function (data) {
				window.setTimeout(function(){
					$('.postage').html(data);
					$.fancybox.hideLoading();
				}, 500);
			}
		});

		$.post().success();
	}
}
