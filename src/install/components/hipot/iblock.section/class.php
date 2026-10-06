<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 02.01.2019 21:47
 * @version pre 1.0
 */
/** @noinspection AutoloadingIssuesInspection */

namespace Hipot\Components;

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;
use Hipot\Services\BitrixEngine;
use Hipot\Utils\UUtils;

/**
 * Компонент выбора списка всех секций.
 * Может использоваться для постоения рубрикатора (вместо меню) для секций
 *
 * <code>
 * $arParams:
 * / IBLOCK_ID - Инфоблок из которого выбираем
 * / SELECTED_SECTION_ID - ID выбранной секции (если это страница секции)
 * / SELECTED_SECTION_CODE - CODE выбранной секции (если это страница секции)
 * / SECTION_CODE_PATH = Y если используется SECTION_CODE_PATH в настройках ИБ
 * / CACHE_TIME - понятно
 * / ORDER - сортировка выбираемых секций
 * / FILTER - дополнительный фильтр; SECTION_ID выбирает все вложенные разделы указанного родителя
 * / ID_FIRST_QUERY Y|N сначала выбрать ID, затем загрузить полные данные найденных разделов
 * / SELECT_COUNT Y|N выбрать ли кол-во элементов в секции, выбираются два кол-ва ELEMENT_CNT - стандартное поле секций
 * 		и ELEMENT_CNT_FROM_ELEMS - это кол-во элементов с заданнымы параметрами при помощи SELECT_COUNT_ELEM_FILTER
 * / SELECT_COUNT_ELEM_FILTER - дополнительный фильтр для определения кол-ва элементов в секции через
 * 		CIBlockElement::GetList (см. параметр SELECT_COUNT)
 * / INCLUDE_SEO Y|N Вывести ли СЕО по секции (если это страница секции с SELECTED_SECTION_ID или SELECTED_SECTION_CODE)
 * / ADDON_PRE_CHAINS - массив массивов дополнительных пунктов, которые нужно включить в хлебные крошки до выбранной
 * 		секции (требует INCLUDE_SEO => Y), структура одного массива array('TEXT' => 'Страница', 'URL' => '/page.php')
 * / SET_404 Y|N подключать ли вывод 404й ошибки
 * / INCLUDE_TEMPLATE_WITH_EMPTY_ITEMS Y|N подключить ли шаблон компонента, в случае если не выбрано ни одной секции
 *
 * / PAGESIZE / сколько элементов на странице, при постраничной навигации
 * / NAV_TEMPLATE / шаблон постранички (по-умолчанию .default)
 * / NAV_SHOW_ALWAYS / показывать ли постаничку всегда (по-умолчанию N)
 * / NAV_SHOW_ALL / (разрешить ли вывод ссылки по просмотру всех элементов на одной странице)
 * / NAV_PAGEWINDOW / ширина диапазона постранички, т.е. напр. тут ширина = 3 "1 .. 3 4 5 .. 50" (т.е. 3,4,5 - 3 шт)
 *
 *
 * $arResult
 * / SECTIONS - массив всех выбранных секций со всеми полями секций, а также двумя дополнительными:
 * 		SELECTED = Y - если секция выбрана из SELECTED_SECTION_ID или SELECTED_SECTION_CODE
 * 		ELEMENT_CNT_FROM_ELEMS - кол-во элементов с параметрами SELECT_COUNT_ELEM_FILTER
 * / CUR_SECTION - текущая секция со всеми полями, как и в массиве SECTIONS
 * </code>
 *
 * @see https://dev.1c-bitrix.ru/api_help/iblock/fields.php
 * @see https://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getlist.php
 * @copyright 2026, hipot studio
 * @version 4.x, см. CHANGELOG.TXT
 */
