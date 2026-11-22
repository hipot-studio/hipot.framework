<?php

defined('B_PROLOG_INCLUDED') || die();

/**
 * @var $eventManager EventManager
 * @var $request \Bitrix\Main\HttpRequest
 */

use Hipot\BitrixUtils\Iblock;

// ORM cache is invalidated by Bitrix; discard request-local maps as well.
foreach (['OnAfterIBlockPropertyAdd', 'OnAfterIBlockPropertyUpdate', 'OnAfterIBlockPropertyDelete'] as $eventName) {
	$eventManager->addEventHandlerCompatible('iblock', $eventName, [Iblock::class, 'clearPropertiesByCodeCache']);
}
