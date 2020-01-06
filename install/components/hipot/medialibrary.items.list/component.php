<?
/**
 * Вывод файлов из альбома(мов) медиабиблиотеки
 * @version 2.0
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/* @var $this CBitrixComponent */

use Bitrix\Main\Type\Collection;

if (! is_array($arParams['COLLECTION_IDS'])) {
	$arParams['COLLECTION_IDS'] = [$arParams['COLLECTION_IDS']];
}
$arParams['COLLECTION_IDS'] = array_filter($arParams['COLLECTION_IDS']);

if ($arParams['ONLY_RETURN_ITEMS'] == 'Y') {
	$arParams['CACHE_TIME']	= 0;
}

if ($this->startResultCache(false)) {
	CModule::IncludeModule("fileman");
	CMedialib::Init();

	$Params = [];
	if (count($arParams['COLLECTION_IDS']) > 0) {
		$Params = ['arCollections' => $arParams['COLLECTION_IDS']];
	}

	$arResult = $fileIds = [];

	$rsCol = CMedialibItem::GetList($Params);
	foreach ($rsCol as $v) {
		// future iterator
		$arResult['ITEMS'][] = $v;
		$fileIds[] = $v['SOURCE_ID'];
	}

	Collection::sortByColumn($arResult['ITEMS'], ['NAME' => SORT_NATURAL]);

	$fileIds = array_filter($fileIds);
	if ($arParams['SELECT_FILE_INFO'] == 'Y' && count($fileIds) > 0) {
		$arResult['arFileInfo'] = [];
		$rs = CFile::GetList([], ['@ID' => implode(',', $fileIds)]);
		while ($f = $rs->Fetch()) {
			$arResult['arFileInfo'][ $f['ID'] ] = $f;
		}
	}

	if (count($arResult['ITEMS']) > 0) {
		$this->setResultCacheKeys([]);

		if ($arParams['ONLY_RETURN_ITEMS'] != 'Y') {
			$this->includeComponentTemplate();
		}
	} else {
		$this->abortResultCache();
	}
}

return $arResult;
