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
 * SELECT / выбрать поля элемента/секции в параметры меню (иначе выбираются поля элемента)
 * ADDON_URL_TO_SELECT_ITEM / string Дополнительный пункт меню для выделения
 * MODIFY_ITEM / Opis\Closure\SerializableClosure позволяет создать callback с ссылкой на элемент и поменять его перед присвоением
 *
 * @version 2.0
 * @copyright hipot, 2024
 */

$arComponentDescription = array(
	"NAME"			=> basename(__DIR__),
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