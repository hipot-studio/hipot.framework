<?php
namespace Hipot\BitrixUtils;

use Bitrix\Main\Loader, CUserTypeEntity;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\ORM\Data\AddResult;

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
	public const CACHE_TTL = 3600;

	/**
	 * Получить HighloadBlockTable-строку хл-блока или обряд создания объекта для работы с HL-блоком
	 *
	 * @param int $hlblockId в приоритете
	 * @param string $hiBlockName иначе по строке
	 * @param boolean $returnEntityClass = false Обряд создания до $hlblock = [] или до \Bitrix\Main\Entity\DataManager (true)
	 *
	 * @return array | false | \Bitrix\Main\Entity\DataManager
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getHightloadBlockTable(int $hlblockId, string $hiBlockName, bool $returnEntityClass = false)
	{
		$hlblock = false;

		if ($hlblockId > 0) {
			$hlblock   = HighloadBlockTable::getByPrimary($hlblockId, ['cache' => ["ttl" => self::CACHE_TTL]])->fetch();
		} else if (trim($hiBlockName) != '') {
			$hlblock	= HighloadBlockTable::getList([
				'filter'    => ['NAME' => $hiBlockName],
				'cache'     => ["ttl" => self::CACHE_TTL]
			])->fetch();
		}

		if (! $returnEntityClass) {
			return $hlblock;
		}

		$entity = HighloadBlockTable::compileEntity( $hlblock );
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
		return self::getHightloadBlockTable(false, $hiBlockName, true);
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
			'NAME'			=> $hiBlockName,
			'TABLE_NAME'	=> $tableName
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
		if (! isset($arFields['SETTINGS'])) {
			$arFields['SETTINGS'] = ["SIZE" => 60, "ROWS" => 2];
		}
		if (trim($arFields['NAME']) == '') {
			$arFields['NAME'] = $arFields['CODE'];
		}

		$arFields['TYPE'] = 'string';

		$obUserField 	= new CUserTypeEntity();
		return $obUserField->Add([
			"ENTITY_ID"			=> 'HLBLOCK_' . $arFields['HLBLOCK_ID'],
			"FIELD_NAME"		=> 'UF_' . $arFields['CODE'],
			"USER_TYPE_ID"		=> $arFields['TYPE'],
			"XML_ID"			=> '',
			"SORT"				=> $arFields["SORT"],
			"MULTIPLE"			=> 'N',
			"MANDATORY"			=> $arFields["REQUIRED"],
			"SHOW_FILTER"		=> 'Y',
			"SHOW_IN_LIST"		=> 'Y',
			"EDIT_IN_LIST"		=> 'Y',
			"IS_SEARCHABLE"		=> $arFields['IS_SEARCHABLE'],
			"SETTINGS"			=> $arFields['SETTINGS'],
			"EDIT_FORM_LABEL"	=> ["ru" => $arFields['NAME']],
			"LIST_COLUMN_LABEL" => ["ru" => $arFields['NAME']],
			"LIST_FILTER_LABEL" => ["ru" => $arFields['NAME']],
			"ERROR_MESSAGE"		=> ["ru" => $arFields['NAME']],
			"HELP_MESSAGE"		=> ["ru" => $arFields['HELP']],
		]);
	}

} // end class


