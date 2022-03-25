<?
/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 *
 * @var $arParams array
 */


//preg_replace_callback example
echo preg_replace_callback('|#H2#(.*?)#/H2#|is', function ($matches) {
	return '<h2>' . strip_tags($matches[1]) . '</h2>';
}, $CurPost["TEXT_FORMATED"]); ?>

<span><?= ToLower(FormatDate('d F Y', MakeTimeStamp($item['DATE_ACTIVE_FROM']))) ?></span>

<?
//dbconn composite debug
define("BX_COMPOSITE_DEBUG", true);
define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"] . "/bx_log.txt");
?>

<?
// для производительности и прочие фиксы на крон
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_BUFFER_USED", true); // ?
define('BX_NO_ACCELERATOR_RESET', true); // ?
?>

<?
// bx csv
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/csv_data.php";
$csvFile = new CCSVData('R', true);
$csvFile->LoadFile($_FILES['csv']['tmp_name']);
$csvFile->SetDelimiter(',');
while ($arRes = $csvFile->Fetch()) {
}
?>


<?
// old-way Выбор элементов со свойствами
CModule::IncludeModule('iblock');


$arOrder = array('SORT' => 'ASC');
$arFilter = array("IBLOCK_ID" => $arParams["IBLOCK_ID"], "ACTIVE" => "Y");

$arNavParams = false;
//$arNavParams = array('nTopCount' => 1);
//$arNavParams = array('nPageSize' => 1, 'bShowAll' => true);

$arSelect = array("ID", "IBLOCK_ID", "DETAIL_PAGE_URL", "NAME", "TIMESTAMP_X");

// QUERY 1 MAIN
$rsItems = CIBlockElement::GetList($arOrder, $arFilter, false, $arNavParams, $arSelect);

while ($arItem = $rsItems->GetNext()) {
	// QUERY 2
	$db_props = CIBlockElement::GetProperty(
		$arItem["IBLOCK_ID"],
		$arItem['ID'],
		array("sort" => "asc"),
		array("EMPTY" => "N")
	);
	while ($ar_props = $db_props->GetNext()) {
		// для свойств TEXT/HTML не верно экранируются символы
		if ($ar_props['PROPERTY_TYPE'] == "S" && isset($ar_props['VALUE']['TEXT'], $ar_props['VALUE']['TYPE'])) {
			$ar_props['VALUE']['TEXT'] = FormatText($ar_props['VALUE']['TEXT'], $ar_props['VALUE']['TYPE']);
		}

		if ($ar_props['MULTIPLE'] == "Y") {
			$arItem['PROPERTIES'][$ar_props['CODE']][] = $ar_props;
		} else {
			$arItem['PROPERTIES'][$ar_props['CODE']] = $ar_props;
		}
	}

}
?>


<?
// cache
$CACHE_ID = __FILE__ . 'sect_to_menu' . $section_id;
$obMenuCache = new CPHPCache;
if ($obMenuCache->StartDataCache(FilterController::$cacheTime, $CACHE_ID, FilterController::$cachePath)) {
	if (count($ar) == 0) {
		$obMenuCache->AbortDataCache();
	} else {
		$obMenuCache->EndDataCache(array("ar" => $ar));
	}

} else {
	$arVars = $obMenuCache->GetVars();
	$ar = $arVars["ar"];
}
// end cache
?>


<?
// Пример выборки дерева подразделов для раздела
$rsParentSection = CIBlockSection::GetByID($ID);
if ($arParentSection = $rsParentSection->GetNext()) {
	$arFilter = array(
		'IBLOCK_ID' => $arParentSection['IBLOCK_ID'],
		'>LEFT_MARGIN' => $arParentSection['LEFT_MARGIN'],
		'<RIGHT_MARGIN' => $arParentSection['RIGHT_MARGIN'],
		'>DEPTH_LEVEL' => $arParentSection['DEPTH_LEVEL']
	);
	$rsSect = CIBlockSection::GetList(array('left_margin' => 'asc'), $arFilter);
	while ($arSect = $rsSect->GetNext()) {
		// получаем подразделы
	}
}
?>


<?
// ajax check
if ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
	exit;
}
?>


<script type="text/javascript">
	// $.ajax пример
	$(".notify_product_p").each(function () {
		var _npp = this;
		$('.notify_product[pid]', _npp).each(function () {
			var __np = this;
			$(__np).click(function () {
				var pid = parseInt($(__np).attr('pid'));
				if (pid < 0 || $(__np).data('posted') == true) {
					return;
				}
				// get all post data
				var js_data = {'ID': pid};

				// lock click
				$(__np).data('posted', true).fadeTo(0, 0.3);

				$.ajax({
					async: true,
					cache: false,
					data: js_data,
					dataType: 'html',
					timeout: 8000,
					type: 'POST',
					url: '/bitrix/templates/esky/ajax_php/absent_message.php',
					error: function (jqXHR, textStatus, errorThrown) {
						$(__np).data('posted', false).fadeTo(0, 1);
					},
					success: function (data, textStatus, jqXHR) {
						$(_npp).html('Вам на email прийдет сообщение о поступлении товара на сайт.').css('top', '-20px').css('cursor', 'default');
					}
				});

			});
		});
	});

	// телефон с хвостом
	$.mask.definitions['~'] = '[+-]';
	$('input[name="PHONE"], .phone_mask').mask("+7 999 999 99 99?999999", {placeholder : "*"});
