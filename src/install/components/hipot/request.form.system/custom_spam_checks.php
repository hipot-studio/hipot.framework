<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * Файл дополнительных проверок на СПАМ, напр. для проверки формы
 * формы  $arParams["POST_NAME"] == subscribe
 * мы делаем функцию
 *

if (! function_exists('CustomSpamChecks_subscribe')) {
	/**
	 * Дополнительные выборки для формы $arParams["POST_NAME"] == subscribe
	 * @param array &$arParams массив входных параметров
	 * /
	function CustomSpamChecks_subscribe(&$arParams)
	{
		$bSpam = false;

		if (preg_match('#(<a\s*href\s*=)|(\[url\s*=)|(link\s*=)#isu', $_POST[ $arParams['POST_NAME'] ]['message'])) {
			$bSpam = true;
		}

		if (trim($arParams['_POST'][ $arParams['POST_NAME'] ]['mail']) != '' && !check_email(trim($arParams['_POST'][ $arParams['POST_NAME'] ]['mail']))) {
			$bSpam = true;
		}

		if (! preg_match('#^[\(\)\-\+ 0-9]+$#', trim($arParams['_POST'][ $arParams['POST_NAME'] ]['phone']))) {
			$bSpam = true;
		}

		if ($bSpam) {
			unset($arParams['_POST'][ $arParams['POST_NAME'] ]);
		}
	}
}
*/


if (! function_exists('CustomSpamChecks_order')) {
	/**
	 * Дополнительные выборки для формы $arParams["POST_NAME"] == order
	 * @param array &$arParams массив входных параметров
	 */
	function CustomSpamChecks_order(&$arParams)
	{
		$bSpam = false;

		if (preg_match('#(<a\s*href\s*=)|(\[url\s*=)|(link\s*=)#isu', $_POST[ $arParams['POST_NAME'] ]['message'])) {
			$bSpam = true;
		}

		if (trim($arParams['_POST'][ $arParams['POST_NAME'] ]['mail']) != '' && !check_email(trim($arParams['_POST'][ $arParams['POST_NAME'] ]['mail']))) {
			$bSpam = true;
		}

		if (! preg_match('#^[\(\)\-\+ 0-9]+$#', trim($arParams['_POST'][ $arParams['POST_NAME'] ]['phone']))) {
			$bSpam = true;
		}

		if ($bSpam) {
			unset($arParams['_POST'][ $arParams['POST_NAME'] ]);
		}
	}
}

?>