<?php
defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\EventManager;
use Hipot\BitrixUtils\Iblock\ElementDescriptionType;
use Hipot\UserField\HtmlType;
use Hipot\UserField\UserType;

/**
 * @var $eventManager EventManager
 * @var $request \Bitrix\Main\HttpRequest
 */

// События старого API ожидают массив описания типа, а не объект EventResult.
$eventManager->addEventHandler('main', 'OnUserTypeBuildList', [UserType::class, 'getUserTypeDescription']);
$eventManager->addEventHandler('main', 'OnUserTypeBuildList', [HtmlType::class, 'getUserTypeDescription']);
$eventManager->addEventHandler('iblock', 'OnIBlockPropertyBuildList', [ElementDescriptionType::class, 'getUserTypeDescription']);