</script>


<?
// bitrix:search.page в файле result_modifier.php
if (count($arResult["SEARCH"]) > 0) {

	$arIDs = array();
	foreach ($arResult["SEARCH"] as $si => $arItem) {
		if ($arItem["MODULE_ID"] == "iblock" && substr($arItem["ITEM_ID"], 0, 1) !== "S") {
			// связь: iblock_id => id : search_id
			$arIDs[$arItem['PARAM2']][$arItem["ITEM_ID"]] = $si;
		}
	}

	CModule::IncludeModule('iblock');

	foreach ($arIDs as $iblockId => $searchIds) {
		// для инфоблоков 2.0 передавать IBLOCK_ID для выбора свойств обязательно
		$grab = CIBlockElement::GetList(array(), array(
			"IBLOCK_ID" => $iblockId,
			"ID" => array_keys($searchIds)
		), false, false, array(
			"ID",
			"IBLOCK_ID",
			"PREVIEW_PICTURE",
			// needed props
			"PROPERTY_tags"
		));
		while ($ar = $grab->Fetch()) {
			$ar['PICTURE'] = CFile::GetFileArray($ar["PREVIEW_PICTURE"]);

			$si = $arIDs[$iblockId][$ar["ID"]];
			$arResult["SEARCH"][$si]["ELEMENT"] = $ar;
		}
	}
}
?>

<?
// init.php - устранение слеша на конце
if (preg_match('#^(.*?[^/])$#', $_SERVER["REQUEST_URI"], $m) && !preg_match('#(\.php)|(\?)#', $_SERVER["REQUEST_URI"])) {
	LocalRedirect($m[0] . '/', true, "301 Moved Permanently");
}
?>


<?
// select order by pseudo-ID
if (trim($_REQUEST['ORDER_ID']) != '') {
	\Bitrix\Main\Loader::includeModule('sale');
	$order = \Bitrix\Sale\Order::getList(array(
		'filter' => array('ACCOUNT_NUMBER' => $_REQUEST['ORDER_ID']),
		'select' => array('*')
	))->fetch();
}
?>


<?
// get cloud backup pass when lost
define('BX_BUFFER_USED', true);
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/backup.php";
var_dump(CPasswordStorage::Get('dump_temporary_cache'));
?>

<?
// csv bitrix
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/csv_data.php";
$csvFile = new CCSVData('R', true);
$csvFile->LoadFile($_FILES['csv']['tmp_name']);
$csvFile->SetDelimiter(',');
while ($arRes = $csvFile->Fetch()) {
	//ec($arRes);
}
?>

<?
// element seo update
$ipropTemplates = new \Bitrix\Iblock\InheritedProperty\ElementTemplates($el['IBLOCK_ID'], $el['ID']);
$ipropTemplates->set(array(
	"ELEMENT_META_TITLE" => $p['DETAIL_PARAMS']['SEO_TITLE'],
	"ELEMENT_META_DESCRIPTION" => $p['DETAIL_PARAMS']['SEO_DESCRIPTION'],
));
?>

<?
// restore agents
COption::SetOptionString("main", "agents_use_crontab", "Y");
echo COption::GetOptionString("main", "agents_use_crontab", "N");

COption::SetOptionString("main", "check_agents", "Y");
echo COption::GetOptionString("main", "check_agents", "N");

COption::SetOptionString("main", "mail_event_bulk", "20");
echo COption::GetOptionString("main", "mail_event_bulk", "5");
?>

<?
CJSCore::Init(array("jquery"));
$arJqueryExt = CJSCore::getExtInfo("jquery");
?>

<?
// 2. Как отменить композитное кеширование в любом месте страницы (проголосовать "против") ?
\Bitrix\Main\Composite\Page::getInstance()->markNonCacheable();
?>

<?
// Вывод штрихкода, добавление штрихкода и изменение штрихкода.

$dbBarCode = CCatalogStoreBarCode::getList(array(), array("PRODUCT_ID" => $arResult["ID"]));
$arBarCode = $dbBarCode->GetNext();
if ($arBarCode === false) {
	$dbBarCode = CCatalogStoreBarCode::Add(array("PRODUCT_ID" => $arResult["ID"], "BARCODE" => $barcode, "CREATED_BY" => $USER->GetID()));
} elseif ($arBarCode["ID"]["BARCODE"] != $barcode) {
	$dbBarCode = CCatalogStoreBarCode::Update($arBarCode["ID"], array("BARCODE" => $barcode, "MODIFIED_BY" => $USER->GetID()));
}
?>

