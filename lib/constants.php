<? /** @noinspection GlobalVariableUsageInspection */
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
 */
define("IS_AJAX", $bIsAjax);

/**
 * На сайте бета-тестировщик
 */
define('IS_BETA_TESTER', $USER->IsAdmin() || $USER->GetEmail() == 'hipot@ya.ru' || str_contains($USER->GetLogin(), '@hipot-studio.com'));

/**
 * Группа контент-редактора
 */
const CONTENT_MANAGER_GID = 0;      // TODO set correct group

/**
 * На сайте редактор
 */
define('IS_CONTENT_MANAGER', IS_BETA_TESTER || CSite::InGroup([CONTENT_MANAGER_GID]));

/**
 * Should PhpCacher use tagged cache in callback-function
 * @var bool PHPCACHER_TAGGED_CACHE_AUTOSTART
 */
const PHPCACHER_TAGGED_CACHE_AUTOSTART = true;

/**
 * The default cache service for PHPCacher.
 *
 * The default cache service is set to use APC cache.
 * This means that when PHPCacher is used without explicitly providing a cache service,
 * it will use APC cache as the default.
 *
 * @var string PHPCACHER_DEFAULT_CACHE_SERVICE
 */
const PHPCACHER_DEFAULT_CACHE_SERVICE = 'cache.apc';