/**
 * Обновлено:
 * isNum - добавлены коды цифр с NumPad`а + TAB
 * isPhone - добавлены коды знаков "-", "+" основной и цифровой клавиатур
 *
 * hipot js main lib
 * @version 2.7 2024
 */
(function ($) {
	/**
	 * Плагин для сокрытия емейлов
	 * @memberOf JQuery
	 */
	$.fn.mailme = function () {
		let at = / AT /,
			dot = / DOT /g;

		return this.each(function () {
			let text = $(this).text(),
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
	 * Плагин для сокрытия емейлов у ссылки
	 * @memberOf JQuery
	 */
	$.fn.mailmea = function () {
		let at = / AT /,
			dot = / DOT /g;

		return this.each(function () {
			let text = $(this).data('mailme'),
				addr = text.replace(at, '@').replace(dot, '.');
			$(this).attr('href', 'mailto:' + addr);
		});
	};

	/**
	 * сериализует форму в объект JSON
	 * @usage $('form').serializeJSON();
	 * @memberOf jQuery
	 * @deprecated use new FormData()
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
	 * @memberOf jQuery
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
	 * @since 1.1.0
	 * @memberOf jQuery
	 * @function triggerChangeAttribute
	 *
	 * @param {Object} options - The options for the trackValueChange plugin.
	 * @param {function} options.onChange - The callback function to execute when the value changes.	 *
	 * @returns {jQuery} The jQuery object for chaining.
	 * @example
	 * $("input[type=hidden]").triggerChangeAttribute("value");
	 * $("#sessid").on('change', () => { console.log('change trigger!'); })
	 * $("#sessid").val('change hidden');
	 */
	$.fn.triggerChangeAttribute = function (attributeName) {
		const MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
		let trackChange = function(element) {
			let observer = new MutationObserver(function(mutations, observer) {
				if (mutations[0].attributeName == attributeName) {
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
	$.extend($.fn, {
		/** @memberOf jQuery */
		clearSelect: function (defaultOption) {
			return this.each(function () {
				if (this.tagName == 'SELECT') {
					this.options.length = 0;
					if (!defaultOption) {
						return;
					}
					this.add(defaultOption, null);
				}
			});
		},
		/** @memberOf jQuery */
		fillSelect: function (dataArray, defaultOption) {
			return this.clearSelect(defaultOption).each(function () {
				if (this.tagName == 'SELECT') {
					let currentSelect = this;
					$.each(dataArray, function (index, data) {
						let option = new Option(data.name, data.value);
						currentSelect.add(option, null);
					});
				}
			});
		}
	});
})(jQuery);

/**
 * @deprecated
 * @param str
 * @returns {*}
 */
function trim(str) {
	return str.toString().trim();
}

function ltrim(str) {
	return str.replace(new RegExp("^\\s+", "g"), "");
}

function rtrim(str) {
	return str.replace(new RegExp("\\s+$", "g"), "");
}

/**
 * Проверяет строку на пустоту
 * @param str строка для проверки
 * @returns {Boolean}
 */
function isEmpty(str)
{
	return !str || str.toString().trim().length <= 0;
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
 * @param {int} cCode код клавиши
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
 * Check if a given string represents a valid email address. (строго, но не жадно!)
 *
 * @param {string} str - The string to be checked.
 * @returns {boolean} - True if the string is a valid email address, false otherwise.
 */
function isMail(str)
{
	return /^[=_.0-9a-z+~-]+@(([-0-9a-z_]+\.)+)([a-z]{2,10})$/i.test(str);
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
 * Разбивает хеш строку, сформированную по типу GET параметов, в объект [key=val]
 * @returns {Object}
 */
function hashAsObject()
{
	const hs = location.hash.substring(1);
	const har = hs.split('&');
	const opts = {};
	for (let i in har) {
		let v = har[i].split('=');
		opts[v[0]] = v[1];
	}
	return opts;
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

/**
 * Retrieve the result from a URL using AJAX POST request.
 *
 * @param {string} url - The URL to send the request to.
 * @param {object|FormData} request - The parameters to send along with the request.
 * @param {function} success - The function to be invoked upon successful response.
 * @param {object} params - The parameters of ajax call, see params of $.ajax
 * @param {function} error - The function to be invoked upon error response.
 * @return {void}
 * @see {https://api.jquery.com/jQuery.ajax/}
 */
function getResultFromUrl(url, request, success, params, error)
{
	if (typeof params === 'undefined') {
		params = {};
	}
	if (request instanceof FormData) {
		$.extend( params, {
			// support send files
			processData: false,
			contentType: false,
		});
	}

	$.ajax($.extend({
		async: true,
		cache: false,
		data: request,
		dataType: 'json',
		timeout: 8000,
		type: 'POST', /*method in jquery 1.9+*/
		url: url,
		error: function (jqXHR, textStatus, errorThrown) {
			if (funcDefined(error)) {
				error(jqXHR, textStatus, errorThrown);
			}
		},
		success: success
	}, params));
}

/**
 * @param {string} filename
 * @param {function(): *} getDataUri to get data uri
 */
function downloadFile(filename, getDataUri)
{
	let link = document.createElement('a');
	link.download = filename;
	link.href = getDataUri();
	link.click();
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
		let len = src.length,
			res = [];
		let listimg = function () {
			for (let i in src) {
				let img = new Image();
				if ('addEventListener' in img) {
					img.addEventListener('load', function () {
						let ret = true;
						if (funcDefined(then)) {
							ret = then.call(this);
						}
						res.push(ret);
					}, false);
				} else {
					img.attachEvent('onload', function () {
						let ret = true;
						if (funcDefined(then)) {
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
				if (funcDefined(thenAll)) {
					thenAll(res);
				}
			} else {
				listimg();
				setTimeout(arguments.callee, 1);
			}
		}, 1);
	} else {
		let img = new Image();
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
 * Displays a confirmation dialog with specified text, an action to execute on confirmation, and customizable button text and color.
 *
 * @param {string} confirmTxt - The confirmation message to display in the dialog.
 * @param {Function} action - The callback function to execute when the confirmation button is clicked.
 * @param {string} btnOkTxt - The text to display on the confirmation button.
 * @param {string} [btnOkColor] - The optional color for the confirmation button ex.: BX.UI.Button.Color.DANGER.
 * @return {void}
 */
function confirmAndDoAction(confirmTxt, action, btnOkTxt, btnOkColor)
{
	if (typeof btnOkColor === 'undefined') {
		btnOkColor = BX.UI.Button.Color.PRIMARY;
	}

	BX.UI.Dialogs.MessageBox.show({
		message: confirmTxt,
		title: "",
		modal: true,
		buttons: [
			new BX.UI.Button(
				{
					color: btnOkColor,
					text: btnOkTxt,
					onclick: function(button, event) {
						action();
						button.context.close();
					}
				}
			),
			new BX.UI.CancelButton(
				{
					color: BX.UI.Button.Color.LINK,
					onclick: function(button, event) {
						button.context.close();
					}
				}
			)
		],
	});
	/*BX.UI.Dialogs.MessageBox.confirm(confirmTxt, '', (messageBox) => {
		action();
		messageBox.close();
	}, btnOkTxt);*/
}