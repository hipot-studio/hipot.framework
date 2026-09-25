<?php

declare(strict_types=1);

namespace Hipot\Components;

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use function Opis\Closure\unserialize as unserializeClosure;

/** Динамическое меню из элементов или разделов инфоблока. */
class IblockMenuExt extends \CBitrixComponent
{
	private const REQUIRED_PARAMS = ['TYPE', 'CACHE_TAG', 'CACHE_TIME'];
	private const ARRAY_PARAMS = ['ORDER', 'FILTER', 'SELECT'];

	public function onPrepareComponentParams($arParams): array
	{
		foreach (self::ARRAY_PARAMS as $param) {
			if (!is_array($arParams[$param] ?? null)) {
				$arParams[$param] = [];
			}
		}

		return $arParams;
	}

	public function executeComponent(): array|false
	{
		foreach (self::REQUIRED_PARAMS as $param) {
			if (trim((string)($this->arParams[$param] ?? '')) === '') {
				\ShowError('Need PARAM ' . $param . ', see .description.php!');
				return false;
			}
		}

		$cacheTime = Option::get('main', 'component_cache_on', 'Y') === 'N'
			? 0
			: (int)$this->arParams['CACHE_TIME'];
		$cacheId = static::class . '|' . serialize($this->arParams);
		$cachePath = 'php/' . mb_strtolower((string)$this->arParams['CACHE_TAG']) . '/';
		$cache = Cache::createInstance();
		$cache->noOutput();

		if ($cache->initCache($cacheTime, $cacheId, $cachePath)) {
			$cacheVars = $cache->getVars();
			return $cacheVars['arResult'] ?? [];
		}
		if (!$cache->startDataCache()) {
			return [];
		}
		if (!Loader::includeModule('iblock')) {
			$cache->abortDataCache();
			return false;
		}

		$order = $this->arParams['ORDER'] ?: ['SORT' => 'ASC'];
		$filter = [
			'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
			'ACTIVE' => 'Y',
		];
		if ($this->arParams['FILTER']) {
			$filter = array_merge(
				$filter,
				$this->arParams['~FILTER'] ?? $this->arParams['FILTER'],
			);
		}

		$result = match ($this->arParams['TYPE']) {
			'elements' => $this->getElementsMenu($order, $filter),
			'sections' => $this->getSectionsMenu($order, $filter),
			default => [],
		};

		if (!$result) {
			$cache->abortDataCache();
			return [];
		}

		$cache->endDataCache(['arResult' => $result]);
		return $result;
	}

	private function getElementsMenu(array $order, array $filter): array
	{
		$select = ['ID', 'IBLOCK_ID', 'DETAIL_PAGE_URL', 'NAME'];
		if ($this->arParams['SELECT']) {
			$select = array_values(array_unique(array_merge($select, $this->arParams['SELECT'])));
		}

		$result = [];
		$items = \CIBlockElement::GetList($order, $filter, false, false, $select);
		while ($item = $items->GetNext()) {
			$linkParams = $this->arParams['SELECT'] ? $item : [];
			$addonUrls = !empty($this->arParams['ADDON_URL_TO_SELECT_ITEM'])
				? [\CIBlock::ReplaceDetailUrl($this->arParams['ADDON_URL_TO_SELECT_ITEM'], $item, false, 'E')]
				: [];
			$this->modifyItem($item);
			$result[] = [$item['NAME'], $item['DETAIL_PAGE_URL'], $addonUrls, $linkParams];
		}

		return $result;
	}

	private function getSectionsMenu(array $order, array $filter): array
	{
		$select = ['ID', 'IBLOCK_ID', 'CODE', 'SECTION_PAGE_URL', 'NAME', 'DEPTH_LEVEL'];
		if ($this->arParams['SELECT']) {
			$select = array_values(array_unique(array_merge($select, $this->arParams['SELECT'])));
		}

		$result = [];
		$sections = \CIBlockSection::GetList($order, $filter, false, $select, false);
		while ($section = $sections->GetNext()) {
			$linkParams = $this->arParams['SELECT'] ? $section : [];
			$addonUrls = !empty($this->arParams['ADDON_URL_TO_SELECT_ITEM'])
				? [\CIBlock::ReplaceSectionUrl($this->arParams['ADDON_URL_TO_SELECT_ITEM'], $section, false, 'S')]
				: [];
			$this->modifyItem($section);
			$result[] = [$section['NAME'], $section['SECTION_PAGE_URL'], $addonUrls, $linkParams];
		}

		return $result;
	}

	private function modifyItem(array &$item): void
	{
		$serializedModifier = $this->arParams['~MODIFY_ITEM'] ?? null;
		if (empty($serializedModifier)) {
			return;
		}

		$modifier = unserializeClosure($serializedModifier);
		if (is_callable($modifier)) {
			$modifier($item);
		}
	}
}