final class IblockSection extends \CBitrixComponent
{
	public function onPrepareComponentParams($arParams)
	{
		\CPageOption::SetOptionString('main', 'nav_page_in_session', 'N');

		if (! isset($arParams['CACHE_TIME'])) {
			$arParams['CACHE_TIME'] = 3600;
		}

		$arParams['PAGEN_1']				= (int)$_REQUEST['PAGEN_1'];
		$arParams['SHOWALL_1']				= (int)$_REQUEST['SHOWALL_1'];
		$arParams['NAV_TEMPLATE']			= (trim($arParams['NAV_TEMPLATE']) != '') ? $arParams['NAV_TEMPLATE'] : '';
		$arParams['NAV_SHOW_ALWAYS']		= (trim($arParams['NAV_SHOW_ALWAYS']) == 'Y') ? 'Y' : 'N';
		$arParams['ID_FIRST_QUERY']       = (trim($arParams['ID_FIRST_QUERY'] ?? '') === 'Y') ? 'Y' : 'N';

		/**
		 * проверяем выбранную секцию (для организации рубрикаторов)
		 */
		$arParams['SELECTED_SECTION_ID']	= (int)$arParams['SELECTED_SECTION_ID'];
		if ($arParams['SECTION_CODE_PATH'] == 'Y') {
			$path                              = array_filter(explode('/', trim($arParams['SELECTED_SECTION_CODE'])));
			$arParams['SELECTED_SECTION_CODE'] = array_pop($path);
			$path                              = array_filter(explode('/', trim($arParams['FILTER']['CODE'])));
			$arParams['FILTER']['CODE']        = array_pop($path);
		} else {
			$arParams['SELECTED_SECTION_CODE'] = trim($arParams['SELECTED_SECTION_CODE']);
		}
		$arParams['SELECT_COUNT']			   = $arParams['SELECT_COUNT'] === 'Y';

		$arParams['SELECT_COUNT_ELEM_FILTER']  = (array)$arParams['SELECT_COUNT_ELEM_FILTER'];
		$arParams['SELECT']                    = (array)$arParams['SELECT'];
		$arParams['FILTER']                    = (array)$arParams['FILTER'];

		$arParams['IS_SHOW_INCLUDE_AREAS'] = BitrixEngine::getCurrentUserD0()->IsAuthorized() ? BitrixEngine::getAppD0()->GetShowIncludeAreas() : false;

		return $arParams;
	}

	public function executeComponent()
	{
		if ($this->startResultCache(false)) {
			Loader::requireModule('iblock');
			$rsSections = \CIBlockSection::GetList(
				$this->arParams['ORDER'] ?: ['SORT' => 'ASC'],
				$this->getSectionFilter(),
				false,
				$this->usesIdFirstQuery() ? ['ID'] : $this->arParams['SELECT'],
				$this->getNavigationParams(),
			);
			$rsNavigation = $rsSections;
			$sections = $this->usesIdFirstQuery()
				? $this->hydrateSectionsByIds($this->fetchSectionIds($rsSections))
				: $rsSections;

			foreach ($this->iterateSections($sections) as $section) {
				$this->arResult['SECTIONS'][] = $this->prepareSection($section);
			}

			if (empty($this->arResult['SECTIONS'])) {
				$this->handleEmptyResult();
			} else {
				$this->prepareNavigation($rsNavigation);
				$this->setResultCacheKeys(['CUR_SECTION']);
				$this->includeComponentTemplate();
			}
		}

		if ($this->arParams['INCLUDE_SEO'] == 'Y' && !empty($this->arResult['CUR_SECTION'])) {
			$this->includeSectionSEO();
		}

		return $this->arResult;
	}

	private function usesIdFirstQuery(): bool
	{
		return $this->arParams['ID_FIRST_QUERY'] === 'Y';
	}

	/**
	 * @param \CIBlockResult $sections
	 * @return int[]
	 */
	private function fetchSectionIds($sections): array
	{
		$ids = [];
		while ($section = $sections->Fetch()) {
			$id = (int)$section['ID'];
			if ($id > 0) {
				$ids[$id] = $id;
			}
		}

		return array_values($ids);
	}

	/**
	 * @param int[] $ids
	 * @return array<int, array>
	 */
	private function hydrateSectionsByIds(array $ids): array
	{
		if (empty($ids)) {
			return [];
		}

		$select = $this->arParams['SELECT'];
		if (!empty($select) && !in_array('ID', $select, true)) {
			$select[] = 'ID';
		}

		$result = \CIBlockSection::GetList(
			[],
			[
				'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
				'ID' => $ids,
			],
			false,
			$select,
			['nTopCount' => count($ids)],
		);
		$sectionsById = [];
		while ($section = $result->GetNext()) {
			$sectionsById[(int)$section['ID']] = $section;
		}

		$sections = [];
		foreach ($ids as $id) {
			if (isset($sectionsById[$id])) {
				$sections[] = $sectionsById[$id];
			}
		}

		return $sections;
	}

	/**
	 * @param \CIBlockResult|array $sections
	 * @return \Generator<int, array>
	 */
	private function iterateSections($sections): \Generator
	{
		if (is_array($sections)) {
			yield from $sections;
			return;
		}

		while ($section = $sections->GetNext()) {
			yield $section;
		}
	}

