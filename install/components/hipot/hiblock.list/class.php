<?php
/**
 * hipot studio source file
 * User: <info AT hipot-studio.com>
 */
/** @noinspection AutoloadingIssuesInspection */
namespace Hipot\Components;

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Highloadblock\HighloadBlockTable,
	Bitrix\Main\Loader,
	Hipot\Utils\UUtils;

use function ShowError;

/**
 * Уникальный компонент списка из Hl-блока.
 * Концептуально параметры похожи на параметры iblock.list, см. справку и по нему.
 *
 * <code>
 * HLBLOCK_ID int  OR:
 * HLBLOCK_CODE string
 *
 * ORDER DEF: ["ID" => "DESC"]
 * SELECT DEF: [ID, *]
 * FILTER
 * PAGESIZE DEF:10 or
 * NTOPCOUNT (set both PAGESIZE and NTOPCOUNT has not sense)
 * GROUP_BY
 * NAV_SHOW_ALWAYS = Y/N DEF: N
 * NAV_TITLE
 * NAV_TEMPLATE
 * SET_404 = Y/N DEF: N
 * ALWAYS_INCLUDE_TEMPLATE = Y/N DEF: N
 * SET_CACHE_KEYS = []
 * CACHE_TIME (default component cache)
 * CACHE_TIME_ORM = 0 (use orm-getList cache)
 *
 * Пример вызова:
 * // new class extends Hipot\Components\HiblockList {};
 * $result = $APPLICATION->IncludeComponent('hipot:hiblock.list', '', [
 * 'HLBLOCK_ID'   => 0, // or
 * 'HLBLOCK_CODE' => '',
 * 'ORDER'        => ["ID" => "DESC"],
 * 'SELECT'       => ['ID', 'UF_*'],
 * 'FILTER'       => [],
 * 'GROUP_BY'     => [],
 *
 * 'PAGESIZE'                => 10, // or
 * 'NTOPCOUNT'               => 10, // (set both PAGESIZE and NTOPCOUNT has not sensed)
 * 'NAV_SHOW_ALWAYS'         => 'N',
 * 'NAV_TITLE'               => '',
 * 'NAV_TEMPLATE'            => '.default',
 * 'SET_404'                 => 'N',
 * 'ALWAYS_INCLUDE_TEMPLATE' => 'Y',
 *
 * 'SET_CACHE_KEYS'     => [],
 * 'CACHE_TIME'         => 3600, // or
 * 'CACHE_TIME_ORM'     => 3600, // (use orm-getList cache)
 *
 *  // CUSTOM:
 * 'VERSION'            => '2.0',
 * 'IS_BETA_TESTER'     => IS_BETA_TESTER,
 * 'IS_CONTENT_MANAGER' => IS_CONTENT_MANAGER,
 * 'IS_AJAX'            => defined('IS_AJAX') && IS_AJAX ? 'Y' : 'N',
 * ]);
 * </code>
 */
class HiblockList extends \CBitrixComponent
{
	public const CACHE_TTL = 3600 * 24;
	
	/**
	 * @var string|null
	 */
	private ?string $entity_class;
	
	/**
	 * @param $arParams
	 * @return array|void
	 */
	public function onPrepareComponentParams($arParams)
	{
		\CPageOption::SetOptionString("main", "nav_page_in_session", "N");
		
		$arParams['PAGEN_1']			    = (int)$_REQUEST['PAGEN_1'];
		$arParams['SHOWALL_1']			    = (int)$_REQUEST['SHOWALL_1'];
		$arParams['NAV_TEMPLATE']		    = (trim($arParams['NAV_TEMPLATE']) != '') ? $arParams['NAV_TEMPLATE'] : '';
		$arParams['NAV_SHOW_ALWAYS']	    = (trim($arParams['NAV_SHOW_ALWAYS']) == 'Y') ? 'Y' : 'N';
		$arParams['CACHE_TIME_ORM']         = (int)$arParams['CACHE_TIME_ORM'];
		
		return $arParams;
	}
	
