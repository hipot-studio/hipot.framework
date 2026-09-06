<?
IncludeModuleLangFile(__FILE__);

class hipot_framework extends CModule
{
	const MODULE_ID = 'hipot.framework';
	var $MODULE_ID = 'hipot.framework';
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $strError = '';
	var $errors = '';

	public function __construct()
	{
		$arModuleVersion = [];
		include __DIR__ . "/version.php";
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = GetMessage("hipot.framework_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("hipot.framework_MODULE_DESC");

		$this->PARTNER_NAME = GetMessage("hipot.framework_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("hipot.framework_PARTNER_URI");
	}

	/** @noinspection PhpHierarchyChecksInspection */
	public function InstallDB($arParams = [])
	{
		global $DB, $APPLICATION;

		$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/". $this->MODULE_ID . "/install/db/".strtolower($DB->type)."/install.sql");
		if ($this->errors !== false) {
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		$this->InstallEvents();

		return true;
	}

	/** @noinspection PhpHierarchyChecksInspection */
	public function UnInstallDB($arParams = [])
	{
		global $DB, $APPLICATION;

		$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/". $this->MODULE_ID . "/install/db/".strtolower($DB->type)."/uninstall.sql");
		if ($this->errors !== false) {
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		$this->UnInstallEvents();

		$DB->Query( "DELETE FROM b_option WHERE `MODULE_ID`='{$this->MODULE_ID}' AND `NAME`='~bsm_stop_date'" );
		return true;
	}

	public function InstallEvents()
	{
		RegisterModuleDependences('main', 'OnBeforeProlog', self::MODULE_ID, 'hiFrameworkGlobalEvents', 'InitFrameworkBeforeProlog', 10);
		return true;
	}

	/** @noinspection PhpHierarchyChecksInspection */
	public function UnInstallEvents()
	{
		UnRegisterModuleDependences('main', 'OnBeforeProlog', self::MODULE_ID, 'hiFrameworkGlobalEvents', 'InitFrameworkBeforeProlog');
		return true;
	}

	public function InstallFiles($arParams = [])
	{
		$installBasePath = is_dir($_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . self::MODULE_ID)
								? $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . self::MODULE_ID
								: $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID;

		if (is_dir($p = $installBasePath . '/install/components')) {
			if ($dir = opendir($p)) {
				while (false !== $item = readdir($dir)) {
					if ($item == '..' || $item == '.') {
						continue;
					}
					CopyDirFiles($p . '/' . $item, $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/' . $item, $ReWrite = True, $Recursive = True);
				}
				closedir($dir);
			}
		}

		if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/'.self::MODULE_ID)) {
			/** @noinspection MkdirRaceConditionInspection */
			mkdir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/' . self::MODULE_ID);
		}
		CopyDirFiles($installBasePath . '/install/js/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/js/', true, true);
		return true;
	}

	/** @noinspection PhpHierarchyChecksInspection */
	public function UnInstallFiles()
	{
		$installBasePath = is_dir($_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . self::MODULE_ID)
			? $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . self::MODULE_ID
			: $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID;

		if (is_dir($p = $installBasePath . '/install/components')) {
			if ($dir = opendir($p)) {
				while (false !== $item = readdir($dir)) {
					if ($item == '..' || $item == '.' || !is_dir($p0 = $p . '/' . $item)) {
						continue;
					}

					$dir0 = opendir($p0);
					while (false !== $item0 = readdir($dir0)) {
						if ($item0 == '..' || $item0 == '.') {
							continue;
						}
						DeleteDirFilesEx('/bitrix/components/' . $item . '/' . $item0);
					}
					closedir($dir0);
				}
				closedir($dir);
			}
		}

		unlink($installBasePath . '/admin/user_date_bsm.php');
		DeleteDirFilesEx( "/bitrix/js/" . self::MODULE_ID . "/" );
		return true;
	}

	////////////////////////////////////////

	public function DoInstall()
	{
		global $APPLICATION, $DB;
		$licenceDB = $DB->Query("SELECT * FROM b_option WHERE `MODULE_ID`='{$this->MODULE_ID}' AND `NAME`='~bsm_stop_date'");
		if ($licenceDB->Fetch()) {
			$DB->Query("DELETE FROM b_option WHERE `MODULE_ID`='{$this->MODULE_ID}' AND `NAME`='~bsm_stop_date'");
		}
		$this->InstallFiles();
		$this->InstallDB();
		RegisterModule(self::MODULE_ID);
	}

	/** @noinspection PhpHierarchyChecksInspection */
	public function DoUninstall()
	{
		global $APPLICATION, $DB;
		UnRegisterModule(self::MODULE_ID);
		$this->UnInstallFiles();
		$this->UnInstallDB();
	}
}
?>