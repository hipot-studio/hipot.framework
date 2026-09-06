<?php
defined('B_PROLOG_INCLUDED') || die();

/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 * @global $BX_MENU_CUSTOM \CMenuCustom
 * @global $stackCacheManager \CStackCacheManager
 */

if (!empty($arResult["ITEMS"][0]["UF_SECTION"])) {
	$pageName = ToLower($arResult["ITEMS"][0]["UF_SECTION"]);

	$APPLICATION->AddChainItem($pageName);
	$APPLICATION->SetPageProperty('title', $pageName);
	$APPLICATION->SetTitle($pageName);
}