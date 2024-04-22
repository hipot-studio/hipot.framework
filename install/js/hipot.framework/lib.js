/**
 * Обновлено:
 * isNum - добавлены коды цифр с NumPad`а + TAB
 * isPhone - добавлены коды знаков "-", "+" основной и цифровой клавиатур
 *
 * hipot js lib
 * @version 2.6 2023
 */
(function ($) {
	/**
	 * Плагин для сокрытия емейлов
	 * @memberOf JQuery
	 */
	$.fn.mailme = function () {
		var at = / AT /;
		var dot = / DOT /g;

		return this.each(function () {
			var text = $(this).text(),
				span_class = $(this).attr('class'),
				addr = text.replace(at, '@').replace(dot, '.'),
				rgx = new RegExp(text),
				html = $(this).html().replace(rgx, addr),
				link = $('<a href="mailto:' + addr + '">' + html + '</a>');
			link.addClass(span_class);
			$(this).after(link);
			$(this).remove();
		});
	};

	/**
	 * сериализует форму в объект JSON
	 * @usage $('form').serializeJSON();
	 */
	$.fn.serializeJSON = function () {
		let data = {};
		function buildInputObject(arr, val) {
			if (arr.length < 1)
				return val;
			let objkey = arr[0];
			if (objkey.slice(-1) == "]") {
				objkey = objkey.slice(0,-1);
			}
			let result = {};
			if (arr.length == 1){
				result[objkey] = val;
			} else {
				arr.shift();
				let nestedVal = buildInputObject(arr,val);
				result[objkey] = nestedVal;
			}
			return result;
		}
		$.each(this.serializeArray(), function() {
			let val = this.value;
			let c = this.name.split("[");
			let a = buildInputObject(c, val);
			$.extend(true, data, a);
		});
		return data;
	};

	/**
	 * Проверяет код нажатой клавиши для полей типа "телефон"
	 * Разрешены символы: 0-9 + - \s ( )
	 * Разрешены комбинации: Backspace, ctrl + v, ctrl + c, ctrl + r
	 * 
	 * @returns {Boolean}
	 */
	$.fn.checkPhone = function () {
		return this.each(function () {
			$(this).unbind().keydown(function (e) {
				if ((e.ctrlKey == true && (e.keyCode != 67 || e.keyCode != 86 || e.keyCode != 82)) || e.key == 'Backspace') {
					return true; // пускаем ctrl + ( v c r )
				}
				if (e.key && e.key.search(/[^0-9\(\)\+\-\s]/i) != -1) {
					return false;
				}
			})
		}).keyup(function (e) {
			$(this).val($(this).val().replace(/[^0-9\(\)\+\-\s]+/gi, ''));
		});
	};

	/**
	 * jQuery plugin that trigger changes to the value of an input field (to track hidden fields)
	 *
	 * @since 1.0.0
	 * @memberOf jQuery.fn
	 * @function triggerValueChange
	 *
	 * @param {Object} options - The options for the trackValueChange plugin.
	 * @param {function} options.onChange - The callback function to execute when the value changes.	 *
	 * @returns {jQuery} The jQuery object for chaining.
	 * @example
	 * $("input[type=hidden]").triggerValueChange();
	 * $("#sessid").on('change', () => { console.log('change trigger!'); })
	 * $("#sessid").val('change hidden');
	 */
	$.fn.triggerValueChange = function () {
		const MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
		let trackChange = function(element) {
			let observer = new MutationObserver(function(mutations, observer) {
				if(mutations[0].attributeName == "value") {
					$(element).trigger("change");
				}
			});
			observer.observe(element, {
				attributes: true
			});
		}
		return this.each(function () {
			trackChange(this);
		});
	};

	/**
	 * Плагин для работы с выпадающими списками SELECT
	 *
	 * Примеры:
	 * // очистка селекта (вариантов option)
	 * $("select").clearSelect();
	 *
	 * // заполнение селекта (вариантами option)
	 * $("select").fillSelect([
	 * 	{name : '123_name', value : 123},
	 * 	{name : '456_name', value : 456}
	 * ]);
	 *
	 * @version 1.0
	 */
	jQuery.extend(jQuery.fn, {
		/** @memberOf jQuery */
		clearSelect: function(defaultOption) {
			return this.each(function(){
				if (this.tagName == 'SELECT') {
					this.options.length = 0;
					if (! defaultOption) {
						return;
					}
					if ($.support.cssFloat) {
						this.add(defaultOption, null);
					} else {
						this.add(defaultOption);
					}
				}
			});
		},
		/** @memberOf jQuery */
		fillSelect: function(dataArray, defaultOption) {
			return this.clearSelect(defaultOption).each(function(){
				if (this.tagName == 'SELECT') {
					var currentSelect = this;
					$.each(dataArray, function(index, data) {
						var option = new Option(data.name, data.value);
						if($.support.cssFloat) {
							currentSelect.add(option, null);
						} else {
							currentSelect.add(option);
						}
					});
				}
			});
		}
	});
})(jQuery);


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