	public function executeComponent()
	{
		global $USER_FIELD_MANAGER;
		
		// simplifier )
		$arParams     = &$this->arParams;
		$arResult     = &$this->arResult;
		$entity_class = &$this->entity_class;
		
		$requiredModules = ['highloadblock', 'iblock'];
		foreach ($requiredModules as $requiredModule) {
			if (! Loader::includeModule($requiredModule)) {
				ShowError($requiredModule . " not inslaled and required!");
				return false;
			}
		}
		if ($this->startResultCache(false)) {
			// hlblock info
			$hlblock_id     = $arParams['HLBLOCK_ID'];
			$hlblock_code   = $arParams['HLBLOCK_CODE'];
			
			if (is_numeric($hlblock_id)) {
				$hlblock    = HighloadBlockTable::getByPrimary($hlblock_id, ['cache' => ["ttl" => self::CACHE_TTL, "cache_joins" => true]])->fetch();
			} else if (trim($hlblock_code) != '') {
				$hlblock	= HighloadBlockTable::getList([
					'filter'    => ['NAME' => $hlblock_code],
					'cache'     => ["ttl" => self::CACHE_TTL, "cache_joins" => true]
				])->fetch();
			} else {
				ShowError('cant init HL-block');
				$this->abortResultCache();
				return false;
			}
			
			$obEntity = HighloadBlockTable::compileEntity( $hlblock );
			$entity_class = $obEntity->getDataClass();
			
			if (! class_exists($entity_class)) {
				if ($arParams["SET_404"] == "Y") {
					UUtils::setStatusNotFound(true);
				}
				ShowError('404 HighloadBlock not found');
				return false;
			}
			
			// region parameters
			// sort
			if ($arParams["ORDER"]) {
				$arOrder = $arParams["ORDER"];
			} else {
				$arOrder = ["ID" => "DESC"];
			}
			
			// limit
			$limit = [
				'iNumPage' => is_set($arParams['PAGEN_1']) ? $arParams['PAGEN_1'] : 1,
				'bShowAll' => $arParams['NAV_SHOW_ALL'] == 'Y'
			];
			if ((int)$arParams["NTOPCOUNT"] > 0) {
				$limit['nPageTop'] = (int)$arParams["NTOPCOUNT"];
			}
			if ((int)$arParams["PAGESIZE"] > 0) {
				$limit['nPageSize'] = (int)$arParams["PAGESIZE"];
			}
			
			$arSelect = ["*"];
			if (!empty($arParams["SELECT"])) {
				$arSelect = $arParams["SELECT"];
				$arSelect[] = "ID";
			}
			
			$arFilter = [];
			if (!empty($arParams["FILTER"])) {
				$arFilter = $arParams["FILTER"];
			}
			
			$arGroupBy = [];
			if (!empty($arParams["GROUP_BY"])) {
				$arGroupBy = $arParams["GROUP_BY"];
			}
			// endregion
			
			$result = $entity_class::getList([
				"order"  => $arOrder,
				"select" => $arSelect,
				"filter" => $arFilter,
				"group"  => $arGroupBy,
				"limit"  => ($limit["nPageTop"] > 0) ? $limit["nPageTop"] : 0,
				"cache"  => ["ttl" => $arParams['CACHE_TIME_ORM'], "cache_joins" => true]
			]);
			
			// region pager
			if ($limit["nPageTop"] <= 0) {
				$result = new \CDBResult($result);
				$result->NavStart($limit, false, true);
				
				$arResult["NAV_STRING"] = $result->GetPageNavStringEx(
					$navComponentObject,
					$arParams["NAV_TITLE"],
					$arParams["NAV_TEMPLATE"]
				);
				$arResult["NAV_CACHED_DATA"] = $navComponentObject->GetTemplateCachedData();
				$arResult["NAV_RESULT"] = $result;
			}
			// endregion
			
			// build results
			$arResult["ITEMS"] = [];
			
			// uf info
			$fields = $USER_FIELD_MANAGER->GetUserFields('HLBLOCK_' . $hlblock['ID'], 0, LANGUAGE_ID);
			
			while ($row = $result->Fetch()) {
				foreach ($row as $k => $v) {
					if ($k == "ID") {
						continue;
					}
					$arUserField = $fields[$k];
					
					$html = '';
					/** @see https://dev.1c-bitrix.ru/api_help/iblock/classes/user_properties/GetAdminListViewHTML.php */
					/** @see https://dev.1c-bitrix.ru/api_d7/bitrix/main/userfield/uf-fieldcomponent.php */
					/** @see \Bitrix\Main\UserField\Types\BaseType::getHtml() */
					/** @var \Bitrix\Main\UserField\Types\BaseType $className */
					$className = $arUserField["USER_TYPE"]["CLASS_NAME"];
					if (is_callable([$className, "GetAdminListViewHTML"])) {
						$html = $className::GetAdminListViewHTML(
							$arUserField,
							[
								"NAME"      => "FIELDS[" . $row['ID'] . "][" . $arUserField["FIELD_NAME"] . "]",
								"VALUE"     => htmlspecialcharsbx($v)
							]
						);
					}
					if ($html == '') {
						$html = '&nbsp;';
					}
					
					$row[$k] = $html;
					$row["~" . $k] = $v;
				}
				
				$row['fields'] = $USER_FIELD_MANAGER->getUserFieldsWithReadyData(
					'HLBLOCK_'.$hlblock['ID'],
					$row,
					LANGUAGE_ID
				);
				
				$arResult["ITEMS"][] = $row;
			}
			
			$bHasItems = count($arResult["ITEMS"]) > 0;
			if ($bHasItems) {
				// добавили сохранение ключей по параметру
				$arSetCacheKeys = [];
				if (is_array($arParams['SET_CACHE_KEYS'])) {
					$arSetCacheKeys = $arParams['SET_CACHE_KEYS'];
				}
				$this->setResultCacheKeys($arSetCacheKeys);
			} else {
				if ($arParams["SET_404"] == "Y") {
					UUtils::setStatusNotFound(true);
				}
				$this->abortResultCache();
			}
			
			if ($arParams["ALWAYS_INCLUDE_TEMPLATE"] == "Y" || $bHasItems) {
				$this->includeComponentTemplate();
			}
		}
		
		// IF NEED SOME USE WITH "SET_CACHE_KEYS"-params
		return $arResult;
	}
}