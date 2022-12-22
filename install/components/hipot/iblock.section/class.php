<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 02.01.2019 21:47
 * @version pre 1.0
 */
namespace Hipot\Components;

class IblockSection extends \CBitrixComponent
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
		$arParams['SELECT_COUNT']			   = $arParams['SELECT_COUNT'] == 'Y' ? true : false;

		return $arParams;
	}

	public function executeComponent()
	{
		$arParams =& $this->arParams;
		$arResult =& $this->arResult;

		if ($this->startResultCache(false)) {

			\CModule::IncludeModule('iblock');

			$arOrder = ['SORT' => 'ASC'];
			if (! empty($arParams['ORDER'])) {
				$arOrder = $arParams['ORDER'];
			}

			$arFilter = ['IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ACTIVE' => 'Y'];
			if (count($arParams['FILTER']) > 0) {
				$arFilter = array_merge($arFilter, $arParams['FILTER']);
			}

			// не реализованный выбор подсекций
			if ((int)$arFilter['SECTION_ID'] > 0) {
				$thisSection = \CIBlockSection::GetByID((int)$arFilter['SECTION_ID'])->Fetch();

				if ((int)$thisSection['ID'] == 0) {
					$arFilter['ID']			= false;
				} else {
					$arFilter = array_merge($arFilter, [
						"<=LEFT_MARGIN"		=> $thisSection["LEFT_MARGIN"],
						">=RIGHT_MARGIN"	=> $thisSection["RIGHT_MARGIN"],
						">DEPTH_LEVEL"		=> $thisSection["DEPTH_LEVEL"],
					]);
				}
				unset($arFilter['SECTION_ID'], $thisSection);
			}

			$arSelect = [];
			if (is_array($arParams['SELECT']) && count($arParams['SELECT']) > 0) {
				$arSelect = array_merge($arSelect, $arParams['SELECT']);
			}

			/**
			 * Фильтр для определения, сколько элементов в секции с такими параметрами
			 * @var array
			 */
			$arElemCountFilter = ["IBLOCK_ID" => $arParams['IBLOCK_ID'],  'ACTIVE' => 'Y', 'INCLUDE_SUBSECTIONS' => 'Y'];
			if (count($arParams['SELECT_COUNT_ELEM_FILTER']) > 0) {
				$arElemCountFilter = array_merge($arElemCountFilter, $arParams['SELECT_COUNT_ELEM_FILTER']);
			}

			$arNavStartParams = false;
			if ($arParams["PAGESIZE"] > 0) {
				$arNavStartParams["nPageSize"]	= $arParams["PAGESIZE"];
				$arNavStartParams["bShowAll"]	= ($arParams['NAV_SHOW_ALL'] == 'Y');
			}

			/**
			 * QUERY
			 */
			$rsSect = \CIBlockSection::GetList($arOrder, $arFilter, false, $arSelect, $arNavStartParams);
			while ($arSect = $rsSect->GetNext()) {
				if ($arParams['SELECT_COUNT']) {
					$cntElemsRes = \CIBlockElement::GetList(
						["SORT" => "ASC"],
						array_merge($arElemCountFilter, ['SECTION_ID' => $arSect['ID']]),
						[], false, ['ID']
					);
					$arSect['ELEMENT_CNT_FROM_ELEMS'] = (int)$cntElemsRes;
				}

				/**
				 * выбираем текущую секцию, переданную через параметр
				 */
				if ($arSect['ID'] == $arParams['SELECTED_SECTION_ID']
					|| (trim($arParams['SELECTED_SECTION_CODE']) != '' && trim($arSect['CODE']) != ''
						&& $arSect['CODE'] == $arParams['SELECTED_SECTION_CODE'])
				) {
					$arResult['CUR_SECTION'] = $arSect;
					$arSect['SELECTED'] = 'Y';
				}

				//
				// TOFUTURE разнообразные мутаторы по секции тут (модификации, довыборки)
				//
				$arResult['SECTIONS'][] = $arSect;
			}

			//
			// TOFUTURE разнообразные мутаторы по всем секциям тут (довыборки)
			//

			if (empty($arResult['SECTIONS'])) {
				$this->abortResultCache();
				if ($arParams['NO_404'] != 'Y') {
					include $_SERVER['DOCUMENT_ROOT'] . '/404_inc.php';
				}
				if ($arParams["INCLUDE_TEMPLATE_WITH_EMPTY_ITEMS"] == "Y") {
					$this->includeComponentTemplate();
				}
			} else {

				if ($arParams["PAGESIZE"]) {
					if ($arParams['NAV_PAGEWINDOW'] > 0) {
						$rsSect->nPageWindow = $arParams['NAV_PAGEWINDOW'];
					}
					$arResult["NAV_STRING"] = $rsSect->GetPageNavStringEx(
						$navComponentObject,
						"",
						$arParams['NAV_TEMPLATE'],
						($arParams["NAV_SHOW_ALWAYS"] == 'Y'),
						$this
					);
				}

				$this->setResultCacheKeys(['CUR_SECTION']);
				$this->includeComponentTemplate();
			}
		}

		if ($arParams['INCLUDE_SEO'] == 'Y' && !empty($arResult['CUR_SECTION'])) {
			$this->includeSectionSEO();
		}

		//
		// TO FUTURE
		//
		return $arResult;
	}

	public function includeSectionSEO()
	{
		global $APPLICATION;
		$arParams =& $this->arParams;
		$arResult =& $this->arResult;

		$APPLICATION->SetTitle($arResult['CUR_SECTION']['NAME']);
		/**
		 * иногда требуется добавить несколько ссылок в хлебные крошки до включения самого выбранного раздела
		 */
		foreach ($arParams['ADDON_PRE_CHAINS'] as $arPre) {
			$APPLICATION->AddChainItem($arPre['TEXT'], $arPre['URL']);
		}
		$APPLICATION->AddChainItem($arResult['CUR_SECTION']['NAME'], $arResult['CUR_SECTION']['SECTION_PAGE_URL']);
	}
}