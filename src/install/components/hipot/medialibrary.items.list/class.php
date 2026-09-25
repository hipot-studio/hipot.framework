<?php

declare(strict_types=1);

namespace Hipot\Components;

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\FileTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Collection;

/** Вывод элементов медиабиблиотеки. */
class MedialibraryItemsList extends \CBitrixComponent
{
	public function onPrepareComponentParams($arParams): array
	{
		$collectionIds = $arParams['COLLECTION_IDS'] ?? [];
		if (!is_array($collectionIds)) {
			$collectionIds = [$collectionIds];
		}
		$arParams['COLLECTION_IDS'] = array_values(array_filter($collectionIds));
		$arParams['ONLY_RETURN_ITEMS'] = ($arParams['ONLY_RETURN_ITEMS'] ?? 'N') === 'Y' ? 'Y' : 'N';
		$arParams['SELECT_FILE_INFO'] = ($arParams['SELECT_FILE_INFO'] ?? 'N') === 'Y' ? 'Y' : 'N';
		if ($arParams['ONLY_RETURN_ITEMS'] === 'Y') {
			$arParams['CACHE_TIME'] = 0;
		}

		return $arParams;
	}

	public function executeComponent(): array|false
	{
		if (!$this->startResultCache(false)) {
			return $this->arResult;
		}
		
		if (!Loader::includeModule('fileman')) {
			return false;
		}
		\CMedialib::Init();
		
		$query = $this->arParams['COLLECTION_IDS']
			? ['arCollections' => $this->arParams['COLLECTION_IDS']]
			: [];
		$this->arResult['ITEMS'] = [];
		$fileIds = [];
		foreach (\CMedialibItem::GetList($query) as $item) {
			$this->arResult['ITEMS'][] = $item;
			$fileIds[] = (int)$item['SOURCE_ID'];
		}

		if ($this->arResult['ITEMS']) {
			Collection::sortByColumn($this->arResult['ITEMS'], ['NAME' => SORT_NATURAL]);
		}

		$fileIds = array_values(array_unique(array_filter($fileIds)));
		if ($this->arParams['SELECT_FILE_INFO'] === 'Y' && $fileIds) {
			$this->arResult['arFileInfo'] = [];
			$files = FileTable::getList([
				'filter' => ['@ID' => $fileIds],
				'order' => ['ID' => 'ASC'],
			]);
			while ($file = $files->fetch()) {
				$this->arResult['arFileInfo'][$file['ID']] = $file;
			}
		}

		if (!$this->arResult['ITEMS']) {
			$this->abortResultCache();
			return $this->arResult;
		}

		$this->setResultCacheKeys(['ITEMS', 'arFileInfo']);
		if ($this->arParams['ONLY_RETURN_ITEMS'] !== 'Y') {
			$this->includeComponentTemplate();
		}

		return $this->arResult;
	}
}
