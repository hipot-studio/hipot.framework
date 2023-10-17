<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/**
 * Компонент с php-кешем для построения ext-меню со списком инфоблоков или секций
 *
 * TYPE - elements|sections - тип выборки, элементы или секции (ОБЯЗАТЕЛЬНЫЙ)
 * CACHE_TAG - пусть сохранения кеша, будет сохранено /bitrix/cache/php/CACHE_TAG/ (ОБЯЗАТЕЛЬНЫЙ)
 * CACHE_TIME - время кеша (ОБЯЗАТЕЛЬНЫЙ)
 *
 * Параметры выборки
 *
 * IBLOCK_ID / конечно же указать инфоблок
 * ORDER / если нужна иная сортировка, по-умолчанию array("SORT" => "ASC")
 * FILTER / если нужна еще какая-то дополнительная фильтрация
 *
 * @version 1.0
 * @copyright hipot, 2017
 */

$arComponentDescription = [
	"NAME"			=> basename(__DIR__),
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