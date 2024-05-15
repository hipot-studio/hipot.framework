/**
 * Form maker framework
 * @version 2.1
 * @author hipot, 2018
 * @use $.fn.center plugin
 */
$(function(){

	/**
	 * основной js-чекер всех форм
	 */
	const __rebornCheckers = function(){

		// бежимся по всем аякс и обычным формам
		$(".ajax_form, .static_form").each(function(){

			var __formId = this.id,
				__form	 = this;

			// проверка перед отправкой
			$(".submit", __form).off('click').on('click', function(){

				let allow = true,
					__submitButton = this;

				// непустота полей
				let var1 = $('.req_inpt', __form);
				$(var1).each(function(){
					if (isEmpty($(this).val())
						|| ($(this).hasClass('email_inpt') && !isMail($(this).val()))
					) {
						$(this).addClass('fail_input');
						allow = false;
					} else {
						$(this).removeClass('fail_input');
					}
				});

				//emails (они не обязательны бывают)
				var1 = $('.email_inpt', __form);
				$(var1).each(function(){
					if ($(this).hasClass('req_inpt')) {
						return;
					}
					if (!isEmpty($(this).val()) && $(this).hasClass('email_inpt') && !isMail($(this).val())) {
						$(this).addClass('fail_input');
						allow = false;
					} else {
						$(this).removeClass('fail_input');
					}
				});

				if (! allow) {
					return false;
				}

				// spam
				$('.token', __form).remove();

				// get all post data
				let js_data = {};
				$('.ajax_form_wrapper', __form).wrap('<form></form>');
				js_data = $('form', __form).serializeJSON();

				// lock click
				$(__submitButton).data('posted', true).fadeTo(0, 0.3);

				js_data['__form__'] = __formId.replace(/_form$/, "");

				//
				// если форма обычная (не аякс), то субмитим ее
				//
				if ($(__form).hasClass('static_form')) {
					$('form', __form).trigger('submit');
					return true;
				}

				$.ajax({
					async: true,
					cache: false,
					data:  js_data,
					dataType: 'html',
					timeout: 8000,
					type: 'POST',
					url: $(__form).attr('uri'),
					error: function(jqXHR, textStatus, errorThrown){
						$(__submitButton).data('posted', false).fadeTo(0, 1);
					},
					success: function(data, textStatus, jqXHR){
						let container = $(__form).parent();

						// удаляем скрипт шаблона формы и саму старую форму
						/*$('#' + __formId + ' ~ script').remove();
						$('#' + __formId).remove();
						$(container).append(data);*/

						$('#' + __formId).html(data);
						__rebornCheckers();
					}
				});
			});
		});

		/*
		$.mask.definitions['~'] = '[+-]';
		$('input[name="PHONE"], .phone_mask').mask("+7 999 999 99 99?999999", {placeholder : "*"});
		*/

	};
	// first run
	__rebornCheckers();
});


/**
 * Закрывает открытое окно и убирает овелей
 * @param {obj} th Объект jQuery который необходима закрыть
 */
function HideWin(th, speed)
{
	if (typeof speed === 'undefined') {
		$(th).hide();
		$('#overlay').hide();
	} else {
		$(th).animate({'opacity': '0'}, speed, function () {
			$('#overlay').hide();
			$(this).hide();
		});
	}
}

/**
 * Открывает открытое окно и убирает овелей
 * @param {obj} th Объект jQuery который необходима закрыть
 */
function ShowWin(th, speed)
{
	//$("body").prepend('<div id="overlay"></div>');
	if (typeof speed === 'undefined') {
		$("#overlay").center({'resize': true});
		$('#overlay').show();
		$(th).center().show();
	} else {
		$("#overlay").center({'resize': true});
		$('#overlay').show(speed, function () {
			$(th).css({'opacity': '0', 'display': 'block'}).center().animate({'opacity': '1'}, speed);
		});
	}
}

/**
 * отображает ошибку заполненности формы
 * @param {jQuery} layer родитель-форма, в которой после заголовка .headess нужно вставить ошибку
 * @param {String} errorHtml Ошибка в виде html
 */
function ShowFillFormErrorMess(layer, errorHtml)
{
	if (errorHtml.trim() === '') {
		return;
	}

	let html = '<div class="alert-errors">';
	html += errorHtml;
	html += '</div>';
	$(html).insertAfter($('.headess', layer));
}

/**
 * убирает ошибку заполненности формы
 * @param {jQuery} layer родитель-форма, в которой после заголовка .HeaderTitle нужно вставить ошибку
 */
function ClearFillFormErrorMess(layer)
{
	$('.alert-errors', layer).remove();
}