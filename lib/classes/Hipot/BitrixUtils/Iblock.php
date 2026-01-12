<?php
namespace Hipot\BitrixUtils;

use Hipot\BitrixUtils\Iblock\Element;
use Hipot\BitrixUtils\Iblock\Section;
use Hipot\Services\BitrixEngine;
use Hipot\Model\EntityHelper;

use	Bitrix\Main\Loader,
	Bitrix\Iblock\PropertyIndex\Manager,
	CIblock,
	_CIBElement;

Loader::includeModule('iblock');

/**
 * Супер-объект дополнительных утилит для работы с инфоблоками
 *
 * IMPORTANT:
 * Некоторые методы выборки избыточны (лучше использовать bitrix api).
 * В основном необходимы для построения быстрых решений: к примеру, отчетов.
 *
 * @version 3.X
 */
class Iblock extends _CIBElement
{
	use Element;

	use Section;

	// region /*********************** cache and tag cache performance tweaks **************************/

	/**
	 * Отключает сброс тэгированного кэша инфоблока
	 * @return bool
	 */
	public static function disableIblockCacheClear(): bool
	{
		while (CIblock::isEnabledClearTagCache()) {
			CIblock::disableClearTagCache();
		}
		return true;
	}

	/**
	 * Включает сброс тэгированного кэша инфоблока
	 * @return bool
	 */
	public static function enableIblockCacheClear(): bool
	{
		while (!CIblock::isEnabledClearTagCache()) {
			CIblock::enableClearTagCache();
		}
		return true;
	}

	/**
	 * run update sets of elements between startMultipleElemsUpdate() and endMultipleElemsUpdate()
	 * @param int $iblockId
	 * @return void
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function startMultipleElemsUpdate(int $iblockId): void
	{
		Manager::enableDeferredIndexing();
		if (Loader::includeModule('catalog')) {
			\Bitrix\Catalog\Product\Sku::enableDeferredCalculation();
		}
		\CIBlock::disableTagCache($iblockId);
	}

	/**
	 * run update sets of elements between startMultipleElemsUpdate() and endMultipleElemsUpdate()
	 * @param int $iblockId
	 * @return void
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function endMultipleElemsUpdate(int $iblockId): void
	{
		\CIBlock::enableTagCache($iblockId);
		\CIBlock::clearIblockTagCache($iblockId);

		if (Loader::includeModule('catalog')) {
			\Bitrix\Catalog\Product\Sku::disableDeferredCalculation();
			\Bitrix\Catalog\Product\Sku::calculate();
		}

		Manager::disableDeferredIndexing();
		Manager::runDeferredIndexing($iblockId);
	}

	// endregion

	/**
	 * Проверить наличие элемента/секции с именем или симв. кодом $field в инфоблоке $iblockId.
	 *
	 * @param string $field
	 * @param int    $iblockId
	 * @param string $fieldType
	 * @param string $table = b_iblock_element | b_iblock_section
	 *
	 * @return boolean | int возвращает ID найденного элемента
	 */
	public static function checkExistsByNameOrCode(string $field, int $iblockId, string $fieldType = 'name', string $table = 'b_iblock_element'): bool|int
	{
		$fieldType = strtolower($fieldType);

		$iblockFieldsBase = ['code', 'xml_id', 'name', 'preview_text'];

		$fields = EntityHelper::getTableFields($table);
		foreach ($fields as &$f) {
			$f = strtolower($f);
			$iblockFields[] = $f;
		}
		unset($f);

		/** @noinspection TypeUnsafeArraySearchInspection */
		if ($iblockId == 0 || trim($field) == ''
			|| !in_array(trim($fieldType, '?=%!<> '), $iblockFields)
			|| !in_array(strtolower($table), ['b_iblock_element', 'b_iblock_section', 'b_iblock_property'])
		) {
			return false;
		}

		$sqlHelper = BitrixEngine::getInstance()->connection->getSqlHelper();

		if ($fieldType == 'code') {
			$fw = 'CODE = "' . $sqlHelper->forSql($field) . '"';
		} else if ($fieldType == 'xml_id') {
			$fw = 'XML_ID = "' . $sqlHelper->forSql($field) . '"';

		} else if (! in_array($fieldType, $iblockFieldsBase)) {
			$fw = $fieldType . ' = "' . $sqlHelper->forSql($field) . '"';
		} else {
			$fw = 'NAME = "' . $sqlHelper->forSql($field) . '"';
		}

		/** @noinspection SqlNoDataSourceInspection */
		/** @noinspection SqlResolve */
		$sqlCheck = 'SELECT ID FROM ' . $table . ' WHERE ' . $fw . ' AND IBLOCK_ID = ' . $iblockId;
		$el = BitrixEngine::getInstance()->connection->query($sqlCheck)->fetch();

		if ((int)$el['ID'] > 0) {
			return (int)$el['ID'];
		}

		return false;
	}

	/**
	 * Получить массив ids элементов инфоблока $iblockId по запросу $query
	 *
	 * @param string $query строка запроса
	 * @param int    $iblockId код инфоблока
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function queryIblockItemsSearch(string $query, $iblockId): array
	{
		$r = [];
		if (!Loader::includeModule('search')) {
			return $r;
		}

		// TODO Can't group on 'RANK' (400) bitrix
		$query = str_replace(['"', '\''], '', $query);
		$query = trim($query);

		$obSearch = new \CSearch();
		$obSearch->Search([
			'QUERY'         => $query,
			//'SITE_ID'     => SITE_ID,
			'MODULE_ID'     => 'iblock',
		], ["RANK" => "DESC"], [
			"=MODULE_ID" => "iblock",
			"!ITEM_ID" => "S%",
			"=PARAM2" => [(int)$iblockId],
		]);
		while ($arResult = $obSearch->GetNext()) {
			$r[] = $arResult['ITEM_ID'];
		}
		return $r;
	}

} // end class

