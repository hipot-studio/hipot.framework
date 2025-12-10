<?php /** @noinspection GlobalVariableUsageInspection */

/**
 * скрипт создания обновлений в папке /home/bitrix/iupdate
 * @version 4.3.2
 * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=101&LESSON_ID=3220&LESSON_PATH=8781.4793.3220
 * @use
 * 1/ create dir to store updates, its may be writable
 * mkdir /home/bitrix/iupdate
 * see TODO above "create dir to store updates, usually its /home/bitrix/iupdate"
 * 2/ copy this script in folder /bitrix
 * 3/ install module from which files using this script, ex. acrit.cleanmaster
 * 4/ open in browser /bitrix/update_create_module.php?MODULE_ID=acrit.cleanmaster&no_redirect=Y to create acrit.cleanmaster updates
 * @deprecated
 * use best solution https://sondr.ru/bxmake/
 * and https://gitverse.ru/sondr/bxmake
 */

// performance fixs
define('BX_SKIP_SESSION_EXPAND', true);
define('BX_SESSION_ID_CHANGE', false);
define('BX_SKIP_POST_UNQUOTE', true);
define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('STATISTIC_SKIP_ACTIVITY_CHECK', true);
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('PERFMON_STOP', true);
// important auth
define('NEED_AUTH', true);

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 */

if (!$USER->IsAdmin()) {
	$APPLICATION->AuthForm('Bad access, please login!', true, true, 'Y', true);
	exit;
}

//
// TODO create dir to store updates, usually its BASE_UPDATES_STORE_DIR + iupdate: /home/bitrix/iupdate
//
const BASE_UPDATES_STORE_DIR = '/home/bitrix/';

$mayModules = [
		'acrit.core',
		'acrit.exportpro',
		'acrit.seo',
		'acrit.cleanmaster',
		'acrit.voicesearch',
		'acrit.catprice',
		'acrit.examination',
		'acrit.import',
		'acrit.bonus',
		'acrit.unisender',
		'vbcherepanov.importuser'
];
if (!in_array($_GET['MODULE_ID'], $mayModules, false)) {
	die('module not supported');
}

$bSuccess = false;
$arResult = [];
$arErrors = [];
$arUpdater = [];
$arSettings = [];

$arSettings["MODULE_ID"] = htmlspecialcharsbx($_GET['MODULE_ID']);
$arSettings["U_PATH"] = '/iupdate/' . $arSettings["MODULE_ID"] . '/';
$arSettings["UPDATE_PATH"] = BASE_UPDATES_STORE_DIR . $arSettings["U_PATH"];
$arSettings["MODULE_PATH"] = $_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/' . $arSettings["MODULE_ID"] . '/';

$arSettings["DIR_READ_NOFOLLOW"] = [
		$arSettings["MODULE_PATH"] . 'install/components/iservice/'
];

$arSettings["DIR_NOFOLLOW"] = [
	//$arSettings["MODULE_PATH"] . 'install/js/'
];

$arSettings["DIR_SKIP"] = [];

$arSettings["FILE_NAME_SKIP"] = [
		'.',
		'..',
		'.hg',
		'.hgignore',
		'.svn',
		'.csv',
		'.idea',
		'.gitignore'
];

$arSettings["FILE_SKIP"] = [
		$arSettings["MODULE_PATH"] . 'install/version.php',
		$arSettings["MODULE_PATH"] . 'version_control.php'
];

$arSettings["UPDATER_COPY"] = [
		"install/js" => "js",
		"install/components" => "components",
		"install/gadgets" => "gadgets",
		"install/admin" => "admin",
		"install/css" => "css",
		"install/fonts" => "fonts",
		"install/themes" => "themes",
		"install/tools" => "tools",
];

$bUseCompression = true;
if (!extension_loaded('zlib') || !function_exists("gzcompress")) {
	$bUseCompression = false;
}
require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/classes/general/tar_gz.php");

