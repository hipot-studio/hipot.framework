<?php /** @noinspection GlobalVariableUsageInspection */
defined('B_PROLOG_INCLUDED') || die();
/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 * @global $BX_MENU_CUSTOM \CMenuCustom
 * @global $stackCacheManager \CStackCacheManager
 */
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Localization\Loc;
use Hipot\BitrixUtils\AssetsContainer;

Loc::loadMessages(__FILE__);
Loc::loadMessages(__DIR__ . '/footer.php');

// region need variables
$curDir = $APPLICATION->GetCurDir();
$curPage = $APPLICATION->GetCurPage(false);
$curPageIndex = $APPLICATION->GetCurPage(true);
$isMainPage = ($curPageIndex === '/index.php');
$request = Application::getInstance()?->getContext()?->getRequest();
// endregion

// main lib js
/*
CJSCore::RegisterExt("jquery37", [
	"js" => SITE_TEMPLATE_PATH . '/js/jquery/3.7.1/jquery.min.js',      // because ["jquery3"] 3.6 in bitrix now
]);
*/
CJSCore::Init(["jquery3"]);
Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . '/js/script.js');

// main css assets
AssetsContainer::addCss(SITE_TEMPLATE_PATH . '/stylesheets/main.css', AssetsContainer::CSS_INLINE);

?><!DOCTYPE html>
<html lang="<?=LANGUAGE_ID?>">
<head>
	<meta name="format-detection" content="telephone=no">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?
	$APPLICATION->ShowMeta("og:title", false, false);
	$APPLICATION->ShowMeta("og:description", false, false);
	$APPLICATION->ShowMeta("og:type", false, false);
	$APPLICATION->ShowMeta("og:url", false, false);
	$APPLICATION->ShowMeta("og:image", false, false);
	$APPLICATION->ShowMeta("og:image:alt", false, false);
	?>
	<?$APPLICATION->ShowHead(false)?>
	<title><?$APPLICATION->ShowTitle()?></title>
</head>
<body>
<?if ($request->get('no_panel') !== 'Y') {?>
	<?$APPLICATION->ShowPanel();?>
<?}?>

<!-- PAGE_START -->