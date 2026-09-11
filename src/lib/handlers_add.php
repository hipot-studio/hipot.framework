<?php
defined('B_PROLOG_INCLUDED') || die();
/**
 * Установка кастомных обработчиков и их описание.
 * Желательно описание (определение класса и метода) делать отдельно от данного файла
 *
 * Т.е. в данном файле пишем addEventHandler, а сам обработчик в файле с классом lib/classes/SiteEvents.php, lib/classes/CatalogEvents.php, ...
 */

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;
use Bitrix\Main\Event;
use Bitrix\Main\Application;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Composite\Page as CompositePage;
use Bitrix\Main\Composite\Engine as CompositeEngine;
use Hipot\BitrixUtils\AssetsContainer;
use Hipot\BitrixUtils\HiBlockApps;
use Hipot\Services\BitrixEngine;
use Hipot\Utils\UUtils;
use Bitrix\Main\ORM\Data\DataManager;

$eventManager = EventManager::getInstance();
$request      = Application::getInstance()->getContext()->getRequest();

// optimize turn off all page-process-handlers when it's an ajax request:
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
		UUtils::disableAssetMinifying();
	}
});

// define global constants that may depend on $APPLICATION and $USER
$eventManager->addEventHandler("main", "OnBeforeProlog", static function () use ($request) {
	global $APPLICATION, $USER;

	// need user and other internal engine items re-create
	BitrixEngine::resetInstance();
	
	// create user-d0 to work in agents
	if ($USER === null) {
		$USER = BitrixEngine::getCurrentUserD0();
	}

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
});

require __DIR__ . '/handlers/admin_ui.php';

require __DIR__ . '/handlers/js_and_seo.php';

require __DIR__ . '/handlers/catalog_sale.php';

require __DIR__ . '/handlers/hi_block.php';

// disable basic auth in bitrix admin
UUtils::disableHttpAuth();