<?
/**
 * Файл с различными глобальными рубильниками-константами
 */

/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 */

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

/**
 * На сайте бета-тестировщик
 * @var bool
 */
define('IS_BETA_TESTER', $USER->IsAdmin() || $USER->GetLogin() == 'info@hipot-studio.com' || $USER->GetEmail() == 'hipot@ya.ru');

if (
	(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') ||
	(isset($_SERVER['HTTP_BX_AJAX']) && $_SERVER['HTTP_BX_AJAX']) ||
	$request->get("testajax") == 'Y' || $request["is_ajax_post"] == 'Y' || $request->get('via_ajax') == 'Y'
) {
	$bIsAjax = true;
} else {
	$bIsAjax = false;
}

/**
 * На сайт пришел аякс-запрос
 * @var bool
 */
define("IS_AJAX", $bIsAjax);