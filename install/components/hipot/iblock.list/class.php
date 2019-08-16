<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 02.01.2019 21:34
 * @version pre 1.0
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * Уникальный компонент всяческих листов элементов инфоблока
 *
 * @version 5.x, см. CHANGELOG.TXT
 * @copyright 2019, hipot studio
 */
class HiIblockListComponent extends CBitrixComponent
{
	const LINKED_CHAINS_CLASS = '\\Hipot\\IbAbstractLayer\\IblockElemLinkedChains';

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

		if ($this->startResultCache(false)) {
			\CModule::IncludeModule("iblock");

			if ($arParams["ORDER"]) {
				$arOrder = $arParams["ORDER"];
			} else {
				$arOrder = array("SORT" => "ASC");
			}

			$arFilter = array("IBLOCK_ID" => $arParams["IBLOCK_ID"], "ACTIVE" => "Y");
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

			$arSelect = array("ID", "IBLOCK_ID", "DETAIL_PAGE_URL", "NAME", "TIMESTAMP_X");
			if ($arParams["SELECT"]) {
				$arSelect = array_merge($arSelect, $arParams["SELECT"]);
			}

			// QUERY 1 MAIN
			$rsItems = \CIBlockElement::GetList($arOrder, $arFilter, false, $arNavParams, $arSelect);

			if ($arParams['SELECT_CHAINS'] == 'Y') {
				// создаем объект, должен создаваться до цикла по элементам, т.к. в него складываются
				// уже выбранные цепочки в качестве кеша
				$className = static::LINKED_CHAINS_CLASS;
				$this->obChainBuilder = new $className;
			}

			while ($arItem = $rsItems->GetNext()) {
				if ($arParams['GET_PROPERTY'] == "Y") {
					// QUERY 2
					$arItem = $this->getPropertys($arItem, $arParams);
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

			if (count($arResult["ITEMS"]) > 0) {
				if ($arParams["PAGESIZE"]) {
					if ($arParams['NAV_PAGEWINDOW'] > 0) {
						$rsItems->nPageWindow = $arParams['NAV_PAGEWINDOW'];
					}
					$arResult["NAV_STRING"] = $rsItems->GetPageNavStringEx(
						$navComponentObject,
						"",
						$arParams['NAV_TEMPLATE'],
						($arParams["NAV_SHOW_ALWAYS"] == 'Y')
					);

					$arResult["NAV_RESULT"] = array(
						'PAGE_NOMER'					=> $rsItems->NavPageNomer,		// номер текущей страницы постранички
						'PAGES_COUNT'					=> $rsItems->NavPageCount,		// всего страниц постранички
						'RECORDS_COUNT'					=> $rsItems->NavRecordCount,	// размер выборки, всего строк
						'CURRENT_PAGE_RECORDS_COUNT'	=> count($arResult["ITEMS"])	// размер выборки текущей страницы
					);
				}

				$this->setResultCacheKeys(array(
					"NAV_RESULT"
				));
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

		// TOFUTURE возвращаем результат (если нужно)
		return $arResult;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * @param       $arItem
	 * @param array $arParams
	 *
	 * @return mixed
	 */
	public function getPropertys($arItem, array $arParams)
	{
		$db_props = \CIBlockElement::GetProperty(
			(int)$arItem["IBLOCK_ID"],
			(int)$arItem['ID'],
			array("sort" => "asc"),
			array("EMPTY" => "N")
		);
		while ($ar_props = $db_props->GetNext()) {
			// для свойств TEXT/HTML не верно экранируются символы
			if ($ar_props['PROPERTY_TYPE'] == "S" && isset($ar_props['VALUE']['TEXT'], $ar_props['VALUE']['TYPE'])) {
				$ar_props['VALUE']['TEXT'] = FormatText($ar_props['VALUE']['TEXT'], $ar_props['VALUE']['TYPE']);
			}
			// довыборка цепочек глубиной 3
			if ($arParams['SELECT_CHAINS'] == 'Y' && $ar_props['PROPERTY_TYPE'] == 'E') {
				// инициализация должна происходить перед каждым вызовом getChains_r
				// с указанием выбираемой вложенности
				$this->obChainBuilder->init((int)$arParams['SELECT_CHAINS_DEPTH']);
				$ar_props['CHAIN'] = $this->obChainBuilder->getChains_r($ar_props['VALUE']);
			}
			if ($ar_props['PROPERTY_TYPE'] == 'F') {
				$ar_props['FILE_PARAMS'] = \CFile::GetFileArray($ar_props['VALUE']);
			}

			if ($ar_props['MULTIPLE'] == "Y") {
				$arItem['PROPERTIES'][$ar_props['CODE']][] = $ar_props;
			} else {
				$arItem['PROPERTIES'][$ar_props['CODE']] = $ar_props;
			}
		}
		return $arItem;
	}
}