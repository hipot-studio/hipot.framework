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
use Bitrix\Main\Event;
use Bitrix\Main\Application;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Composite\Page as CompositePage;
use Bitrix\Main\Composite\Engine as CompositeEngine;
use Bitrix\Main\Page\Asset;
use Hipot\BitrixUtils\AssetsContainer;
use Hipot\BitrixUtils\HiBlockApps;
use Hipot\Services\BitrixEngine;
use Hipot\Utils\UUtils;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Data\DataManager;

$eventManager = EventManager::getInstance();
$request      = Application::getInstance()->getContext()->getRequest();

// optimize turn off all page-process-handlers when it's ajax request:
$eventManager->addEventHandler('main', 'OnPageStart', static function () use ($request) {
	$be = BitrixEngine::getInstance();

	/**
	 * На сайт пришел аякс-запрос
	 */
	define('IS_AJAX', UUtils::isAjaxRequest($be));

	/**
	 * Текущий процесс запущен из командной строки
	 */
	define('IS_CLI', PHP_SAPI === 'cli');

	if (IS_AJAX || IS_CLI || (defined('DISABLE_PAGE_EVENTS') && DISABLE_PAGE_EVENTS === true)) {
		UUtils::disableAllPageProcessEvents($be);
	}

	if (! empty($request->get('sources'))) {
		Asset::getInstance()->disableOptimizeCss();
		Asset::getInstance()->disableOptimizeJs();
		Asset::getInstance()->setJsToBody(false);

		// no .min.js and .min.css
		$canLoad = Option::get("main","use_minified_assets", "Y") === "Y";
		if ($canLoad) {
			$optionClass = new \ReflectionClass(Option::class);
			$options = $optionClass->getStaticPropertyValue('options');
			$options['main']['-']['use_minified_assets'] = 'N';
			$optionClass->setStaticPropertyValue('options', $options);
			$canLoad = Asset::getInstance()::canUseMinifiedAssets();
			$options['main']['-']['use_minified_assets'] = 'Y';
			$optionClass->setStaticPropertyValue('options', $options);
			unset($options, $optionClass, $canLoad);
		}
	}
});

