<? /** @noinspection GlobalVariableUsageInspection */
defined('B_PROLOG_INCLUDED') || die();

/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 */

global $APPLICATION;

\CHTTP::setStatus('404 Not Found');
if (!defined('ERROR_404')) {
	define('ERROR_404', 'Y');
}
?>
<?
if (LANGUAGE_ID == 'en') {
	$APPLICATION->SetTitle("Page not found");
	?>
	Dear visitor! Unfortunately, the page you requested is unavailable. This can happen for the following reasons:
	<br>
	<ul>
		<li>page has been deleted</li>
		<li>page has been renamed</li>
		<li>you made a mistake in the address (https://<?=$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']?>)</li>
	</ul>
	<br>
	Please go to <a href="<?=SITE_DIR?>">home page</a> site and try again or use <strong>site map</strong>:
	
	<?
	/*\Bitrix\Main\Config\Option::set("main", "map_top_menu_type", 	"en_top");
	\Bitrix\Main\Config\Option::set("main", "map_left_menu_type", 	"en_left,content");*/
} else {
	$APPLICATION->SetTitle("Страница не найдена");
	?>
	Уважаемый посетитель! К сожалению, запрашиваемая Вами страница не доступна. Это могло произойти по следующим причинам:
	<br>
	<ul>
		<li>Страница была удалена</li>
		<li>Страница была переименована</li>
		<li>Вы допустили ошибку в адресе (https://<?=$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']?>)</li>
	</ul>
	<br>
	Пожалуйста, перейдите на <a href="<?=SITE_DIR?>">главную страницу</a> сайта и попробуйте еще раз или воспользуйтесь <strong>картой сайта</strong>:

	<?
	/*\Bitrix\Main\Config\Option::set("main", "map_top_menu_type", 	"top");
	\Bitrix\Main\Config\Option::set("main", "map_left_menu_type", 	"left,content");*/
}
?>
<br /><br />

<?$APPLICATION->IncludeComponent("bitrix:main.map", "", [
	"COMPONENT_TEMPLATE"		=> ".default",
	"CACHE_TYPE"				=> "A",
	"CACHE_TIME"				=> 0,
	"SET_TITLE"					=> "N",
	"LEVEL"						=> 3,
	"COL_NUM"					=> 1,
	"SHOW_DESCRIPTION"			=> "N",
], false);?>
