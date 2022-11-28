<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/**
* Основные параметры:
*
* IBLOCK_ID / конечно же указать инфоблок
* ORDER / если нужна иная сортировка, по-умолчанию array("SORT" => "ASC")
* FILTER / если нужна еще какая-то фильтрация
* NTOPCOUNT / ограничение количества элементов (имеет более высокий приоритет над PAGESIZE)
* PAGESIZE / сколько элементов на странице, при постраничной навигации
* SELECT / какие еще поля могут понадобится по-умолчанию array("ID", "CODE", "DETAIL_PAGE_URL", "NAME")
* GET_PROPERTY / Y – вывести все свойства
* CACHE_TIME / время кеша
* CACHE_GROUPS / N - кешировать ли группы пользователей (для интерфейса эрмитаж)
*
* Дополнительные параметры:
*
* NAV_TEMPLATE / шаблон постранички (по-умолчанию .default)
* NAV_SHOW_ALWAYS / показывать ли постаничку всегда (по-умолчанию N)
* NAV_SHOW_ALL / (разрешить ли вывод ссылки по просмотру всех элементов на одной странице)
* NAV_PAGEWINDOW / ширина диапазона постранички, т.е. напр. тут ширина = 3 "1 .. 3 4 5 .. 50" (т.е. 3,4,5 - 3 шт)
* SET_404 / Y установить ли ошибку 404 в случае пустой выборки (по-умолчанию N)
* ALWAYS_INCLUDE_TEMPLATE / Y|N подключать ли шаблон компонента в случае пустой выборки (по-умолчанию N)
* SELECT_CHAINS / Y|N выбирать ли цепочки связанных элементов
* SELECT_CHAINS_DEPTH / глубина выбираемых элементов (по умолчанию 3)
*/

$arComponentDescription = [
	"NAME"			=> "iblock.list pages mutator",
	"DESCRIPTION"	=> "",
	"ICON"			=> "/images/ico.gif",
	"PATH" => [
		"ID"		=> "hipot_root",
		"NAME"		=> "hipot"
	],
	"AREA_BUTTONS"	=> [],
	"CACHE_PATH"	=> "Y",
	"COMPLEX"		=> "N"
];
?>
