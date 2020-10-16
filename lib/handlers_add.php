<?
/**
 * Установка обработчиков и их описание.
 * Желательно описание (определение класса и метода) делать отдельно от данного файла
 *
 * Т.е. в данном файле пишем AddEventHandler
 * а сам обработчик в файле с классом /include/lib/classes/siteevents.php
 */

// определяем глобальные константы, которые могут зависеть от $APPLICATION и $USER
AddEventHandler("main", "OnBeforeProlog", static function () {
	global $APPLICATION, $USER;

	foreach (
		[
			$_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/constants.php',
			$_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/lib/constants.php',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/constants.php',
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/lib/constants.php',
			__DIR__ . '/constants.php'
		] as $constFile) {
		if (is_file($constFile)) {
			include $constFile;
			break;
		}
	}

	// включаем генератор ORM
	if ($APPLICATION->GetCurPage() == '/bitrix/admin/perfmon_tables.php' && $_GET['orm'] != 'y') {
		LocalRedirect( $APPLICATION->GetCurPageParam("orm=y") );
	}

	if (defined("ADMIN_SECTION")) {
		ob_start();
		?>
		<script type="text/javascript">
			BX.ready(function(){
				try {
					var mess = BX.findChild(BX('adm-workarea'), {'class' : 'adm-info-message'}, true);
					if (mess && mess.textContent && mess.textContent.search('пробная') != -1) {
						BX.remove( BX.findChild(BX('adm-workarea'), {'class' : 'adm-info-message-wrap'}, true) );
					}
				} catch (ignore) {
				}
			});
		</script>
		<?
		$APPLICATION->oAsset->addString( ob_get_clean() );
	}
});

// проставляем id инфоблоков в административном меню
AddEventHandler("main", "OnBuildGlobalMenu", static function (&$aGlobalMenu, &$aModuleMenu) {
	if (! $GLOBALS['USER']->IsAdmin() || !defined("ADMIN_SECTION")) {
		return;
	}
	foreach ($aModuleMenu as $k => $arMenu) {
		if ($arMenu['icon'] != 'iblock_menu_icon_types') {
			continue;
		}
		foreach ($arMenu['items'] as $i => $item) {
			$arEx = explode('/', $item['items_id']);
			$aModuleMenu[$k]['items'][$i]['text'] .= ' [' . $arEx[2] . ']';
		}
	}
});

// верхняя постраничка в админке в лентах
AddEventHandler("main", "OnAdminListDisplay", static function ($this_al) {
	/* @var $this_al CAdminList */
	if (in_array($this_al->table_id, ['tbl_user'])) {
		return;
	}
	//echo $this_al->sNavText;
});

// draw user picture after login
AddEventHandler("main", "OnAdminListDisplay", /** @param CAdminUiList $this_al */static function (&$this_al) {
	if ($this_al->table_id == "tbl_user") {
		foreach ($this_al->aRows as &$row) {
			$userId = (int)$row->arRes['ID'];
			$picPath = CFile::GetPath( (CUser::GetByID($userId)->Fetch())["PERSONAL_PHOTO"] );
			if (trim($picPath) != '') {
				$row->aFields["LOGIN"]["view"]["value"] .= ' <br><a target="_blank" href="' . $picPath . '">'
					. '<img style="max-width:200px;" src="' . $picPath  . '"></a>';
			}
		}
	}
});

// очищаем настройки формы по-умолчанию для всех админов
// @see http://hipot.mooo.com/Codex/form_iblock_element_settings/
AddEventHandler('main', 'OnEndBufferContent', static function (&$content) {
	if (!isset($_POST['p']) || !is_array($_POST['p']) || count($_POST['p']) <= 0) {
		return;
	}

	global $APPLICATION, $DB, $CACHE_MANAGER;

	$pCfg 		= array_shift($_POST['p']);

	if ($APPLICATION->GetCurPage() != '/bitrix/admin/user_options.php'
		|| $pCfg['c'] != 'form' || $pCfg['d'] != 'Y'
		|| !preg_match('#^form_((section)|(element))_[\d]+$#', $pCfg['n'])
	) {
		return;
	}

	$DB->Query("DELETE FROM b_user_option WHERE CATEGORY = 'form' AND NAME = '" . $pCfg['n'] . "' AND COMMON = 'N'");
	$CACHE_MANAGER->CleanDir("user_option");
});

AddEventHandler('main', 'OnEndBufferContent', static function (&$cont) {
	global $APPLICATION;

	// process 404 in content part
	if (defined('ERROR_404') && constant('ERROR_404') == 'Y' && $APPLICATION->GetCurPage() != '/404.php') {
		$contCacheFile  = $_SERVER['DOCUMENT_ROOT'] . '/404_cache.html';

		if (is_file($contCacheFile) && ((time() - filemtime($contCacheFile)) > 3600)) {
			unlink($contCacheFile);
		}

		$cont = file_get_contents($contCacheFile);
		if (trim($cont) == '') {
			//$cont = QueryGetData($_SERVER['HTTP_HOST'], 80, '/404.php', '', $errno, $errstr);
			$el = new \Bitrix\Main\Web\HttpClient();
			$cont = $el->get((CMain::IsHTTPS() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/404.php');
			file_put_contents($contCacheFile, $cont);
		}

		header("HTTP/1.0 404 Not Found\r\n");
	}
});

?>