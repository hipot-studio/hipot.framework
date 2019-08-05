<?
// .left.menu_ext.php с выбором элементов
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$aMenuLinksExt = $APPLICATION->IncludeComponent("hipot:iblock.menu_ext", "", array(
	"CACHE_TAG"		=> 'left_menu_ext',
	'CACHE_TIME'	=> 3600,
	'TYPE'			=> 'elements',
	"IBLOCK_ID"		=> 2,
));
$aMenuLinks = array_merge((array)$aMenuLinks, (array)$aMenuLinksExt);




// .right.menu_ext.php с выбором секций
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$aMenuLinksExt = $APPLICATION->IncludeComponent("hipot:iblock.menu_ext", "", array(
	"CACHE_TAG"		=> 'right_menu_ext',
	'CACHE_TIME'	=> 3600,
	'TYPE'			=> 'sections',
	'ORDER'			=> array('name' => 'asc'),
	'SELECT'		=> array('UF_*'),
	"IBLOCK_ID"		=> 4,
));
$aMenuLinks = array_merge((array)$aMenuLinks, (array)$aMenuLinksExt);


// TODO bitrix:menu use only default components
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$aMenuLinksExt = $APPLICATION->IncludeComponent("bitrix:menu.sections","", array(
		"IS_SEF" => "Y",
		"SEF_BASE_URL" => "/catalog/sale/",
		"SECTION_PAGE_URL" => "#SECTION_CODE#/",
		"DETAIL_PAGE_URL" => "#SECTION_ID#/#ELEMENT_ID#",
		"IBLOCK_TYPE" => "catalog",
		"IBLOCK_ID" => "7",
		"DEPTH_LEVEL" => 4,
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600"
	)
);
$aMenuLinks = array_merge((array)$aMenuLinks, (array)$aMenuLinksExt);


?>