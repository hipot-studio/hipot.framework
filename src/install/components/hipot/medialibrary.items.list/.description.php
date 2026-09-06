<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/**
 * Компонент "Список элементов медиабиблиотеки"
 * medialibrary.items.list
 *
 * @version 2.0, 12.08.2017
 * @author hipot
 */

/**
 * Параметры компонента:
 *
 * COLLECTION_IDS			- числовые идентификаторы коллекций (либо один числовой идентификатор)
 * CACHE_TIME				- время кеша
 * ONLY_RETURN_ITEMS 		- Y/N, по-умолчанию N.
 * 		параметр, используется компонентом medialibrary.collection.list
 * 		если установлено в Y, то кеш отключается, а компонент не подключая шаблон возвращает
 * 		свой массив элементов (т.е. компонент возвращает свой $arResult)
 * SELECT_FILE_INFO         - Y/N, по-умолчанию N выбрать массив всех файлов из b_file в $arResult['arFileInfo'][ int ID ]
 */

$arComponentDescription = [
	"NAME"			=> "medialibrary.items.list",
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