/**
 * Проверяет строку на пустоту
 * @param str строка для проверки
 * @returns {Boolean}
 */
function isEmpty(str)
{
	if ($.trim(str).length > 0) {
		return false;
	} else {
		return true;
	}
}

/**
 * Проверяет код нажатой клавиши, и возвращает true, если это цифра
 * @param int cCode код клавиши
 * @returns {Boolean}
 */
function isNum(cCode)
{
	if ((cCode >= 48 && cCode <= 57) || (cCode >= 96 && cCode <= 105) || (cCode >= 17 && cCode <= 20) || cCode == 27 || cCode == 0 || cCode == 127 || cCode == 8 || cCode == 9) {
		return true;
	} else {
		return false;
	}
}
/**
 * Проверяет код нажатой клавиши для полей типа "телефон"
 * @param int cCode код клавиши
 * @returns {Boolean}
 *
 * FIXME перестала работать
 */
function isPhone(cCode)
{
	// позволяю пробел, скобки () и знак + - (и комбинацию "вставить" - нельзя)
	if (cCode == 32 || cCode == 40 || cCode == 41 || cCode == 43 || cCode == 45 || cCode == 107 || cCode == 109 || cCode == 189 || cCode == 187) {
		return true;
	} else {
		return isNum(cCode);
	}
}

/**
 * Проверяет емайл по регулярке (строго, но не жадно!)
 * @param {String} str строка для проверки
 * @returns {Boolen}
 * @version 1.0
 */
function isMail(str)
{
	return /^[=_.0-9a-z+~-]+@(([-0-9a-z_]+\.)+)([a-z]{2,10})$/i.test(str);
}

/**
 * получить данные по урлу, передав ему параметры и выполнив функцию при получении данных
 * @param string url
 * @param object params
 * @param callback success function(data, textStatus, jqXHR){}
 */
function getResultFromUrl(url, params, success)
{
	$.ajax({
		async: true,
		cache: false,
		data: params,
		dataType: 'html',
		timeout: 8000,
		type: 'POST',
		url: url,
		error: function (jqXHR, textStatus, errorThrown) {
		},
		success: success
	});
}

/**
 * Analog PHP htmlspecialchars
 * @param text
 * @returns
 * @see http://stackoverflow.com/questions/1787322/htmlspecialchars-equivalent-in-javascript
 */
function escapeHtml(text)
{
	let map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return text.replace(/[&<>"']/g, function (m) {
		return map[m];
	});
}

