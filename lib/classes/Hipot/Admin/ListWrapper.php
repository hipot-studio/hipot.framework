<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 08.06.2017 22:38
 * @version pre 1.5
 */

namespace Hipot\Admin;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Для упрощения создания списков в админке
 * @package Hipot\Admin
 *
 * @example

require_once $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php";

global $APPLICATION, $USER, $USER_FIELD_MANAGER;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

\Bitrix\Main\Loader::includeModule('acrit.cleanmaster');
$adminTableName     = "tbl_acrit_cleanmaster_profiles";
$ormDataClass       = '\\Acrit\\Cleanmaster\\ProfilesTable';

$oAdminList = new \Hipot\Admin\ListWrapper($adminTableName, 'ID', 'ASC');
$oAdminList->postGroupActions($ormDataClass);

/** @var \UploadBfileindexTable $ormDataClass * /
$rsData = $ormDataClass::getList(array(
'select'    => array('*'),
'filter'    => array(),
'order'     => array($by => $order),
));

$arHeaders = (array)$ormDataClass::getMap();

// custom field
$arHeaders['CRON_CMD'] = array(
'data_type' => 'string',
'title' => Loc::getMessage('CRON_CMD_TITLE'),
'default' => true,
'sort' => 1
);

$oAdminList->addHeaders($arHeaders);
$oAdminList->collectAdminResultAndNav($rsData, $ormDataClass, function (&$arFieldsTable, &$arData) {
$arFieldsTable['CRON_CMD'] = 'html';
$arData['CRON_CMD'] = '<b><code>php -f ' . Bitrix\Main\Application::getDocumentRoot() . '/bitrix/modules/acrit.cleanmaster/cron/profile_run.php ' . $arData['ID'] . '</code></b>';
});
$oAdminList->addAdminContextMenuAndCheckXls();

$APPLICATION->SetTitle(GetMessage("acrit_cleanmaster_PROFILES_LIST"));
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php";

if ($isDemo != 1) {
echo BeginNote();
echo GetMessage("ACRIT_CLEANMASTER_IS_DEMO_MESSAGE");
echo '<br /><br /><input type="button" value="'.GetMessage("ACRIT_CLEANMASTER_IS_DEMO_MESSAGE_BTN").'" onclick="location.href = \''
. GetMessage('ACRIT_CLEANMASTER_IS_DEMO_MESSAGE_BUY_URL').'\'">';
echo EndNote();
} else {
echo BeginNote();
echo GetMessage("ACRIT_CLEANMASTER_CRONTAB_HELP_HTML");
echo EndNote();
}

$oAdminList->displayList();
unset($oAdminList);

require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php";
 */
class ListWrapper
{
	/**
	 * @var string
	 */
	public $sTableID;

	/**
	 * @var \CAdminList
	 */
	public $lAdmin;

	/**
	 * @var array
	 */
	public $arFields;

	/**
	 * @var \CAdminResult
	 */
	public $rsData;

	public function __construct($sTableID = '', $initSort = 'ID', $orderSort = 'ASC')
	{
		if (trim($sTableID) == '') {
			$this->sTableID = 'tbl_' . ToLower(randString());
		} else {
			$this->sTableID = $sTableID;
		}

		$oSort = new \CAdminSorting($this->sTableID, $initSort, $orderSort);
		$this->lAdmin = new \CAdminList($this->sTableID, $oSort);
	}

	/**
	 * @param array $arFieldsEx обязательно описать как передавать колонки!!!!
	 */
	public function addHeaders($arFieldsEx)
	{
		$this->arFields = array();
		foreach ($arFieldsEx as $FIELD_NAME => $FIELD_INFO) {
			$this->arFields[$FIELD_NAME] = $FIELD_INFO["data_type"];
		}

		$arHeaders = array();
		foreach ($this->arFields as $FIELD_NAME => $FIELD_TYPE) {
			$arHeaders[$FIELD_NAME] = array(
				"id" => $FIELD_NAME,
				"content" => $arFieldsEx[$FIELD_NAME]["title"] ? $arFieldsEx[$FIELD_NAME]["title"] : $FIELD_NAME,
				"sort" => $arFieldsEx[$FIELD_NAME]["sortable"] ? $FIELD_NAME : "",
				"default" => true,
			);
			if ($FIELD_TYPE == "int" || $FIELD_TYPE == "datetime" || $FIELD_TYPE == "date" || $FIELD_TYPE == "double") {
				$arHeaders[$FIELD_NAME]["align"] = "right";
			}
		}

		$this->lAdmin->AddHeaders($arHeaders);
	}

	public function getNavParams()
	{
		$navyParams = \CDBResult::GetNavParams(\CAdminResult::GetNavSize(
			$this->sTableID,
			array('nPageSize' => 20/*, 'sNavID' => $APPLICATION->GetCurPage().'?ENTITY_ID='.$ENTITY_ID*/)
		));

		$navyParams['PAGEN'] = (int)$navyParams['PAGEN'];
		$navyParams['SIZEN'] = (int)$navyParams['SIZEN'];

		return $navyParams;
	}

