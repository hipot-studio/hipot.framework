<?php

namespace Hipot\BitrixUtils;

use Bitrix\Main\Loader,
	CUserTypeEntity;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Main\ORM\Data\AddResult;
use Hipot\Services\DbResultGenerator;

Loader::includeModule('highloadblock');

/**
 * Удобный класс для работы с HL-блоками
 */
class HiBlock
{
	/**
	 * @internal
	 * @var int
	 */
	public const int CACHE_TTL = 3600;

	/**
	 * Получить HighloadBlockTable-строку хл-блока или обряд создания объекта для работы с HL-блоком
	 *
	 * @param int     $hlblockId в приоритете
	 * @param string  $hiBlockName иначе по строке
	 * @param boolean $returnEntityClass = false Обряд создания до $hlblock = [] или до \Bitrix\Main\Entity\DataManager
	 * @return null | array | \Bitrix\Main\Entity\DataManager
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @see \Bitrix\Highloadblock\HighloadBlockTable::resolveHighloadblock()
	 */
	public static function getHightloadBlockTable(int $hlblockId, string $hiBlockName, bool $returnEntityClass = false)
	{
		$hlblock = null;

		if ($hlblockId > 0) {
			$hlblock = HighloadBlockTable::getByPrimary($hlblockId, ['cache' => ["ttl" => self::CACHE_TTL]])->fetch();
		} else if (trim($hiBlockName) != '') {
			$hlblock = HighloadBlockTable::getList([
				'filter' => ['=NAME' => $hiBlockName],
				'cache'  => ["ttl" => self::CACHE_TTL]
			])->fetch();
		}

		if (!$returnEntityClass) {
			return $hlblock;
		}

		$entity = HighloadBlockTable::compileEntity($hlblock);
		return $entity->getDataClass();
	}

	/**
	 * Получить сущность DataManager HL-инфоблока
	 *
	 * @param int|string $hlblockId код HL-инфоблока
	 *
	 * @return bool | \Bitrix\Main\Entity\DataManager
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getDataManagerByHiId($hlblockId)
	{
		if ((int)$hlblockId <= 0) {
			return false;
		}
		return self::getHightloadBlockTable($hlblockId, false, true);
	}

	/**
	 * Получить сущность DataManager HL-инфоблока
	 *
	 * @param string $hiBlockName символьный код HL-инфоблока
	 *
	 * @return bool | \Bitrix\Main\Entity\DataManager
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getDataManagerByHiCode(string $hiBlockName)
	{
		if (trim($hiBlockName) == '') {
			return false;
		}
		return self::getHightloadBlockTable(0, $hiBlockName, true);
	}

	/**
	 * Добавляем hl-блок в систему
	 *
	 * @param string $hiBlockName
	 * @param string $tableName
	 * @param array  $addField = []
	 *
	 * @return AddResult
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function addHiBlock(string $hiBlockName, string $tableName, array $addField = []): AddResult
	{
		$flds = [
			'NAME'       => $hiBlockName,
			'TABLE_NAME' => $tableName
		];
		foreach ($addField as $f => $v) {
			$flds[$f] = $v;
		}
		return HighloadBlockTable::add($flds);
	}

	/**
	 * Добавить поле в таблицу в HL-блоком, пок поддерживаются только одиночные строки
	 *
	 * @param array $arFields = [<pre>
	 * HLBLOCK_ID
	 * CODE (без UF_ в начале)
	 * SORT
	 * REQUIRED = Y/N
	 * IS_SEARCHABLE = Y/N
	 * SETTINGS = ["SIZE" => 60, "ROWS" => 3]
	 * NAME = DEF: CODE
	 * HELP</pre>]
	 *
	 * @return false|int код добавленного свойства
	 */
	public static function addHiBlockField($arFields)
	{
		if (!isset($arFields['SETTINGS'])) {
			$arFields['SETTINGS'] = ["SIZE" => 60, "ROWS" => 2];
		}
		if (trim($arFields['NAME']) == '') {
			$arFields['NAME'] = $arFields['CODE'];
		}

		$arFields['TYPE'] = 'string';

		$obUserField = new CUserTypeEntity();
		return $obUserField->Add([
			"ENTITY_ID"         => 'HLBLOCK_' . $arFields['HLBLOCK_ID'],
			"FIELD_NAME"        => 'UF_' . $arFields['CODE'],
			"USER_TYPE_ID"      => $arFields['TYPE'],
			"XML_ID"            => '',
			"SORT"              => $arFields["SORT"],
			"MULTIPLE"          => 'N',
			"MANDATORY"         => $arFields["REQUIRED"],
			"SHOW_FILTER"       => 'Y',
			"SHOW_IN_LIST"      => 'Y',
			"EDIT_IN_LIST"      => 'Y',
			"IS_SEARCHABLE"     => $arFields['IS_SEARCHABLE'],
			"SETTINGS"          => $arFields['SETTINGS'],
			"EDIT_FORM_LABEL"   => [self::getLanguageId() => $arFields['NAME']],
			"LIST_COLUMN_LABEL" => [self::getLanguageId() => $arFields['NAME']],
			"LIST_FILTER_LABEL" => [self::getLanguageId() => $arFields['NAME']],
			"ERROR_MESSAGE"     => [self::getLanguageId() => $arFields['NAME']],
			"HELP_MESSAGE"      => [self::getLanguageId() => $arFields['HELP']],
		]);
	}

