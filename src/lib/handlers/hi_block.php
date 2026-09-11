<?php
defined('B_PROLOG_INCLUDED') || die();

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

/**
 * @var $eventManager EventManager
 * @var $request \Bitrix\Main\HttpRequest
 */

// immediately drop the custom setting hl-block cache
foreach ([DataManager::EVENT_ON_AFTER_UPDATE, DataManager::EVENT_ON_AFTER_ADD, DataManager::EVENT_ON_AFTER_DELETE] as $event) {
	$eventManager->addEventHandler('', HiBlockApps::CS_HIBLOCK_NAME . $event,   [HiBlockApps::class, 'clearCustomSettingsCacheHandler']);
}