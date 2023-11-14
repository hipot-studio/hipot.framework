<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 2022
 * @version 2.0
 */
/** @noinspection AutoloadingIssuesInspection */
namespace Hipot\Components;

defined('B_PROLOG_INCLUDED') || die();

use Hipot\BitrixUtils\IblockUtils,
	Bitrix\Main;

/**
 * Уникальный компонент всяческих листов элементов инфоблока
 *
 * @version 5.x, см. CHANGELOG.TXT
 * @copyright 2022, hipot studio
 */
class IblockList extends \CBitrixComponent
{
	private const LINKED_CHAINS_CLASS = '\\Hipot\\IbAbstractLayer\\IblockElemLinkedChains';

	/**
	 * @var \Hipot\IbAbstractLayer\IblockElemLinkedChains
	 */
	private $obChainBuilder;

	public function onPrepareComponentParams($arParams)
	{
		\CpageOption::SetOptionString("main", "nav_page_in_session", "N");

		$arParams['PAGEN_1']			    = (int)$_REQUEST['PAGEN_1'];
		$arParams['SHOWALL_1']			    = (int)$_REQUEST['SHOWALL_1'];
		$arParams['NAV_TEMPLATE']		    = (trim($arParams['NAV_TEMPLATE']) != '') ? $arParams['NAV_TEMPLATE'] : '';
		$arParams['NAV_SHOW_ALWAYS']	    = (trim($arParams['NAV_SHOW_ALWAYS']) == 'Y') ? 'Y' : 'N';
		$arParams['SELECT_CHAINS']          = (trim($arParams['SELECT_CHAINS']) == 'Y') ? 'Y' : 'N';
		$arParams['SELECT_CHAINS_DEPTH']    = (int)$arParams['SELECT_CHAINS_DEPTH'] > 0 ? (int)$arParams['SELECT_CHAINS_DEPTH'] : 3;

		if ($arParams['SELECT_CHAINS'] == 'Y' && !class_exists(static::LINKED_CHAINS_CLASS)) {
			$arParams['SELECT_CHAINS'] = 'N';
		}

		return $arParams;
	}

	public function executeComponent()
	{
		$arParams =& $this->arParams;
		$arResult =& $this->arResult;

		if ($this->startResultCache(false, $this->getAdditionalCacheId())) {
			Main\Loader::includeModule("iblock");

			if ($arParams["ORDER"]) {
				$arOrder = $arParams["ORDER"];
			} else {
				$arOrder = ["SORT" => "ASC"];
			}

			$arFilter = ["IBLOCK_ID" => $arParams["IBLOCK_ID"], "ACTIVE" => "Y"];
			if (count($arParams["FILTER"]) > 0) {
				$arFilter = array_merge($arFilter, $arParams["~FILTER"]);
			}

			$arNavParams = false;
			if ($arParams["NTOPCOUNT"] > 0) {
				$arNavParams["nTopCount"] = $arParams["NTOPCOUNT"];
			} else if ($arParams["PAGESIZE"] > 0) {
				$arNavParams["nPageSize"]	= $arParams["PAGESIZE"];
				$arNavParams["bShowAll"]	= ($arParams['NAV_SHOW_ALL'] == 'Y');
			}

			$arSelect = ["ID", "IBLOCK_ID", "DETAIL_PAGE_URL", "NAME", "TIMESTAMP_X"];
			if ($arParams["SELECT"]) {
				$arSelect = array_merge($arSelect, $arParams["SELECT"]);
			}

			$arResult["ITEMS"] = [];

			// QUERY 1 MAIN
			$rsItems = \CIBlockElement::GetList($arOrder, $arFilter, false, $arNavParams, $arSelect);

			if ($arParams['SELECT_CHAINS'] == 'Y') {
				// создаем объект, должен создаваться до цикла по элементам, т.к. в него складываются
				// уже выбранные цепочки в качестве кеша
				$className = static::LINKED_CHAINS_CLASS;
				$this->obChainBuilder = new $className();
			}

			while ($arItem = $rsItems->GetNext()) {
				if ($arParams['GET_PROPERTY'] == "Y") {
					// QUERY 2
					$arItem['PROPERTIES'] = IblockUtils::selectElementProperties(
						(int)$arItem['ID'],
						(int)$arItem["IBLOCK_ID"],
						false,
						["EMPTY" => "N"],
						($arParams['SELECT_CHAINS'] == 'Y' ? $this->obChainBuilder : null),
						(int)$arParams['SELECT_CHAINS_DEPTH']
					);
				}

				/*
				 * TOFUTURE Всяческие довыборки на каждый элемент $arItem по произвольному
				 * параметру $arParams писать тут
				 * оставить комментарий по параметру, где этот параметр используется
				 */

				$arResult["ITEMS"][] = $arItem;
			}

			// освобождаем память от цепочек
			if (isset($this->obChainBuilder)) {
				unset($this->obChainBuilder);
			}

			/*
			 * TOFUTURE Всяческие довыборки на произвольный параметр $arParams писать тут
			 * оставить комментарий по параметру, где этот параметр используется
			 */

			if (is_countable($arResult["ITEMS"]) && count($arResult["ITEMS"]) > 0) {
				if ($arParams["PAGESIZE"]) {
					if ($arParams['NAV_PAGEWINDOW'] > 0) {
						$rsItems->nPageWindow = $arParams['NAV_PAGEWINDOW'];
					}
					$arResult["NAV_STRING"] = $rsItems->GetPageNavStringEx(
						$navComponentObject,
						"",
						$arParams['NAV_TEMPLATE'],
						($arParams["NAV_SHOW_ALWAYS"] == 'Y'),
						$this
					);

					$arResult["NAV_RESULT"] = [
						'PAGE_NOMER'					=> (int)$rsItems->NavPageNomer,		// номер текущей страницы постранички
						'PAGES_COUNT'					=> (int)$rsItems->NavPageCount,		// всего страниц постранички
						'RECORDS_COUNT'					=> (int)$rsItems->NavRecordCount,	// размер выборки, всего строк
						'CURRENT_PAGE_RECORDS_COUNT'	=> count($arResult["ITEMS"])	    // размер выборки текущей страницы
					];
				}

				$this->setResultCacheKeys([
					"NAV_RESULT"
				]);
			} else {
				if ($arParams["SET_404"] == "Y") {
					include Main\Loader::getDocumentRoot() . SITE_DIR . "/404_inc.php";
				}

				$this->abortResultCache();
			}

			if (count($arResult["ITEMS"]) > 0 || $arParams["ALWAYS_INCLUDE_TEMPLATE"] == "Y") {
				$this->includeComponentTemplate();
			}
		}

		// TOFUTURE возвращаем результат (если нужно)
		return $arResult;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	private function getAdditionalCacheId(): array
	{
		return [
			$this->arParams['CACHE_GROUPS'] === 'N' ? false : $this->getUserGroupsCacheId(),
		];
	}

	/**
	 * Return user groups. Now worked only with current user.
	 *
	 * @return array
	 */
	private function getUserGroups(): array
	{
		/** @global \CUser $USER */
		global $USER;
		$result = [2];
		if (isset($USER) && $USER instanceof \CUser) {
			$result = $USER->GetUserGroupArray();
			Main\Type\Collection::normalizeArrayValuesByInt($result, true);
		}
		return $result;
	}

	/**
	 * Return user groups string for cache id.
	 *
	 * @return string
	 */
	private function getUserGroupsCacheId(): string
	{
		return implode(',', $this->getUserGroups());
	}
}