<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var $this \CBitrixComponent */

$requiredModules = ['highloadblock', 'iblock'];
foreach ($requiredModules as $requiredModule) {
	if (!CModule::IncludeModule($requiredModule)) {
		ShowError($requiredModule . " not inslaled and required!");
		return 0;
	}
}

$arParams['PAGEN_1']        = (int)$_REQUEST['PAGEN_1'];
$arParams['SHOWALL_1']      = (int)$_REQUEST['SHOWALL_1'];

use Bitrix\Highloadblock as HL;

if ($this->startResultCache(false)) {

	// hlblock info
	$hlblock_id = $arParams['IBLOCK_ID'];

	$hlblock = HL\HighloadBlockTable::getById($hlblock_id)->fetch();

	if (empty($hlblock)) {
		if ($arParams["SET_404"] == "Y") {
			include $_SERVER["DOCUMENT_ROOT"] . "/404_inc.php";
		}
		ShowError('404 HighloadBlock not found');
		return 0;
	}

	// uf info
	$fields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('HLBLOCK_' . $hlblock['ID'], 0, LANGUAGE_ID);

	// sort
	if ($arParams["ORDER"]) {
		$arOrder = $arParams["ORDER"];
	} else {
		$arOrder = ["ID" => "DESC"];
	}

	// limit
	$limit = [
		'nPageSize' => (int)$arParams["PAGESIZE"] > 0 ? (int)$arParams["PAGESIZE"] : 10,
		'iNumPage' => is_set($arParams['PAGEN_1']) ? $arParams['PAGEN_1'] : 1,
		'bShowAll' => $arParams['NAV_SHOW_ALL'] == 'Y',
		'nPageTop' => (int)$arParams["NTOPCOUNT"]
	];


	$arSelect = ["*"];
	if (!empty($arParams["SELECT"])) {
		$arSelect = $arParams["SELECT"];
		$arSelect[] = "ID";
	}

	$arFilter = [];
	if (!empty($arParams["FILTER"])) {
		$arFilter = $arParams["FILTER"];
	}

	$arGroupBy = [];
	if (!empty($arParams["GROUP_BY"])) {
		$arGroupBy = $arParams["GROUP_BY"];
	}

	$entity = HL\HighloadBlockTable::compileEntity($hlblock);
	$entity_class = $entity->getDataClass();

	$result = $entity_class::getList([
		"order" => $arOrder,
		"select" => $arSelect,
		"filter" => $arFilter,
		"group" => $arGroupBy,
		"limit" => $limit["nPageTop"] > 0 ? $limit["nPageTop"] : 0,
	]);


	if ($limit["nPageTop"] <= 0) {
		$result = new CDBResult($result);
		$result->NavStart($limit, false, true);

		$arResult["NAV_STRING"] = $result->GetPageNavStringEx(
			$navComponentObject,
			$arParams["PAGER_TITLE"],
			$arParams["PAGER_TEMPLATE"]
		);
		$arResult["NAV_CACHED_DATA"] = $navComponentObject->GetTemplateCachedData();
		$arResult["NAV_RESULT"] = $result;
	}

	// build results
	$arResult["ITEMS"] = [];

	while ($row = $result->Fetch()) {
		foreach ($row as $k => $v) {
			if ($k == "ID") {
				continue;
			}
			$arUserField = $fields[$k];

			$html = call_user_func([$arUserField["USER_TYPE"]["CLASS_NAME"], "getadminlistviewhtml"], $arUserField, [
				"NAME" => "FIELDS[" . $row['ID'] . "][" . $arUserField["FIELD_NAME"] . "]",
				"VALUE" => htmlspecialcharsbx($v)
			]);
			if ($html == '') {
				$html = '&nbsp;';
			}

			$row[$k] = $html;
			$row["~" . $k] = $v;
		}
		$arResult["ITEMS"][] = $row;
	}

	if (count($arResult["ITEMS"]) > 0) {

		// добавили сохранение ключей по параметру
		$arSetCacheKeys = [];
		if (is_array($arParams['SET_CACHE_KEYS'])) {
			$arSetCacheKeys = $arParams['SET_CACHE_KEYS'];
		}

		$this->setResultCacheKeys($arSetCacheKeys);
	} else {
		if ($arParams["SET_404"] == "Y") {
			include $_SERVER["DOCUMENT_ROOT"] . "/404_inc.php";
		}

		$this->abortResultCache();
	}

	if (count($arResult["ITEMS"]) > 0 || $arParams["ALWAYS_INCLUDE_TEMPLATE"] == "Y") {
		$this->includeComponentTemplate();
	}
}

return $arResult;