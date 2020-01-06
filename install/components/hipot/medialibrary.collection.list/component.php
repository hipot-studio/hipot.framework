<?
/**
 * Вывод коллекций из медиабиблиотеки
 *
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/* @var $this CBitrixComponent */

if ($arParams["SELECT_WITH_ITEMS"] == 'Y') {
	$arParams['ITEMS_LIST_COMPONENT_NAME'] =
				(trim($arParams['ITEMS_LIST_COMPONENT_NAME']) == '')
				? "hipot:medialibrary.items.list"
				: $arParams['ITEMS_LIST_COMPONENT_NAME'];
}

if ($this->startResultCache(false)) {
	
	// довыборка элементов, если это нужно
	if ($arParams["SELECT_WITH_ITEMS"] == 'Y') {
		$arAllItems = $APPLICATION->IncludeComponent($arParams['ITEMS_LIST_COMPONENT_NAME'], '', array(
			'ONLY_RETURN_ITEMS'		=> 'Y',
			'CACHE_TIME'			=> 0		//избыточно, он и так будет 0 из-за ONLY_RETURN_ITEMS => Y
		));
		
		// массив связей, в ключе ID коллекции
		$arAllItemsIndexed = array();
		foreach ($arAllItems['ITEMS'] as $item) {
			$arAllItemsIndexed[ $item['COLLECTION_ID'] ][] = $item;
		}
		unset($arAllItems);
	}
	
	$Params = array(
		'arOrder'		=> ['ID'		=> 'ASC'],
		'arFilter'		=> ['ACTIVE'	=> 'Y']
	);
	
	if (count($arParams["ORDER"]) > 0) {
		$Params['arOrder'] = $arParams["ORDER"];
	}
	if (count($arParams["FILTER"]) > 0) {
		$Params['arFilter'] = array_merge($Params['arFilter'], $arParams["~FILTER"]);
	}
	
	CModule::IncludeModule("fileman");
	CMedialib::Init();
	
	$rsCol = CMedialibCollection::GetList($Params);
	foreach ($rsCol as $v) {
		// если у нас еще довыборка элементов идет
		if ($arParams["SELECT_WITH_ITEMS"] == 'Y') {
			$v['ITEMS'] = $arAllItemsIndexed[ $v['ID'] ];
			unset( $arAllItemsIndexed[ $v['ID'] ] );
		}
				
		$arResult['COLLECTIONS'][] = $v;
	}
	
	unset($arAllItemsIndexed);
	
	if (count($arResult['COLLECTIONS']) > 0) {
		$this->setResultCacheKeys(array());
		$this->includeComponentTemplate();
	} else {
		$this->abortResultCache();
	}
}

return $arResult;
?>