<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * Компонент выбора списка всех секций.
 * Может использоваться для постоения рубрикатора (вместо меню) для секций
 *
 * $arParams
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
 * / NO_404 Y|N не подключать вывод ошибки
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
 *
 * @see http://dev.1c-bitrix.ru/api_help/iblock/fields.php
 * @see http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getlist.php
 * @copyright 2019, hipot AT ya DOT com
 * @version 4.x, см. CHANGELOG.TXT
 */

$arComponentDescription = array(
	"NAME"			=> "iblock.section list mutator",
	"DESCRIPTION"	=> "",
	"ICON"			=> "/images/ico.gif",
	"PATH" => array(
		"ID"		=> "hipot_root",
		"NAME"		=> "hipot"
	),
	"AREA_BUTTONS"	=> array(),
	"CACHE_PATH"	=> "Y",
	"COMPLEX"		=> "N"
);
?>