	private function getSectionFilter(): array
	{
		$filter = array_merge(
			['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'ACTIVE' => 'Y'],
			$this->arParams['FILTER'],
		);
		$parentSectionId = (int)($filter['SECTION_ID'] ?? 0);
		if ($parentSectionId <= 0) {
			return $filter;
		}

		$parentSection = SectionTable::query()
			->setSelect(['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL'])
			->where('ID', $parentSectionId)
			->where('IBLOCK_ID', (int)$this->arParams['IBLOCK_ID'])
			->setCacheTtl((int)$this->arParams['CACHE_TIME'])->cacheJoins(true)
			->fetch();
		unset($filter['SECTION_ID']);

		if (!$parentSection) {
			$filter['ID'] = false;
			return $filter;
		}

		return array_merge($filter, [
			'>LEFT_MARGIN' => $parentSection['LEFT_MARGIN'],
			'<RIGHT_MARGIN' => $parentSection['RIGHT_MARGIN'],
			'>DEPTH_LEVEL' => $parentSection['DEPTH_LEVEL'],
		]);
	}

	private function getNavigationParams(): array|false
	{
		if ((int)$this->arParams['PAGESIZE'] <= 0) {
			return false;
		}

		return [
			'nPageSize' => (int)$this->arParams['PAGESIZE'],
			'bShowAll' => $this->arParams['NAV_SHOW_ALL'] === 'Y',
		];
	}

	private function prepareSection(array $section): array
	{
		if ($this->arParams['SELECT_COUNT']) {
			$section['ELEMENT_CNT_FROM_ELEMS'] = $this->getSectionElementCount((int)$section['ID']);
		}

		if ($this->isCurrentSection($section)) {
			$this->arResult['CUR_SECTION'] = $section;
			$section['SELECTED'] = 'Y';
		}

		return $section;
	}

	private function getSectionElementCount(int $sectionId): int
	{
		$filter = array_merge(
			[
				'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
				'ACTIVE' => 'Y',
				'INCLUDE_SUBSECTIONS' => 'N',
			],
			$this->arParams['SELECT_COUNT_ELEM_FILTER'],
			['SECTION_ID' => $sectionId],
		);

		return (int)\CIBlockElement::GetList(['SORT' => 'ASC'], $filter, [], false, ['ID']);
	}

	private function isCurrentSection(array $section): bool
	{
		return (int)$section['ID'] === (int)$this->arParams['SELECTED_SECTION_ID']
			|| ($this->arParams['SELECTED_SECTION_CODE'] !== ''
				&& $section['CODE'] !== ''
				&& $section['CODE'] === $this->arParams['SELECTED_SECTION_CODE']);
	}

	private function handleEmptyResult(): void
	{
		$this->abortResultCache();
		if ($this->arParams['SET_404'] === 'Y') {
			UUtils::setStatusNotFound(true);
		}
		if ($this->arParams['INCLUDE_TEMPLATE_WITH_EMPTY_ITEMS'] === 'Y') {
			$this->includeComponentTemplate();
		}
	}

	/** @param \CIBlockResult $sections */
	private function prepareNavigation($sections): void
	{
		if ((int)$this->arParams['PAGESIZE'] <= 0) {
			return;
		}
		if ((int)$this->arParams['NAV_PAGEWINDOW'] > 0) {
			$sections->nPageWindow = (int)$this->arParams['NAV_PAGEWINDOW'];
		}

		$this->arResult['NAV_STRING'] = $sections->GetPageNavStringEx(
			$navComponentObject,
			'',
			$this->arParams['NAV_TEMPLATE'],
			$this->arParams['NAV_SHOW_ALWAYS'] === 'Y',
			$this,
		);
	}

	public function includeSectionSEO()
	{
		$arParams =& $this->arParams;
		$arResult =& $this->arResult;

		BitrixEngine::getAppD0()->SetTitle($arResult['CUR_SECTION']['NAME']);
		/**
		 * иногда требуется добавить несколько ссылок в хлебные крошки до включения самого выбранного раздела
		 */
		foreach ($arParams['ADDON_PRE_CHAINS'] as $arPre) {
			BitrixEngine::getAppD0()->AddChainItem($arPre['TEXT'], $arPre['URL']);
		}
		BitrixEngine::getAppD0()->AddChainItem($arResult['CUR_SECTION']['NAME'], $arResult['CUR_SECTION']['SECTION_PAGE_URL']);
	}
}
