<?php
defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;
use Bitrix\Main\Event;
use Bitrix\Main\Application;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Composite\Page as CompositePage;
use Bitrix\Main\Composite\Engine as CompositeEngine;
use Hipot\BitrixUtils\AssetsContainer;
use Hipot\BitrixUtils\HiBlockApps;
use Hipot\Services\BitrixEngine;
use Hipot\Utils\UUtils;
use Bitrix\Main\ORM\Data\DataManager;

/**
 * @var $eventManager EventManager
 * @var $request \Bitrix\Main\HttpRequest
 */

// region admin UI

// Remove admin notify message after execute updaters
$eventManager->addEventHandler('main', 'OnProlog', static function () use ($request) {
	if ($request === null || (defined('IS_BETA_TESTER') && !IS_BETA_TESTER) || !$request->isAdminSection()) {
		return;
	}
	
	$notNeededNotifyFilters = [
		[
			'MODULE_ID' => 'main',
			'TAG'       => 'checklist_cp',
		],
		[
			'MODULE_ID' => 'acrit.%',
			'TAG'       => 'TOKEN_NOTIFIER',
		],
		[
			'MODULE_ID' => 'call',
			'TAG'       => 'call_registration',
		]
	];
	foreach ($notNeededNotifyFilters as $filter) {
		$iterator    = \CAdminNotify::GetList([], $filter);
		$adminNotify = $iterator->Fetch();
		if ($adminNotify) {
			\CAdminNotify::Delete($adminNotify['ID']);
		}
	}
});

// Add meta-IDs of the information blocks in the administrative menu
$eventManager->addEventHandler("main", "OnBuildGlobalMenu", static function (&$aGlobalMenu, &$aModuleMenu) use ($request) {
	if (!defined('IS_BETA_TESTER') || !IS_BETA_TESTER || !$request->isAdminSection()) {
		return;
	}
	foreach ($aModuleMenu as $k => $arMenu) {
		if ($arMenu['icon'] != 'iblock_menu_icon_types') {
			continue;
		}
		foreach ($arMenu['items'] as $i => $item) {
			$arEx = explode('/', $item['items_id']);
			$aModuleMenu[$k]['items'][$i]['text'] .= ' /' . $arEx[2] . '/';
		}
	}
});

// Add meta-data to property names (ID and CODE), example: Article: 123, CML2_ARTICLE
$eventManager->addEventHandler('main', 'OnEndBufferContent', static function (&$strContent) use ($request) {
	if (!defined('IS_BETA_TESTER') || !IS_BETA_TESTER) {
		return;
	}
	$arAllowedUrls = ['/bitrix/admin/cat_product_edit.php', '/bitrix/admin/iblock_element_edit.php'];
	$intIBlockId = (int)$request->getQueryList()->get('IBLOCK_ID');
	
	if ($intIBlockId <= 0 || !in_array(BitrixEngine::getAppD0()->GetCurPage(false), $arAllowedUrls, false)) {
		return;
	}
	
	$arProps  = [];
	$resProps = \CIBlockProperty::getList([], ['IBLOCK_ID' => $intIBlockId]);
	while ($arProp = $resProps->fetch()) {
		$arProps[$arProp['ID']] = $arProp;
	}
	unset($resProps);
	/** @noinspection HtmlUnknownAttribute */
	/** @noinspection HtmlDeprecatedAttribute */
	$strRegExp  = '#(<tr [^>]*id="tr_PROPERTY_(\d+)"[^>]*>\s*<td class="[^>]+" width="40%">)(\s*<span[^>]+>.*?</script>&nbsp;)?(\s*[^>]+:\s*)(</td>)#is';
	$strContent = preg_replace_callback($strRegExp, static function ($arMatch) use ($arProps) {
		$strName = sprintf('%s%s <span class="hi_iblock_prop_meta">%d, %s</span>', $arMatch[3], $arMatch[4],
			$arMatch[2], $arProps[$arMatch[2]]['CODE']);
		return $arMatch[1] . $strName . $arMatch[5];
	}, $strContent);
	//
	$strCss = '
		<style>
			.hi_iblock_prop_meta {
				color:#999 !important;
				display:block !important;
				font-size:11px !important;
				padding-right:3px !important;
				text-align:right !important;
			}
		</style>
		';
	if ($request->getQueryList()->get('bxpublic') == 'Y') {
		$strContent = $strCss . $strContent;
	} else {
		$strContent = str_ireplace('</head>', $strCss . '</head>', $strContent);
	}
});