	/**
	 * Retrieves a list of highload block entities with optional filtering, selection, property fetching, and caching.
	 *
	 * @param array $filter Optional array of filtering conditions for highload blocks.
	 * @param array $select Optional array of fields to select. Default selects all fields ['*'].
	 * @param bool  $getProps Determines whether to fetch associated properties for each highload block. Default is true.
	 * @param bool  $useCache Indicates whether to use caching for the query. Default is false.
	 *
	 * @return array{array{'ID':int, 'LOC':{array{'NAME'}}, 'PROPERTIES':array{'ID':int, 'FIELD_NAME':string, 'VALUE_LIST':array{'ID':int, 'VALUE':string}}}} Returns an array of
	 *     highload block entities. If $getProps is true, each entity includes its associated properties.
	 */
	public static function getList(array $filter = [], array $select = ['*'], bool $getProps = true, bool $useCache = false): array
	{
		$cache = $useCache ? ["ttl" => self::CACHE_TTL] : [];

		$hlblockList = HighloadBlockTable::getList([
			'order'  => ['ID' => 'ASC'],
			'filter' => $filter,
			'select' => $select,
			'cache'  => $cache,
		])->fetchAll();

		// localization
		$localization = [];
		$res = HighloadBlockLangTable::getList([
			'filter' => ['ID' => array_column($hlblockList, 'ID'), 'LID' => self::getLanguageId()],
			'select' => ['*'],
			'cache'  => $cache,
		]);
		while ($row = $res->fetch()) {
			$localization[ $row['ID'] ] = $row;
		}
		foreach ($hlblockList as &$hlblock) {
			$hlblock['LOC'] = $localization[ $hlblock['ID'] ] ?? ['NAME' => $hlblock['NAME']];
			if ($getProps) {
				$props = [];
				$rs    = \CAllUserTypeEntity::GetList(['SORT' => 'ASC', 'FIELD_NAME' => 'ASC'], ['%ENTITY_ID' => 'HLBLOCK_', 'LANG' => self::getLanguageId()]);
				while ($prop = $rs->Fetch()) {
					if ($prop['USER_TYPE_ID'] === 'enumeration') {
						$prop['VALUE_LIST'] = self::getEnumPropertyValues($prop['ID']);
					}
					$props[$prop['ENTITY_ID']][] = $prop;
				}
				$hlblock['PROPERTIES'] = $props['HLBLOCK_' . $hlblock['ID']] ?? [];
			}
		}
		unset($hlblock, $localization);

		return $hlblockList;
	}

	/**
	 * Получить значения списка для перечислимого свойства пользовательского поля.
	 *
	 * @param int $ufPropertyId Идентификатор пользовательского свойства.
	 * @return array Массив значений перечислимого свойства.
	 */
	public static function getEnumPropertyValues(int $ufPropertyId): array
	{
		$result = \CUserFieldEnum::GetList(['VALUE' => 'ASC'], ['USER_FIELD_ID' => $ufPropertyId]);
		return (new DbResultGenerator($result, false, false))->fetchAll();
	}

	/**
	 * @internal
	 * @return string
	 */
	public static function getLanguageId(): string
	{
		return LANGUAGE_ID;
	}

} // end class


