<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!empty($arResult["ITEMS"][0]["UF_SECTION"])) {
	$pageName = "Принято покупать в esky.ru " . ToLower($arResult["ITEMS"][0]["UF_SECTION"]);

	$APPLICATION->AddChainItem($pageName);
	$APPLICATION->SetPageProperty('title', $pageName);
	$APPLICATION->SetTitle($pageName);
}