// RemoveYandexDirectTab in iblock elements
$eventManager->addEventHandler('main', 'OnAdminTabControlBegin', static function (&$TabControl) {
	$arAllowedUrls = ['/bitrix/admin/cat_product_edit.php', '/bitrix/admin/iblock_element_edit.php'];
	if (! in_array(BitrixEngine::getAppD0()->GetCurPage(false), $arAllowedUrls, false)) {
		return;
	}
	foreach ($TabControl->tabs as $Key => $arTab) {
		if ($arTab['DIV'] == 'seo_adv_seo_adv') {
			unset($TabControl->tabs[$Key]);
		}
	}
});

// draw user picture after login and top pagenav
$eventManager->addEventHandler(	"main", "OnAdminListDisplay",
	/** @param CAdminUiList $this_al */
	static function (&$this_al) {
		if ($this_al->table_id == "tbl_user" || str_contains($this_al->table_id, 'iblock')
			|| str_starts_with($this_al->table_id, 'tbl_hi')
			|| str_starts_with($this_al->table_id, 't_users_online')
		) {
			echo $this_al->sNavText;
			BitrixEngine::getInstance()->asset->addString('
				<style>
					.adm-workarea > .main-ui-pagination {padding:10px 0;}
				</style>
			');
		}
		if ($this_al->table_id == "tbl_user") {
			foreach ($this_al->aRows as &$row) {
				$userId = (int)$row->arRes['ID'];
				$picPath = CFile::GetPath( (CUser::GetByID($userId)->Fetch())["PERSONAL_PHOTO"] );
				if (trim($picPath) != '') {
					$row->aFields["LOGIN"]["view"]["value"] .= ' <br><a target="_blank" href="' . $picPath . '">'
						. '<img style="max-width:200px;" alt="" loading="lazy" src="' . $picPath  . '"></a>';
				}
			}
		}
	}
);

// Очистка настроек форм по-умолчанию для всех админов при галочке "Установить данные настройки по умолчанию для всех пользователей"
// @see https://www.hipot-studio.com/Codex/form_iblock_element_settings/
$eventManager->addEventHandler('main', 'OnBeforeEndBufferContent', static function () use ($request) {
	if (! $request->isPost()) {
		return;
	}
	
	if (BitrixEngine::getAppD0()->GetCurPage() == '/bitrix/admin/user_options.php') {
		// detail forms
		$p = $request->getPost('p');
		
		$category = 'form';
		$checkByNameRegx = '#^(form_((section)|(element)))|(hlrow_edit)_\d+$#';
	} elseif ($request['action'] == 'main.userOption.saveOptions' && BitrixEngine::getAppD0()->GetCurPage() == '/bitrix/services/main/ajax.php') {
		// list forms
		$p = $request->getJsonList()['newValues'] ?? null;
		
		$category = 'list';
		$checkByNameRegx = '#^tbl_#';
	} else {
		return;
	}
	
	if (!isset($p) || !is_array($p) || count($p) <= 0) {
		return;
	}
	$pCfg = array_shift($p);
	
	if ($pCfg['c'] != $category || $pCfg['d'] != 'Y' || !preg_match($checkByNameRegx, $pCfg['n'])) {
		return;
	}
	
	$userOptionCategory = $pCfg['c'];
	$userOptionName     = $pCfg['n'];
	
	if (BitrixEngine::getCurrentUserD0()->CanDoOperation('edit_other_settings')) {
		/** @noinspection SqlResolve */
		BitrixEngine::getInstance()->connection->query(
			sprintf("DELETE FROM b_user_option WHERE CATEGORY = '%s' AND NAME = '%s' AND COMMON = 'N'", $userOptionCategory, $userOptionName)
		);
		BitrixEngine::getInstance()->app->getManagedCache()->cleanDir("user_option");
	}
});

// endregion admin UI