if (array_key_exists("full_arc", $_POST)) {

	if (!mkdir($concurrentDirectory = $arSettings["UPDATE_PATH"] . '.last_version') && !is_dir($concurrentDirectory)) {
		throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
	}
	CopyDirFiles($arSettings["MODULE_PATH"], $arSettings["UPDATE_PATH"] . '.last_version', true, true);
	ConvertEncodingFolder($arSettings["UPDATE_PATH"] . '.last_version');

	$tempFile = $arSettings["UPDATE_PATH"] . "/last_version_AUTO_" . time() . ".tar.gz";
	$oArchiver = new CArchiver($tempFile, $bUseCompression);
	$success = $oArchiver->add($arSettings["UPDATE_PATH"] . '.last_version', false, $arSettings["UPDATE_PATH"]);

	if ($success) {
		$bSuccess = true;
		$UPD["VERSION"] = 'Полная версия собрана - ' . basename($tempFile);
	} else {
		$arErrors[] = 'Архив не удалось собрать';
	}

} else if (array_key_exists("UPD", $_POST)) {
	$UPD = $_POST["UPD"];

	// remove if exists
	/*
	 * if($UPD["REMOVE_UPD"]=='Y')
	 * DeleteDirFilesEx($arSettings["U_PATH"].'/'.$UPD["VERSION"].'/');
	 */

	if (!isset($UPD["VERSION"]) || !preg_match('~^\d{1,}\.\d{1,}\.\d{1,}$~i', trim($UPD["VERSION"]))) {
		$arErrors[] = 'Некорректная версия обновления';
	}
	if (!$UPD["DESCRIPTION"]) {
		$arErrors[] = 'Пустое описание';
	}
	if (!isset($UPD["FOLDERS"]) && !isset($UPD["FILES"])) {
		$arErrors[] = 'Не выбраны файлы для сборки обновления';
	}
	if (is_dir($arSettings["UPDATE_PATH"] . $UPD["VERSION"])) {
		$arErrors[] = 'Директория для данного обновления уже занята';
	}
	if (empty($arErrors)) {
		if (!isset($UPD["FOLDERS"])) {
			$UPD["FOLDERS"] = [];
		}
		if (!isset($UPD["FILES"])) {
			$UPD["FILES"] = [];
		}
		// copy files
		foreach ($UPD["FOLDERS"] as $folder) {
			CopyDirFiles($arSettings["MODULE_PATH"] . $folder, $arSettings["UPDATE_PATH"] . $UPD["VERSION"] . '/' . $folder, true, true);
		}

		foreach ($UPD["FILES"] as $file)
			CopyDirFiles($arSettings["MODULE_PATH"] . $file, $arSettings["UPDATE_PATH"] . $UPD["VERSION"] . $file, true, true);

		// write version
		if (!is_dir($arSettings["UPDATE_PATH"] . $UPD["VERSION"] . '/install/')) {
			if (!mkdir($concurrentDirectory = $arSettings["UPDATE_PATH"] . $UPD["VERSION"] . '/install/') && !is_dir($concurrentDirectory)) {
				throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
			}
		}
		$fp = fopen($arSettings["UPDATE_PATH"] . $UPD["VERSION"] . '/install/version.php', 'w+');
		fwrite($fp, '<?
$arModuleVersion = array(
	"VERSION" => "' . $UPD["VERSION"] . '",
	"VERSION_DATE" => "' . date("Y-m-d H:i:s") . '"
);
?>');
		fclose($fp);

		// write description
		$fp = fopen($arSettings["UPDATE_PATH"] . $UPD["VERSION"] . '/description.ru', 'w+');
		fwrite($fp, nl2br(trim($UPD["DESCRIPTION"])));
		fclose($fp);

		foreach ($arSettings["UPDATER_COPY"] as $cFrom => $cTo) {
			if (file_exists($arSettings["UPDATE_PATH"] . $UPD["VERSION"] . '/' . $cFrom))
				$arUpdater[] = '$updater->CopyFiles("' . $cFrom . '", "' . $cTo . '");';
		}
		if (!empty($arUpdater) || trim($UPD['UPDATER_PHP']) != '') {
			// write updater
			$fp = fopen($arSettings["UPDATE_PATH"] . $UPD["VERSION"] . '/updater.php', 'w+');
			fwrite($fp, '<? /* @var CUpdater $updater */
' . implode("\n", $arUpdater) . "\n\n" .  trim($UPD['UPDATER_PHP']) . '
?>');
			fclose($fp);
		}

		// cleanup file_name_skip
		iCleanUp($arSettings["UPDATE_PATH"] . $UPD["VERSION"] . '/');

		// convert to cp1251
		ConvertEncodingFolder($arSettings["UPDATE_PATH"] . $UPD["VERSION"]);

		// tar update
		if ($UPD["ARCHIVE"] == "Y") {
			unlink($arSettings["UPDATE_PATH"] . "/" . $UPD["VERSION"] . ".tar.gz");
			$tempFile = $arSettings["UPDATE_PATH"] . "/" . $UPD["VERSION"] . ".tar.gz";


			$oArchiver = new CArchiver($tempFile, $bUseCompression);
			$success = $oArchiver->add($arSettings["UPDATE_PATH"] . $UPD["VERSION"], false, $arSettings["UPDATE_PATH"]/*.$UPD["VERSION"]*/);
			if (!$success)
				$arErrors[] = 'Не удалось создать архив';
		}

		$bSuccess = true;
	}
}

function iCleanUp($path)
{
	global $arSettings;
	if ($handle = opendir($path)) {
		while (false !== ($file = readdir($handle))) {
			if ($file == '.' || $file == '..')
				continue;
			// FILE_NAME_CLEANUP
			if (in_array($file, $arSettings["FILE_NAME_SKIP"])) {
				$tmpPath = str_replace($_SERVER["DOCUMENT_ROOT"], '', $path . $file . '/');
				DeleteDirFilesEx($tmpPath);
			}

			if (is_dir($path . $file . '/'))
				iCleanUp($path . $file . '/');
		}
	}
	closedir($handle);
}

function iReadDir($path)
{
	global $arResult, $arSettings;
	if ($handle = opendir($path)) {
		while (false !== ($file = readdir($handle))) {
			// FILE_NAME_SKIP
			if (in_array($file, $arSettings["FILE_NAME_SKIP"]))
				continue;

			// DIR_READ_NOFOLLOW
			if (is_dir($path . $file . '/') && in_array($path . $file . '/', $arSettings["DIR_SKIP"]))
				continue;
			elseif (is_dir($path . $file . '/') && in_array($path . $file . '/', $arSettings["DIR_NOFOLLOW"])) {
				$arResult[$path . $file . '/'] = [];
				continue;
			} elseif (in_array($path, $arSettings["DIR_READ_NOFOLLOW"])) {
				if (is_dir($path . $file . '/'))
					$arResult[$path . $file . '/'] = [];
				else {
					/** @noinspection UnsupportedStringOffsetOperationsInspection */
					$arResult[$path][] = $file;
				}

				continue;
			} else {
				if (is_dir($path . $file . '/')) {
					if (!array_key_exists($path, $arResult))
						$arResult[$path] = [];
					iReadDir($path . $file . '/');
				} else {
					// !FILE_SKIP
					if (!in_array($path . $file, $arSettings["FILE_SKIP"])) {
						/** @noinspection UnsupportedStringOffsetOperationsInspection */
						$arResult[$path][] = $file;
					}
				}
			}
		}

		closedir($handle);
	}
}

// lib
function ConvertEncodingFolder($path)
{
	if (is_dir($path)) // dir
	{
		$dir = opendir($path);
		while ($item = readdir($dir)) {
			if ($item == '.' || $item == '..')
				continue;

			$f = __FUNCTION__;
			$f($path . '/' . $item);
		}
		closedir($dir);
	} else // file
	{
		ProcessEncodingFile($path);
	}
}

function ProcessEncodingFile($file)
{
	// only lang files!
	if (strpos($file, '/lang/') === false && strpos($file, '/description') === false) {
		return;
	}

	$content = file_get_contents($file);

	if (GetStringCharset($content) != 'utf8')
		return;

	if ($content === false)
		CreateUpdateError('Не удалось прочитать файл: ' . $file);

	if (file_put_contents($file, mb_convert_encoding($content, 'cp1251', 'utf8')) === false)
		CreateUpdateError('Не удалось сохранить файл: ' . $file);
}
function GetStringCharset($str)
{
	global $APPLICATION;
	if (preg_match("/[\xe0\xe1\xe3-\xff]/", $str))
		return 'cp1251';
	$str0 = $APPLICATION->ConvertCharset($str, 'utf8', 'cp1251');
	if (preg_match("/[\xe0\xe1\xe3-\xff]/", $str0, $regs))
		return 'utf8';
	return 'ascii';
}
function CreateUpdateError($text)
{
	die('<font color=red>' . $text . '</font>');
}

// start reading
iReadDir($arSettings["MODULE_PATH"]);

foreach ($arResult as $k => $v) {
	natsort($v);
	$arResult[$k] = $v;
}

if (trim($UPD["VERSION"]) == '') {
	$versions = [];
	foreach (glob($arSettings["UPDATE_PATH"] . '*.tar.gz') as $file) {
		$file = basename($file);
		if (preg_match('~^\d{1,}\.\d{1,}\.\d{1,}~i', trim($file))) {
			if (is_string($file)) {
				$versions[] = str_replace('.tar.gz', '', $file);
			}
		}
	}
	natsort($versions);
	$versions = array_reverse($versions);
	$UPD["VERSION"] = $versions[0];
}


?><!doctype html>
<html>
<head>
	<title>Сборщик обновлений модулей <?= $arSettings["MODULE_ID"] ?></title>
	<style>
		* {font-family:Helvetica, Arial, sans-serif; }
		textarea {width:100%;}
		body {padding:10px 10px; margin:0px;  font-size:12pt; background: #c0c6c9; color:#231f20;}
		.num_checks {font-weight:bold; color: #b06161; position:fixed; top:600px; left:10px;}
		pre {font-family: Consolas, 'Courier New', Courier, monospace; overflow-x:auto; width:100%; max-width:800px; font-size:13px;}
		pre, pre * {font-family: Consolas, 'Courier New', Courier, monospace;}
		.interface {}
		.interface .info {width:45%;}
		.interface .checks_col {width:55%; padding-left: 25px; white-space:nowrap; }

		.main-form-wrapper{position: relative;}
		.select-files-by-list-block{position: absolute; top: 0; right: 0; max-width: 600px; display: flex; flex-direction: column; width: 100%;}
		.select-files-by-list-block .head {display: inline-block; padding: 4px 0px; font-size: 16px; width: max-content;}
		.select-files-by-list-block button {margin-top: 20px; width: max-content;}
		#files-list-update {max-width: 90%; font-size: 16px; line-height: 1.5; height:500px;}
		.meta {color:#8f8f8f;}

		/*! copy-to-clipboard START */
		.copy-to-clipboard{
			position: relative;
			cursor: copy;
		}
		.copy-to-clipboard:hover{
			font-weight: bold;
		}
		.copy-to-clipboard:hover::after,
		.copy-to-clipboard--success::after,
		.copy-to-clipboard--error::after{
			padding: 2px;
			position: absolute;
			left: 0;
			top: 0;
			background-color: #000;
			color: #fff;
			font-size: 14px;
			font-weight: normal;
			transform: translateY(-80%);
			opacity: 0;
		}
		.copy-to-clipboard:hover::after{
			content: 'копировать текст';
			opacity: 1;
		}
		.copy-to-clipboard--success:hover::after,
		.copy-to-clipboard--success::after{
			content: 'текст скопирован в буфер обмена';
			animation: copy-to-clipboard-success-after 2s linear forwards;
		}
		.copy-to-clipboard--success{
			background-color: #0377D9;
			color: #fff;
			cursor: text;
		}
		.copy-to-clipboard--error{
			animation: copy-to-clipboard-error 2s linear forwards;
		}
		.copy-to-clipboard--error::after{
			display: none;

		}
		@keyframes copy-to-clipboard-success-after {
			0%, 100%{
				opacity: 0;
			}
			50%{
				opacity: 1;
			}
		}
		@keyframes copy-to-clipboard-error {
			0%, 50%{
				opacity: 1;
				cursor: not-allowed;
			}
			25%, 75%{
				opacity: 0;
			}
			100%{
				opacity: 1;
				cursor: copy;
			}
		}
		/*! copy-to-clipboard END */
	</style>
	<?$APPLICATION->ShowHead()?>
	<?CJSCore::Init(['jquery3']) ?>
	<style>

	</style>
	<script>
		let cnt = 0;
		function showCntChecks(input)
		{
			if (input.checked) {
				cnt++;
			} else {
				cnt--;
			}
			$(".num_checks").html('Выбрано: ' + cnt);
		}

		$(function (){
			$(".checks_col :checkbox").each(function (){
				$(this).on('change', function (){
					showCntChecks(this);
				});
			});

			(function () {
				document
					.querySelector(".select-files-by-list-block button")
					.addEventListener("click", () => {
						const filesListUpdate = document
							.getElementById("files-list-update")
							.value.split("\n")
							.filter((filePath) => filePath.trim() !== "")
							.map((filePath) => ({ name: filePath, found: false }));

						filesListUpdate.forEach((file) => {
							document
								.getElementById("files-check-list")
								.querySelectorAll("input")
								.forEach((input) => {
									const inputFileName =
										input.getAttribute("value").charAt(0) === "/"
											? input.getAttribute("value").substring(1)
											: input.getAttribute("value");
									if (inputFileName === file.name) {
										input.checked = true;
										file.found = true;
										showCntChecks(input);
									}
								});
						});

						document.getElementById("files-list-update").value = filesListUpdate
							.filter((file) => !file.found)
							.map((file) => file.name)
							.join("\n");
					});
			})();

			$(window).on('click', function(event) {
				$('.copy-to-clipboard--success, .copy-to-clipboard--error').removeClass('copy-to-clipboard--success copy-to-clipboard--error');

				if($(event.target).hasClass('copy-to-clipboard')){
					const $target = $(event.target);

					navigator.clipboard.writeText($target.text()).then(function() {

						$target.addClass('copy-to-clipboard--success');

					}).catch(function(err) {

						console.error('Ошибка при копировании текста: ', err);
						$target.addClass('copy-to-clipboard--error');

					});
				}
			});
		});
	</script>
</head>
<body>

<h1>Создание Updater'ов для модуля <?= $arSettings["MODULE_ID"] ?></h1>

<? if (!empty($arErrors)): ?>
	<div style="color: red;"><?= implode('<br/>', $arErrors) ?></div>
<? endif; ?>

<? if ($bSuccess): ?>
	<div style="color: green;">обновление <?= htmlspecialchars($UPD["VERSION"]) ?> собрано</div>
<? endif; ?>

<p><a href="https://partners.1c-bitrix.ru/personal/modules/update.php?ID=<?=htmlspecialcharsbx($_GET['MODULE_ID'])?>"
      target="_blank">Залить архив в маркетплейс</a></p>

<form method="POST">
	<input type="hidden" name="full_arc" value='Y'>
	<input type="submit" value="Собрать полную сборку"><br/>
	<div style="font-weight:bold;font-size:14px;">(<span style="color:crimson">!!!</span>)Перед заливкой в маркетплейс полной сборки обязательно проверить версию в
		файле <code>install/version.php</code>!</div>
</form>

<hr size="1"/>

<div class="main-form-wrapper">
	<form method="POST">
		<table class="interface">
			<tr>
				<td class="info" valign="top">Версия: <input type="text" name="UPD[VERSION]" value="<?= htmlspecialchars($UPD["VERSION"]) ?>"><br/>
					<small>(указана последняя версия из папки сборок, необходимо нарастить!)</small>
					<br><br>
					Описание:<br/> <textarea name="UPD[DESCRIPTION]" rows="7"><?= htmlspecialcharsbx($UPD["DESCRIPTION"]) ?></textarea>
					<br/>
					<br/>
					<input type="submit" value="Собрать"><br/>
					<? /*<input type="checkbox" name="UPD[REMOVE_UPD]" value="Y"> Очистить директорию сборки<br/>*/ ?>
					<input type="checkbox" name="UPD[ARCHIVE]" value="Y" checked>Создать архив<br/>

					<span class="num_checks"></span>

					<br/><br/><br/>
					Дополнительный код в updater.php:<br/>
					<small style="color:#333;">должен быть валидный php-код
						<pre>/* @var $updater CUpdater */
// $updater->CopyFiles(...
// $updater->QueryBatch(...

<span class="copy-to-clipboard">// Remove trash
$strRoot = \Bitrix\Main\Loader::getDocumentRoot();
$arTrash = [
	'/bitrix/modules/acrit.bonus/profiles',
	'/bitrix/modules/acrit.bonus/lib/profiles/example',
];
foreach ($arTrash as $strFile) {
	if (is_file($strRoot . $strFile)) {
		\Bitrix\Main\IO\File::deleteFile($strRoot . $strFile);
	} else if (is_dir($strRoot . $strFile)) {
		\Bitrix\Main\IO\Directory::deleteDirectory($strRoot . $strFile);
	}
}</span>

<span class="copy-to-clipboard">// Index in db
if ($updater->TableExists("acrit_bonus_accounts") && !$DB->IndexExists("acrit_bonus_accounts", ["MIGRATE_ID"])) {
	$updater->Query("CREATE INDEX ix_acrit_bonus_MIGRATE_ID ON acrit_bonus_accounts(MIGRATE_ID)");
}</span>

<span class="copy-to-clipboard">// update table data
if ($updater->TableExists("acrit_cleanmaster_bitrix_standard")) {
	$updater->Query('TRUNCATE TABLE acrit_cleanmaster_bitrix_standard');
	$updater->QueryBatch("install/db/mysql/acrit_cleanmaster_bitrix_standard_dump.001.sql");
	$updater->QueryBatch("install/db/mysql/acrit_cleanmaster_bitrix_standard_dump.002.sql");
	$updater->QueryBatch("install/db/mysql/acrit_cleanmaster_bitrix_standard_dump.003.sql");
}</span>

<span class="copy-to-clipboard">// add column
if ($updater->TableExists("acrit_import_profile")
	&& !$DB->Query("SHOW COLUMNS FROM `acrit_import_profile` LIKE 'ACTIONS_DEFAULT_CATALOG_FIELDS'")->Fetch()
) {
	$updater->Query("ALTER TABLE acrit_import_profile ADD `ACTIONS_DEFAULT_CATALOG_FIELDS` char(1) NOT NULL DEFAULT 'N'");
}</span>

<span class="copy-to-clipboard">// check core module
if (! \Bitrix\Main\Loader::includeModule('acrit.core')) {
	$errorMessage = 'Not found core module acrit.core';
}</span>

<span class="copy-to-clipboard">// check version
$needVersion = '8.1';
if (! CheckVersion(PHP_VERSION, $needVersion)) {
	$errorMessage = sprintf('Update need PHP version >=%s, current version is %s. Please, update PHP version to %s+', $needVersion, PHP_VERSION, $needVersion);
}</span>
</pre></small><br/>
					<textarea name="UPD[UPDATER_PHP]" rows="7" style="font-family: Consolas, 'Courier New', Courier, monospace"><?= htmlspecialcharsbx($UPD["UPDATER_PHP"]) ?></textarea>
					<br/><br/>
					<a href="https://dev.1c-bitrix.ru/learning/course/?COURSE_ID=101&LESSON_ID=3218&LESSON_PATH=8781.4793.3218" target="_blank">Структура обновления модуля</a><br/>

				</td>
				<td align="left" valign="top" class="checks_col" id="files-check-list">
					<?
					$l = strlen($arSettings["MODULE_PATH"]) - 1;

					$arResultKeys = array_keys($arResult);
					natsort($arResultKeys);
					$arResultEx = [];
					foreach ($arResultKeys as $sortPath) {
						$arResultEx[$sortPath] = $arResult[$sortPath];
					}
					$arResult = $arResultEx;
					unset($arResultEx);

					foreach ($arResult as $path => $arFile): ?>
						<input type="checkbox" id="<?=md5(substr($path, $l))?>" value="<?= substr($path, $l) ?>" name="UPD[FOLDERS][]" <?/*if (substr($path, $l) == '/') { ?>checked<? }*/?>>
						<label for="<?=md5(substr($path, $l))?>"><b><?= substr($path, $l) ?></b></label><br/>
						<? foreach ($arFile as $file): ?>
							<input id="<?=md5(substr($path, $l) . $file)?>" type="checkbox" value="<?= substr($path, $l) . $file ?>" name="UPD[FILES][]">
							<label for="<?=md5(substr($path, $l) . $file)?>"><small><i><?= $file ?></i> <span class="meta">(<?=CFile::FormatSize(filesize($path . $file))?>)</span></small></label><br/>
						<? endforeach; ?>
					<? endforeach; ?>
				</td>
			</tr>
		</table>
	</form>
	<div class="select-files-by-list-block">
		<span class="head">Выбрать файлы по списку:</span>
		<textarea rows="7" name="files-list" id="files-list-update"></textarea>
		<button>Выбрать</button>
		<pre>// список измененных файлов можно получить так, где $1 $2 - id коммитов
(чаще всего с изменения $1 по HEAD)

<span class="copy-to-clipboard">git diff --diff-filter=ACMRT --name-only $1 $2</span>

// список измененных файлов одного коммита
<span class="copy-to-clipboard">git diff-tree --no-commit-id --name-only -r $1</span></pre>

	</div>
</div>

</body>
</html>
<?
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php";
?>