/**
 * Предзагрузчик картинок, исполняет функцию then и thenAll после загрузки, соответственно, каждой картинки и всех картинок.
 *
 * @param src {string|array} Путь к картинке, может быть строкой(одна картинка), или массивом строк(несколько картинок).
 * @param then {function} Callback функция исполняется после загрузки каждой из картинок.
 *          Здесь this - загруженная картинка(объект Image()).
 * @param thenAll { function(ar) } Callback функция исполняется после загрузки всех картинок.
 *           Здесь ar - массив всех значений возвращаемых функцией then.
 */
function imgPreloader(src, then, thenAll)
{
	if (typeof src == 'object') {
		var len = src.length,
			res = [];
		var listimg = function () {
			for (var i in src) {
				var img = new Image();
				if ('addEventListener' in img) {
					img.addEventListener('load', function () {
						var ret = true;
						if (typeof then == 'function') {
							ret = then.call(this);
						}
						res.push(ret);
					}, false);
				} else {
					img.attachEvent('onload', function () {
						var ret = true;
						if (typeof then == 'function') {
							ret = then.call(this);
						}
						res.push(ret);
					});
				}
				img.src = src[i];
			}
		};
		listimg();
		setTimeout(function () {
			if (res.length >= len) {
				if (typeof thenAll == 'function')
					thenAll(res);
			} else {
				listimg();
				setTimeout(arguments.callee, 1);
			}
		}, 1);
	} else {
		var img = new Image();
		if ('addEventListener' in img) {
			img.addEventListener('load', function () {
				then.call(this);
			}, false);
		} else {
			img.attachEvent('onload', function () {
				then.call(this);
			});
		}
		img.src = src;
	}
}

/**
 * @deprecated
 * @param str
 * @returns {*}
 */
function trim(str) {
	return ltrim(rtrim(str));
}

function ltrim(str) {
	return str.replace(new RegExp("^\\s+", "g"), "");
}

function rtrim(str) {
	return str.replace(new RegExp("\\s+$", "g"), "");
}

/**
 * Разбивает хеш строку, сформированную по типу GET параметов, в объект [key=val]
 * @returns {Object}
 */
function hashAsObject()
{
	const hs = location.hash.substr(1);
	const har = hs.split('&');
	const opts = {};
	for (let i in har) {
		let v = har[i].split('=');
		opts[v[0]] = v[1];
	}
	return opts;
}

/**
 * Attaches a right-click event handler to all the images within the <body> of the current document,
 * preventing the default context menu from being displayed.
 *
 * @function rightClick
 * @returns {undefined}
 */
function rightClick()
{
	$('body').on('contextmenu', 'img', function (e) {
		e.preventDefault();
	});
}

/**
 * Requires JavaScript libraries and executes a logic function
 *
 * @param {Array<string>} libs - An array of library URLs to be loaded
 * @param {Function} logik - The function to be executed after loading the libraries
 *
 * @return {undefined}
 */
function requireJJs(libs, logik)
{
	BX.loadScript(libs, function () {
		logik();
	});
	// console.info(libs);
}

/**
 * Check if a function is defined.
 *
 * @param {string|function} func - The function or the name of the function to check.
 * @returns {boolean} - Returns true if the function is defined, otherwise false.
 */
function funcDefined(func)
{
	try {
		if (typeof func != 'function') {
			return typeof window[func] === "function";
		} else {
			return true;
		}
	} catch (e) {
		return false;
	}
}

/**
 * Проверяет, существует ли ключ в объекте или массиве по указанному пути.
 * @param {Object | Array} obj - Исходный объект или массив
 * @param {string} path - Путь к элементу в объекте в формате "key1.key2.key3".
 * @returns {boolean} Возвращает true, если ключ существует, и false в противном случае.
 * @example
 * issetItem(typeof newObj != "undefined" ? newObj : null, 'key1.key2.key3')
 */
function issetItem(obj, path)
{
	if (!obj || !path || path.trim() === "") {
		return false;
	}
	let current = obj;
	for (const key of path.split(".")) {
		if (!current.hasOwnProperty(key)) {
			return false;
		}
		current = current[key];
	}
	return true;
}