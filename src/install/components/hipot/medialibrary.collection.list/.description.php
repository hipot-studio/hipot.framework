<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/**
 * Компонент "Список коллекций медиабиблиотеки"
 * medialibrary.collection.list
 *
 * @version 2.0, 12.09.2017
 * @author hipot
 */

/**
 * Параметры компонента:
 *
 * ORDER   				- сортировка коллекций, по-умолчанию array('ID' => 'ASC')
 * FILTER  				- фильтрация коллекций, можно фильтровать по полям:
 * 		ID, NAME, DESCRIPTION, ACTIVE, DATE_UPDATE, OWNER_ID, PARENT_ID,
 * 		SITE_ID, KEYWORDS, ITEMS_COUNT, ML_TYPE
 * 		по-умолчанию идет array('ACTIVE' => 'Y')
 * CACHE_TIME
 * SELECT_WITH_ITEMS 			- Y/N, по-умолчанию N.
 * 		Выбирать ли коллекции вместе со своим содержимым.
 * 		ВАЖНО! для этого дела используется другой компонент, см. параметр
 * 		ITEMS_LIST_COMPONENT_NAME, этот компонент должен существовать.
 * ITEMS_LIST_COMPONENT_NAME 	- имя компонента для выбора элементов коллекций,
 * 		по-умолчанию "hipot:medialibrary.items.list"
 */
?>