// определяем глобальные константы, которые могут зависеть от $APPLICATION и $USER
$eventManager->addEventHandler("main", "OnBeforeProlog", static function () use ($request) {
	global $APPLICATION, $USER;

	// need user and other internal engine items re-create
	BitrixEngine::resetInstance();

	foreach (
		[
			__DIR__ . '/constants.php',
			__DIR__ . '/lib/constants.php',     // handler in init.php
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

	// create user-d0 to work in agents
	if ($USER === null) {
		$USER = BitrixEngine::getCurrentUserD0();
	}
});

// проставляем id инфоблоков в административном меню
$eventManager->addEventHandler("main", "OnBuildGlobalMenu", static function (&$aGlobalMenu, &$aModuleMenu) use ($request) {
	if (!defined('IS_BETA_TESTER') || !IS_BETA_TESTER || !$request->isAdminSection()) {
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
	/** @var $GLOBALS array{'DB':\CDatabase, 'APPLICATION':\CMain, 'USER':\CUser, 'USER_FIELD_MANAGER':\CUserTypeManager, 'CACHE_MANAGER':\CCacheManager, 'stackCacheManager':\CStackCacheManager} */
	if ($GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/iblock_element_edit.php') {
		foreach ($TabControl->tabs as $Key => $arTab) {
			if ($arTab['DIV'] == 'seo_adv_seo_adv') {
				unset($TabControl->tabs[$Key]);
			}
		}
	}
});

// draw user picture after login and top pagenav
$eventManager->addEventHandler(	"main", "OnAdminListDisplay",
	/** @param CAdminUiList $this_al */
	static function (&$this_al) {
		if ($this_al->table_id == "tbl_user" || str_contains($this_al->table_id, 'iblock')) {
			echo $this_al->sNavText;
			?>
			<style>
				.adm-workarea > .main-ui-pagination {padding:10px 0;}
			</style>
			<?
		}
		if ($this_al->table_id == "tbl_user") {
			foreach ($this_al->aRows as &$row) {
				$userId = (int)$row->arRes['ID'];
				$picPath = CFile::GetPath( (CUser::GetByID($userId)->Fetch())["PERSONAL_PHOTO"] );
				if (trim($picPath) != '') {
					$row->aFields["LOGIN"]["view"]["value"] .= ' <br><a target="_blank" href="' . $picPath . '">'
						. '<img style="max-width:200px;" alt="" loading="lazy" src="' . $picPath  . '"></a>';
				}
			}
		}
	}
);

// отрисовка 404 страницы с прерыванием текущего буфера и замены его на содержимое 404
$eventManager->addEventHandler('main', 'OnEpilog', static function () use ($request, $eventManager) {
	if ($request->isAdminSection() || $request->isAjaxRequest()) {
		return;
	}
	global $APPLICATION;
	static $isRun = false;

	if (IS_BETA_TESTER) {
		$isRun = true;
	}

	// process 404 in content part
	if ((!$isRun && defined('ERROR_404') && ERROR_404 === 'Y' && $APPLICATION->GetCurPage() != '/404.php')) {
		$isRun = true;
		// region re-get one time 404 page
		$eventManager->addEventHandler('main', 'OnEndBufferContent', static function (&$content) use ($request) {
			$contCacheFile = Loader::getDocumentRoot() . sprintf('/upload/404_%s_cache.html', Application::getInstance()?->getContext()?->getSite());
			if (is_file($contCacheFile) && ((time() - filemtime($contCacheFile)) > 3600)) {
				unlink($contCacheFile);
			}
			$content = is_file($contCacheFile) ? file_get_contents($contCacheFile) : '';
			if (trim($content) === '') {
				$el      = new HttpClient();
				$content = $el->get(($request->isHttps() ? 'https://' : 'http://') . $request->getServer()->getHttpHost() . '/404.php?last_page=' . urlencode($request->getRequestUri()));

				file_put_contents($contCacheFile, $content, LOCK_EX);
			}
			\CHTTP::setStatus('404 Not Found');

			CompositePage::getInstance()?->markNonCacheable();
			CompositeEngine::setEnable(false);
		});
		// endregion
	}
});

// lazy loaded css, TOFUTURE: js and js-appConfig array
$eventManager->addEventHandler('main', 'OnEpilog', [AssetsContainer::class, 'onEpilogSendAssets']);

// очищаем настройки формы по-умолчанию для всех админов
// @see https://www.hipot-studio.com/Codex/form_iblock_element_settings/
$eventManager->addEventHandler('main', 'OnBeforeEndBufferContent', static function () use ($request) {
	$p = $request->getPost('p');

	if (!isset($p) || !is_array($p) || count($p) <= 0) {
		return;
	}
	global $APPLICATION;

	$pCfg = array_shift($p);
	if ($APPLICATION->GetCurPage() != '/bitrix/admin/user_options.php'
		|| $pCfg['c'] != 'form' || $pCfg['d'] != 'Y'
		|| !preg_match('#^form_((section)|(element))_\d+$#', $pCfg['n'])
	) {
		return;
	}

	/** @noinspection SqlResolve */
	Application::getConnection()->query("DELETE FROM b_user_option WHERE CATEGORY = 'form' AND NAME = '" . $pCfg['n'] . "' AND COMMON = 'N'");
	Application::getInstance()->getManagedCache()->cleanDir("user_option");
});

// delete system scripts (Use only when not needed composite dynamic blocks)
$eventManager->addEventHandler('main', 'OnEndBufferContent', static function (&$cont) use ($request) {
	if ($request === null || $request->isAdminSection()) {
		return;
	}
	global $APPLICATION, $USER;

	if (is_object($USER) && !$USER->IsAuthorized() && !$request->isPost() && $APPLICATION->GetProperty('JS_core_frame_cache_NEED') != 'Y') {
		$toRemove = [
			'#<script[^>]+src="/bitrix/js/ui/dexie/[^>]+></script>#',
			'#<script[^>]+src="/bitrix/js/main/core/core_frame_cache[^>]+></script>#',
			'#<link[^>]+href="/bitrix/js/ui/design-tokens/dist/ui.design-tokens.min.css\?\d+"[^>]+>#',
			'#<link[^>]+href="/bitrix/panel/main/popup.min.css\?\d+"[^>]+>#',
		];
		$cont = preg_replace($toRemove, "", $cont);
	}

	$toInline = [
		'#<script[^>]+src="(?<src>/bitrix/js/main/core/core.min.js)\?\d+"[^>]*></script>#',
		'#<script[^>]+src="(?<src>/bitrix/js/main/core/core_ls.min.js)\?\d+"[^>]*></script>#',
		'#<script[^>]+src="(?<src>/bitrix/cache/js/.*?\.js)\?\d+"[^>]*></script>#'
	];
	$cont = preg_replace_callback($toInline, static function ($matches) {
		$content = file_get_contents(Loader::getDocumentRoot() . $matches['src']);
		if (preg_match('#</head>#', $content)) {
			return $matches[0];
		}
		$scriptHeader = '/////////////////////////////////////' . PHP_EOL
				. '// Script: ' . $matches['src'] . PHP_EOL
				. '/////////////////////////////////////' . PHP_EOL;
		return '<script>' . PHP_EOL
				. (IS_BETA_TESTER ? $scriptHeader : '')
				. $content . PHP_EOL
				. '</script>';
	}, $cont);

	// validator.w3.org: The type attribute is unnecessary for JavaScript resources.
	$cont = preg_replace('#<script([^>]*)type=[\'"]text/javascript[\'"]([^>]*)>#', '<script\\1\\2>', $cont);
});

// drop unused cache to per-product discount on cli run
$eventManager->addEventHandler('catalog', 'OnGetDiscountResult', static function (&$arResult) {
	static $cnt = 0;
	if (PHP_SAPI == 'cli' && (++$cnt % 200 == 0)) {
		UUtils::setPrivateProperty(\CAllCatalogDiscount::class, 'arCacheProduct', []);

		\CCatalogDiscount::ClearDiscountCache([
			'PRODUCT' => true,
			/*'SECTIONS'        => true,
			'PROPERTIES'        => true,
			'SECTION_CHAINS'    => true*/
		]);
	}
	return true;
});

// immediately drop the custom setting hl-block cache
$eventManager->addEventHandler('', HiBlockApps::CS_HIBLOCK_NAME . DataManager::EVENT_ON_AFTER_UPDATE,   [HiBlockApps::class, 'clearCustomSettingsCacheHandler']);
$eventManager->addEventHandler('', HiBlockApps::CS_HIBLOCK_NAME . DataManager::EVENT_ON_AFTER_ADD,      [HiBlockApps::class, 'clearCustomSettingsCacheHandler']);
$eventManager->addEventHandler('', HiBlockApps::CS_HIBLOCK_NAME . DataManager::EVENT_ON_AFTER_DELETE,   [HiBlockApps::class, 'clearCustomSettingsCacheHandler']);

// region очистка из корзины ненужных свойств (при добавлении товара из админки)
$eventManager->addEventHandler("sale", "OnBasketAdd", static function ($ID, $arFields) {
	Hipot\BitrixUtils\Sale::deleteUnUsedBasketProps($ID);
});
$eventManager->addEventHandler(
	'sale',
	'OnSaleOrderSaved',
	static function (Event $event) {
		/** @var \Bitrix\Sale\Order $order */
		$order = $event->getParameter("ENTITY");

		if (isset($GLOBALS['deletedOrderUnusedProps_' . $order->getId()])) {
			return;
		}

		Hipot\BitrixUtils\Sale::deleteUnUsedBasketProps();
		$GLOBALS['deletedOrderUnusedProps_' . $order->getId()] = true;
	}
);
// endregion

// disable basic auth in bitrix admin
UUtils::disableHttpAuth();