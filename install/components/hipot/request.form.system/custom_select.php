<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * Файл дополнительных выборок, напр. если нужно выбрать справочник стран или проч для
 * формы  $arParams["POST_NAME"] == subscribe
 * мы делаем функцию
 *

if (! function_exists('CustomRequestSelects_subscribe')) {
	/**
	 * Дополнительные выборки для формы $arParams["POST_NAME"] == subscribe
	 * @return array массив, который будет доступен в $arResult['CUSTOM_SELECTS']
	 * /
	function CustomRequestSelects_subscribe()
	{
		static $_cache;
		if (isset($_cache)) {
			return $_cache;
		}
		
		// selects
		$array = array();

		$_cache = $array;
		return $_cache;
	}
}
*/

?>