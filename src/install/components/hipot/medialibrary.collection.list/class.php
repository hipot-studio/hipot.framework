<?php

declare(strict_types=1);

namespace Hipot\Components;

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader;

/** Вывод коллекций медиабиблиотеки. */
class MedialibraryCollectionList extends \CBitrixComponent
{
	public function onPrepareComponentParams($arParams): array
	{
		$arParams['SELECT_WITH_ITEMS'] = ($arParams['SELECT_WITH_ITEMS'] ?? 'N') === 'Y' ? 'Y' : 'N';
		$arParams['ORDER'] = is_array($arParams['ORDER'] ?? null) ? $arParams['ORDER'] : [];
		$arParams['FILTER'] = is_array($arParams['FILTER'] ?? null) ? $arParams['FILTER'] : [];

		if ($arParams['SELECT_WITH_ITEMS'] === 'Y') {
			$componentName = trim((string)($arParams['ITEMS_LIST_COMPONENT_NAME'] ?? ''));
			$arParams['ITEMS_LIST_COMPONENT_NAME'] = $componentName !== ''
				? $componentName
				: 'hipot:medialibrary.items.list';
		}

		return $arParams;
	}

	public function executeComponent(): array|false
	{
		global $APPLICATION;

		if (!$this->startResultCache(false)) {
			return $this->arResult;
		}
		
		if (!Loader::includeModule('fileman')) {
			return false;
		}

		$itemsByCollection = [];
		if ($this->arParams['SELECT_WITH_ITEMS'] === 'Y') {
			$allItems = $APPLICATION->IncludeComponent(
				$this->arParams['ITEMS_LIST_COMPONENT_NAME'],
				'',
				['ONLY_RETURN_ITEMS' => 'Y', 'CACHE_TIME' => 0],
				$this,
				['HIDE_ICONS' => 'Y'],
			);
			foreach (($allItems['ITEMS'] ?? []) as $item) {
				$itemsByCollection[$item['COLLECTION_ID']][] = $item;
			}
		}

		\CMedialib::Init();
		$query = [
			'arOrder' => $this->arParams['ORDER'] ?: ['ID' => 'ASC'],
			'arFilter' => ['ACTIVE' => 'Y'],
		];
		if ($this->arParams['FILTER']) {
			$query['arFilter'] = array_merge(
				$query['arFilter'],
				$this->arParams['~FILTER'] ?? $this->arParams['FILTER'],
			);
		}

		$this->arResult['COLLECTIONS'] = [];
		foreach (\CMedialibCollection::GetList($query) as $collection) {
			if ($this->arParams['SELECT_WITH_ITEMS'] === 'Y') {
				$collection['ITEMS'] = $itemsByCollection[$collection['ID']] ?? null;
			}
			$this->arResult['COLLECTIONS'][] = $collection;
		}

		if (!$this->arResult['COLLECTIONS']) {
			$this->abortResultCache();
			return $this->arResult;
		}

		$this->setResultCacheKeys(['COLLECTIONS']);
		$this->includeComponentTemplate();

		return $this->arResult;
	}
}