<?
/** @var \Bitrix\Sale\Order $order */
$order = \Bitrix\Sale\Order::load($arOrder['ID']);
\Bitrix\Sale\OrderStatus::isAllowPay($order->getField('STATUS_ID'))
?>

<?php
# http://mamihlapinatapai.mooo.com/bitrix/admin/repair_db.php?lang=ru&optimize_tables=Y

$arTable["Name"] = "b_stat_searcher_hit";
$rsStatus = $DB->Query('check table `'.$arTable["Name"].'`');
$rsStatus = $DB->Query('optimize table `'.$arTable["Name"].'`');
$rsStatus = $DB->Query('analyze table `'.$arTable["Name"].'`');
# check table b_stat_searcher_hit;
# optimize table b_stat_searcher_hit;
# analyze table b_stat_searcher_hit;
?>


<?php
function CleatCatalogCompositCache($arFields)
{
	global $USER, $CACHE_MANAGER;
	if (! in_array($arFields['IBLOCK_ID'], [54, 32])) {
		return;
	}
	$el = CIBlockElement::GetList([], ['ID' => (int)$arFields['ID']], false, false,
		['ID', 'DETAIL_PAGE_URL', 'IBLOCK_ID'])->GetNext();
	if (trim($el['DETAIL_PAGE_URL']) != '') {

		$basePath = '/bitrix/html_pages/q-watch.ru';
		if (is_dir(\Bitrix\Main\Loader::getDocumentRoot() . $basePath . $el['DETAIL_PAGE_URL'])) {
			DeleteDirFilesEx($basePath . $el['DETAIL_PAGE_URL']);
		}

		$basePath = '/bitrix/html_pages/www.citytime.ru';
		if (is_dir(\Bitrix\Main\Loader::getDocumentRoot() . $basePath . $el['DETAIL_PAGE_URL'])) {
			DeleteDirFilesEx($basePath . $el['DETAIL_PAGE_URL']);
		}
	}
}

AddEventHandler('iblock', 'OnAfterIBlockElementUpdate', 'CleatCatalogCompositCache');
AddEventHandler('iblock', 'OnAfterIBlockElementAdd', 'CleatCatalogCompositCache');
?>

<?php
exec ("find /var/www/vhosts/matugalnik.ru/httpdocs/bitrix/modules/bitrixcloud/classes/general  -type d -exec chmod 0750 {} +");
exec ("find /var/www/vhosts/matugalnik.ru/httpdocs/bitrix/modules/bitrixcloud/classes/general  -type f -exec chmod 0644 {} +");
?>


<?php
// see merge bitrix backup cmd
$dir            = '/home/bitrix/www/bitrix/backup';
$startName      = '1rmc.ru_20210309_192159_full_c46e65f1.tar.gz';

$arChParts = [];
$iter = new DirectoryIterator($dir);
foreach ($iter as $fileinfo) {
	/* @var SplFileInfo $fileinfo */
	if ($fileinfo->isDir()) {
		continue;
	}
	if (strpos($fileinfo->getFilename(), $startName) !== false) {
		$arChParts[] = $fileinfo->getFilename();
	}
}
natsort($arChParts);

$cmd = 'cat ' . implode(' ', $arChParts) . ' > merged.tar.gz';
echo 'cd ' . $dir . '<br>' . PHP_EOL;
echo $cmd;
?>

<?php
usort($arItem["VALUES"], static function ($a, $b) {
	return strnatcasecmp($a['VALUE'], $b['VALUE']);
});
?>

<?php
/* get memcached stat */
$memcache_obj = new Memcache;
$memcache_obj->addServer('unix:///home/bitrix/memcached.sock', '0');
$memcache_obj->addServer('localhost', 11211);

$stats = $memcache_obj->getExtendedStats();
print_r($stats);
print_r( CFile::FormatSize( $stats['unix:///home/bitrix/memcached.sock:0']['bytes'] ));

print_r( PHP_EOL . CFile::FormatSize( $stats['localhost:11211']['bytes'] ));
?>


<?php
// 403 admin edit /bitrix/modules/security/admin/security_403.php
?>

<?php
$_SERVER['HTTP_HOST'] = preg_replace('#:(80|443)$#', '', $_SERVER['HTTP_HOST']);
?>

<?
define("CACHED_b_iblock", 3600 * 24);
define("CACHED_b_iblock_bucket_size", 40);
?>


<?
$APPLICATION->SetTitle("title h1");
$APPLICATION->SetPageProperty('title', "title browser");    // opt set to title

// <title><?$APPLICATION->ShowTitle()?></title>
// <h1 id="pagetitle"><?$APPLICATION->ShowTitle(false)?></h1>
?>