	/**
	 * @param \CDBResult $rsDataOrm
	 * @param string|null $ormDataClass  = null
	 * @param callable|null $itemPresaveCallback = null
	 * @param callable|null $rowActionCallback = null
	 * @param callable|null $groupActionCallback = null
	 *
	 * @internal param bool|\Hipot\Admin\CDBResult|mixed $rsData
	 * @internal param $itemPresaveCallback = null
	 */
	public function collectAdminResultAndNav($rsDataOrm, $ormDataClass = null, $itemPresaveCallback = null, $rowActionCallback = null, $groupActionCallback = null): void
	{
		$perPage = $this->getNavParams();

		$this->rsData = new \CAdminResult($rsDataOrm, $this->sTableID);

		$this->rsData->NavStart($perPage['SIZEN']);
		$this->lAdmin->NavText($this->rsData->GetNavPrint(""));

		while ($arRes = $this->rsData->Fetch()) {
			$row =& $this->lAdmin->AddRow($arRes["ID"], $arRes);

			if (is_callable($itemPresaveCallback)) {
				$itemPresaveCallback($this->arFields, $arRes);
			}

			foreach ($this->arFields as $FIELD_NAME => $FIELD_TYPE) {
				if (strlen($arRes[$FIELD_NAME]) > 0) {
					if ($FIELD_TYPE == "int" || $FIELD_TYPE == 'integer') {
						$val = round($arRes[$FIELD_NAME], 3);
					} elseif ($FIELD_TYPE == "double") {
						$val = htmlspecialcharsEx($arRes[$FIELD_NAME]);
					}
					/*elseif ($FIELD_TYPE == "datetime") {
						$val = str_replace(" ", "&nbsp;", $arRes["FULL_" . $FIELD_NAME]);
					} elseif ($FIELD_TYPE == "date") {
						$val = str_replace(" ", "&nbsp;", $arRes["SHORT_" . $FIELD_NAME]);
					}*/
					else if ($FIELD_TYPE == "html") {
						$val = $arRes[$FIELD_NAME];
					} else {
						$val = htmlspecialcharsbx($arRes[$FIELD_NAME]);
					}
					$row->AddViewField($FIELD_NAME, $val);
				}
			}

			if (is_callable($rowActionCallback)) {
				$rowActionCallback($row, $arRes, $this->lAdmin);
			} else {
				$this->setRowActions($row, $arRes);
			}
		}

		$this->rsData->NavRecordCount = $this->rsData->SelectedRowsCount();
		$this->rsData->NavPageCount = ceil($this->rsData->NavRecordCount / $perPage['SIZEN']);
		$this->rsData->NavPageNomer = $perPage['PAGEN'];

		$this->drawFooter($groupActionCallback);
	}

	/**
	 * @param callable|null $groupActionCallback
	 */
	protected function drawFooter($groupActionCallback): void
	{
		$this->lAdmin->AddFooter(
			[
				[
					"title" => Loc::getMessage("acrit_cleanmaster_MAIN_ADMIN_LIST_SELECTED"),
					"value" => $this->rsData->SelectedRowsCount(),
				],
			]
		);

		if (is_callable($groupActionCallback)) {
			$groupActionCallback($this->lAdmin);
		} else {
			$this->lAdmin->AddGroupActionTable(
				[
					"delete" => GetMessage("acrit_cleanmaster_MAIN_ADMIN_LIST_DELETE")
				]
			);
		}
	}

	/**
	 * @param \CAdminListRow   $row
	 * @param array $arRes
	 * @param bool $bMayDelete = true
	 */
	protected function setRowActions(&$row, $arRes, $bMayDelete = true)
	{
		$arActions = array();
		if ($bMayDelete) {
			/*$arActions[] = array(
				"ICON" => "edit",
				"DEFAULT" => true,
				"TEXT" => Loc::getMessage("acrit_cleanmaster_MAIN_EDIT"),
				//"ACTION" => $this->lAdmin->ActionRedirect("perfmon_row_edit.php?lang=" . LANGUAGE_ID . "&table_name=" . urlencode($table_name) . "&" . implode("&", $arRowPK)),
			);*/
			$arActions[] = array(
				"ICON" => "delete",
				"DEFAULT" => false,
				"TEXT" => Loc::getMessage("acrit_cleanmaster_MAIN_DELETE"),
				"ACTION" => $this->lAdmin->ActionDoGroup($arRes["ID"], "delete", 'profile_table=Y&lang=ru&mid=acrit.cleanmaster&mid_menu=1'),
			);
		}

		if (count($arActions)) {
			$row->AddActions($arActions);
		}
	}

	public function addAdminContextMenuAndCheckXls($aContext = array())
	{
		if (count($aContext) <= 0) {
			$aContext = array();
			/*$aContext[] = array(
				"TEXT" => GetMessage("acrit_cleanmaster_MAIN_ADD"),
				"LINK" => "/bitrix/admin/perfmon_row_edit.php?lang=".LANGUAGE_ID."&table_name=".urlencode($table_name),
				"ICON" => "btn_new",
			);*/
		}

		$this->lAdmin->AddAdminContextMenu($aContext);
		$this->lAdmin->CheckListMode();
	}

	public function displayList()
	{
		/*$filter = new \CAdminFilter($this->sTableID);
		$filter->Begin();
		$filter->Buttons();
		$filter->End();*/

		$this->lAdmin->DisplayList();
	}

	/**
	 * @param \Bitrix\Main\Entity\DataManager $ormDataClass
	 */
	public function postGroupActions($ormDataClass)
	{
		if ($_REQUEST['action_target'] == 'selected'
			&& in_array('delete', (array)$_REQUEST['action_button'])
		) {
			$ormDataClass::clearTable();
			return;
		}


		$arID = $this->lAdmin->GroupAction();
		if (! is_array($arID)) {
			$arID = [];
		}
		$arID = array_filter($arID);
		if (count($arID) <= 0) {
			return;
		}
		foreach ($arID as $ID) {
			if ((int)$ID <= 0) {
				continue;
			}
			$ID = (int)$ID;
			switch ($_REQUEST['action']) {
				case "delete": {
					$ormDataClass::delete($ID);
					break;
				}
			}
		}

	}
}

?>