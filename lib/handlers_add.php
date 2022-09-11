<?php
/**
 * Установка обработчиков и их описание.
 * Желательно описание (определение класса и метода) делать отдельно от данного файла
 *
 * Т.е. в данном файле пишем AddEventHandler
 * а сам обработчик в файле с классом /include/lib/classes/siteevents.php
 */

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Composite\Page as CompositePage;
use Bitrix\Main\Application;

$eventManager = EventManager::getInstance();
$request      = Application::getInstance()->getContext()->getRequest();

// определяем глобальные константы, которые могут зависеть от $APPLICATION и $USER
$eventManager->addEventHandler("main", "OnBeforeProlog", static function () {
	global $APPLICATION, $USER;

	foreach (
		[
			__DIR__ . '/constants.php',
			Loader::getDocumentRoot() . '/local/php_interface/include/constants.php',
			Loader::getDocumentRoot() . '/local/php_interface/include/lib/constants.php',
			Loader::getDocumentRoot() . '/bitrix/php_interface/include/constants.php',
			Loader::getDocumentRoot() . '/bitrix/php_interface/include/lib/constants.php'
		] as $constFile) {
			if (is_file($constFile)) {
				include $constFile;
				break;
			}
		}
});

// проставляем id инфоблоков в административном меню
$eventManager->addEventHandler("main", "OnBuildGlobalMenu", static function (&$aGlobalMenu, &$aModuleMenu) {
	if (!IS_BETA_TESTER || !defined("ADMIN_SECTION")) {
		return;
	}
	foreach ($aModuleMenu as $k => $arMenu) {
		if ($arMenu['icon'] != 'iblock_menu_icon_types') {
			continue;
		}
		foreach ($arMenu['items'] as $i => $item) {
			$arEx = explode('/', $item['items_id']);
			$aModuleMenu[$k]['items'][$i]['text'] .= ' /' . $arEx[2] . '/';
		}
	}
});

// RemoveYandexDirectTab in iblock elements
$eventManager->addEventHandler('main', 'OnAdminTabControlBegin', static function (&$TabControl) {
	if ($GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/iblock_element_edit.php') {
		foreach ($TabControl->tabs as $Key => $arTab) {
			if ($arTab['DIV'] == 'seo_adv_seo_adv') {
				unset($TabControl->tabs[$Key]);
			}
		}
	}
});

// draw user picture after login
$eventManager->addEventHandler(	"main", "OnAdminListDisplay",
	/** @param CAdminUiList $this_al */
	static function (&$this_al) {
		if ($this_al->table_id == "tbl_user") {
			foreach ($this_al->aRows as &$row) {
				$userId = (int)$row->arRes['ID'];
				$picPath = CFile::GetPath( (CUser::GetByID($userId)->Fetch())["PERSONAL_PHOTO"] );
				if (trim($picPath) != '') {
					$row->aFields["LOGIN"]["view"]["value"] .= ' <br><a target="_blank" href="' . $picPath . '">'
						. '<img style="max-width:200px;" src="' . $picPath  . '"></a>';
				}
			}
		}
	}
);

// очищаем настройки формы по-умолчанию для всех админов
// @see https://www.hipot-studio.com/Codex/form_iblock_element_settings/
$eventManager->addEventHandler('main', 'OnEndBufferContent', static function (&$content) use ($request) {
	$p = $request->getPost('p');

	if (!isset($p) || !is_array($p) || count($p) <= 0) {
		return;
	}

	global $APPLICATION, $DB, $CACHE_MANAGER;

	$pCfg 		= array_shift($p);

	if ($APPLICATION->GetCurPage() != '/bitrix/admin/user_options.php'
		|| $pCfg['c'] != 'form' || $pCfg['d'] != 'Y'
		|| !preg_match('#^form_((section)|(element))_\d+$#', $pCfg['n'])
	) {
		return;
	}

	/** @noinspection SqlResolve */
	$DB->Query("DELETE FROM b_user_option WHERE CATEGORY = 'form' AND NAME = '" . $pCfg['n'] . "' AND COMMON = 'N'");
	$CACHE_MANAGER->CleanDir("user_option");
});

// отрисовка 404 страницы с прерыванием текущего буфера и замены его на содержимое 404
$eventManager->addEventHandler('main', 'OnEndBufferContent', static function (&$content) use ($request) {
	global $APPLICATION;

	// process 404 in content part
	if ((defined('ERROR_404') && constant('ERROR_404') == 'Y' && $APPLICATION->GetCurPage() != '/404.php')

		|| ($GLOBALS['httpCode'] == 404 && $APPLICATION->GetCurPage() != '/404.php')

	) {
		$contCacheFile  = Loader::getDocumentRoot() . '/upload/404_cache.html';

		if (is_file($contCacheFile) && ((time() - filemtime($contCacheFile)) > 3600)) {
			unlink($contCacheFile);
		}

		$content = file_get_contents($contCacheFile);
		if (trim($content) == '') {
			$el = new HttpClient();
			$content = $el->get((CMain::IsHTTPS() ? 'https://' : 'http://') . $request->getServer()->getServerName() . '/404.php');
			file_put_contents($contCacheFile, $content);
		}

		CompositePage::getInstance()->markNonCacheable();

		header("HTTP/1.0 404 Not Found\r\n");
	}
});

// drop unused cache to per-product discount on cli run
$eventManager->addEventHandler('catalog', 'OnGetDiscountResult', static function (&$arResult) {

	if (PHP_SAPI == 'cli') {
		\CCatalogDiscount::ClearDiscountCache([
			'PRODUCT'       => true,
			/*'SECTIONS'      => true,
			'PROPERTIES'    => true*/
		]);
	}
	return true;

});

// immediately drop custom setting hl-block cache
$eventManager->addEventHandler('', 'CustomSettingsOnAfterUpdate',   ['Hipot\\BitrixUtils\\HiBlockApps', 'clearCustomSettingsCacheHandler']);
$eventManager->addEventHandler('', 'CustomSettingsOnAfterAdd',      ['Hipot\\BitrixUtils\\HiBlockApps', 'clearCustomSettingsCacheHandler']);
$eventManager->addEventHandler('', 'CustomSettingsOnAfterDelete',   ['Hipot\\BitrixUtils\\HiBlockApps', 'clearCustomSettingsCacheHandler']);