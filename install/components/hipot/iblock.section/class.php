<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 02.01.2019 21:47
 * @version pre 1.0
 */
/** @noinspection AutoloadingIssuesInspection */

namespace Hipot\Components;

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
 * / FILTER - дополнительный фильтр для выбираемых секций (не документированный параметр SECTION_ID обрабатывается, выбор подсекций секции)
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
 * @see http://dev.1c-bitrix.ru/api_help/iblock/fields.php
 * @see http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getlist.php
 * @copyright 2023, hipot studio
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
			if (count($arParams['SELECT']) > 0) {
				$arSelect = array_merge($arSelect, $arParams['SELECT']);
			}

			/**
			 * Фильтр для определения, сколько элементов в секции с такими параметрами
			 * @var array
			 */
			$arElemCountFilter = ["IBLOCK_ID" => $arParams['IBLOCK_ID'],  'ACTIVE' => 'Y', 'INCLUDE_SUBSECTIONS' => 'N'];
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
				if ($arParams["SET_404"] === "Y") {
					UUtils::setStatusNotFound(true);
				}
				if ($arParams["INCLUDE_TEMPLATE_WITH_EMPTY_ITEMS"] === "Y") {
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
						($arParams["NAV_SHOW_ALWAYS"] === 'Y'),
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