<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * Файл дополнительных модификаций полей письма $MAIL_VARS перед отправкой
 * допустим для формы  $arParams["POST_NAME"] == subscribe
 * мы делаем функцию
 *

if (! function_exists('CustomRequestMailVars_subscribe')) {
	/**
	 * Дополнительный обработчик для формы $arParams["POST_NAME"] == subscribe
	 * для модификации полей письма $MAIL_VARS перед отправкой
	 * @param array $_post массив после успешного выполнения $arParams[_POST]
	 * @param array $arResult массив с данными, у него есть ключ $arResult['CUSTOM_SELECTS']
	 * 	из обработчиков произвольных выборок ;)
	 * @param array &$mailVars массив $mailVars, можно модифицировать
	 * /
	function CustomRequestMailVars_subscribe($_post, $arResult, &$mailVars)
	{
	}
}
*/

?>