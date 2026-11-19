<?php

use Bitrix\Main\Loader;

defined('B_PROLOG_INCLUDED') || die();

$arTypesEx = [];

Loader::includeModule('fileman');
CMedialib::Init();

$collections = CMedialibCollection::GetList([
	'arOrder'	=> ['ML_TYPE' => 'ASC']
]);
$arColIndexed = [];
foreach ($collections as $collection) {
	$arColIndexed[ $collection['ID'] ] = $collection;
}

if (! function_exists('_getCollectionParentsNames')) {
	function _getCollectionParentsNames($parentId, $arColIndexed)
	{
		$name = '';
		if (! isset($arColIndexed[ $parentId ])) {
			return $name;
		}
		$name = $arColIndexed[ $parentId ]['NAME'] . ' / ';
		if ($arColIndexed[ $parentId ]['PARENT_ID'] > 0) {
			$name = _getCollectionParentsNames($arColIndexed[ $parentId ]['PARENT_ID'], $arColIndexed) . $name;
		}
		return $name;
	}
}

foreach ($collections as $collection) {
	$arTypesEx[ $collection['ID'] ] = (_getCollectionParentsNames($collection['PARENT_ID'], $arColIndexed)) . $collection['NAME'] . ' [' . $collection['ID'] . '] ';
}
uasort($arTypesEx, static function ($a, $b) {
	if ($a == $b) {
		return 0;
	}
	return ($a < $b) ? 1 : -1;
});
unset($rsCol, $arColIndexed);

$arComponentParameters = [
	"GROUPS" => [
	],
	"PARAMETERS" => [
		"COLLECTION_IDS"	=> [
			"PARENT"		=> "BASE",
			"NAME"			=> "Collection(s)",
			"TYPE"			=> "LIST",
			"VALUES"		=> $arTypesEx,
			"DEFAULT"		=> false,
			"ADDITIONAL_VALUES" => "N",
			"MULTIPLE"			=> "Y",
			"REFRESH"			=> 'N',
			"COLS"				=> 8,
			"ROWS"				=> 8
		],
		"CACHE_TIME"  =>  ["DEFAULT"=>36000],
	],
];
