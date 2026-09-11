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

// region sale and catalog

// drop unused cache to per-product discount on cli run
$eventManager->addEventHandler('catalog', 'OnGetDiscountResult', static function (&$arResult) {
	static $cnt = 0;
	if (PHP_SAPI == 'cli' && (++$cnt % 200 == 0)) {
		UUtils::setPrivateProperty(\CCatalogDiscount::class, 'arCacheProduct', []);
		
		\CCatalogDiscount::ClearDiscountCache([
			'PRODUCT' => true,
			/*'SECTIONS'        => true,
			'PROPERTIES'        => true,
			'SECTION_CHAINS'    => true*/
		]);
	}
	return true;
});

// region очистка из корзины ненужных свойств (при добавлении товара из админки)
$eventManager->addEventHandler("sale", "OnBasketAdd", static function ($ID, $arFields) {
	Hipot\BitrixUtils\Sale::deleteUnusedBasketProps($ID);
});
$eventManager->addEventHandler(
	'sale',
	'OnSaleOrderSaved',
	static function (Event $event) {
		/** @var \Bitrix\Sale\Order $order */
		$order = $event->getParameter("ENTITY");
		
		$checkFlag = 'deletedOrderUnusedProps_' . $order->getId();
		/** @noinspection GlobalVariableUsageInspection */
		if (isset($GLOBALS[$checkFlag])) {
			return;
		}
		Hipot\BitrixUtils\Sale::deleteUnusedBasketProps();
		/** @noinspection GlobalVariableUsageInspection */
		$GLOBALS[$checkFlag] = true;
	}
);
// endregion

// endregion sale and catalog