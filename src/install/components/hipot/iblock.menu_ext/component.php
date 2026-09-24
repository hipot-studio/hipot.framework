<?php
/**
 * hipot:iblock.menu_ext: динамическое меню из элементов или разделов инфоблока
 * @see docs/components/hipot_iblock_menu_ext.md
 * @author hipot-studio
 * @version 2.0
 */
defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use function Opis\Closure\unserialize as UnserializeClosure; // from 4.4 version of a library

/**
 * @var array $arParams
 * @var array $arResult
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CDatabase $DB
 * @var CBitrixComponent $this
 */

$reqParams = ['TYPE', 'CACHE_TAG', 'CACHE_TIME'];
foreach ($reqParams as $param) {
	if (trim($arParams[$param]) == '') {
		ShowError('Need PARAM ' . $param . ', see .description.php!');
		return false;
	}
}
$needArrayParams = ['ORDER', 'FILTER', 'SELECT'];
foreach ($needArrayParams as $param) {
	if (!is_array($arParams[$param])) {
		$arParams[$param] = [];
	}
}
unset($reqParams, $needArrayParams);

// сюда соберем все пункты меню
$arResult 		= [];

$cacheTime		= Option::get('main', 'component_cache_on', 'Y') === 'N'
	? 0
	: (int)$arParams['CACHE_TIME'];
$cacheId		= __FILE__ . '|' . serialize($arParams);
$cachePath		= 'php/' . mb_strtolower($arParams['CACHE_TAG']) . '/';

$menuCache = Cache::createInstance();
$menuCache->noOutput();

if ($menuCache->initCache($cacheTime, $cacheId, $cachePath)) {
	$cacheVars = $menuCache->getVars();
	$arResult = $cacheVars['arResult'] ?? [];
} elseif ($menuCache->startDataCache()) {
	
	Loader::requireModule('iblock');
	
	if (count((array)$arParams["ORDER"]) > 0) {
		$arOrder = $arParams["ORDER"];
	} else {
		$arOrder = ["SORT" => "ASC"];
	}
	
	$arFilter = ["IBLOCK_ID" => $arParams["IBLOCK_ID"], "ACTIVE" => "Y"];
	if (count((array)$arParams["FILTER"]) > 0) {
		$arFilter = array_merge($arFilter, $arParams["~FILTER"]);
	}
	
	if ($arParams["TYPE"] == 'elements') {
		
		$arSelect = ["ID", "IBLOCK_ID", "DETAIL_PAGE_URL", "NAME"];
		if (is_array($arParams["SELECT"]) && count($arParams["SELECT"]) > 0) {
			$arSelect = array_merge($arSelect, $arParams["SELECT"]);
		}
		$rsItems = CIBlockElement::GetList($arOrder, $arFilter, false, false, $arSelect);
		while ($arItem = $rsItems->GetNext()) {
			$link_params = (is_array($arParams["SELECT"]) && count($arParams["SELECT"]) > 0) ? $arItem : [];
			$addonsUri = !empty($arParams['ADDON_URL_TO_SELECT_ITEM']) ? [CIBlock::ReplaceDetailUrl($arParams['ADDON_URL_TO_SELECT_ITEM'], $arItem, false, 'E')] : [];
			
			if (!empty($arParams['~MODIFY_ITEM'])) {
				$closure = UnserializeClosure($arParams['~MODIFY_ITEM']);
				if (is_callable($closure)) {
					$closure($arItem);
				}
			}
			
			$arResult[] = [
				$arItem['NAME'],
				$arItem['DETAIL_PAGE_URL'],
				$addonsUri,
				$link_params
			];
		}
	} else if ($arParams["TYPE"] == 'sections') {
		
		$arSelect = ['ID', 'IBLOCK_ID', 'CODE', 'SECTION_PAGE_URL', 'NAME', 'DEPTH_LEVEL'];
		if (is_array($arParams["SELECT"]) && count($arParams['SELECT']) > 0) {
			$arSelect = array_merge($arSelect, $arParams['SELECT']);
		}
		
		$arNavStartParams = false;
		
		$rsSect = CIBlockSection::GetList($arOrder, $arFilter, false, $arSelect, $arNavStartParams);
		while ($arSect = $rsSect->GetNext()) {
			$link_params = (is_array($arParams["SELECT"]) && count($arParams["SELECT"]) > 0) ? $arSect : [];
			$addonsUri = !empty($arParams['ADDON_URL_TO_SELECT_ITEM']) ? [CIBlock::ReplaceSectionUrl($arParams['ADDON_URL_TO_SELECT_ITEM'], $arSect, false, 'S')] : [];
			
			if (!empty($arParams['~MODIFY_ITEM'])) {
				$closure = UnserializeClosure($arParams['~MODIFY_ITEM']);
				if (is_callable($closure)) {
					$closure($arSect);
				}
			}
			
			$arResult[] = [
				$arSect['NAME'],
				$arSect['SECTION_PAGE_URL'],
				$addonsUri,
				$link_params
			];
		}
	}
	
	if (count($arResult) == 0) {
		$menuCache->abortDataCache();
	} else {
		$menuCache->endDataCache(["arResult" => $arResult]);
	}
}


// возвращаем выборку для использования в файлах menu_ext
return $arResult;
