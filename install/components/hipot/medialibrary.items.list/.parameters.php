<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arTypesEx = [];

CModule::IncludeModule("fileman");
CMedialib::Init();

$rsCol = CMedialibCollection::GetList([
	'arOrder'	=> ['ML_TYPE' => 'ASC']
]);
$arColIndexed = [];
foreach ($rsCol as $ar) {
	$arColIndexed[ $ar['ID'] ] = $ar;
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

foreach ($rsCol as $ar) {
	$arTypesEx[ $ar['ID'] ] = (_getCollectionParentsNames($ar['PARENT_ID'], $arColIndexed)) . $ar['NAME'] . ' [' . $ar['ID'] . '] ';
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
?>
