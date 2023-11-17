<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 16.11.2023 23:07
 * @version pre 1.0
 */

namespace Hipot\Utils;

/**
 * Trait UserFieldUtils
 *
 * This trait provides utility functions for manipulating user fields in Bitrix.
 */
trait UserFieldUtils
{
	public static $arDefaultUserFieldTypes = [
		'string', // строка;
		'string_formatted', // Шаблон
		'integer', // целое число;
		'double', // число;
		'date', // дата;
		'datetime', // дата со временем;
		'boolean', // Да / Нет;
		'file', // файл;
		'enumeration', //список;
		'url', // ссылка;
		'address', // адрес;
		'video', // Видео
		'iblock_section', // раздел инфоблока;
		'iblock_element', // элемент инфоблока;
		'hlblock', // привязка к HL-блоку
		'employee', // сотрудник;
		'crm', // элемент CRM;
		'crm_status', // привязка к справочнику CRM.
		'crm', // Привязка к элементам CRM
		'crm_status' // Привязка к справочникам CRM
	];

	/**
	 * @param array $filter = []
	 * @param array $getList = []
	 * @return array{array{'ID':int, 'FIELD_NAME':string, 'ENTITY_ID':string, 'USER_TYPE_ID':string, 'MULTIPLE':string, 'MAIN_USER_FIELD_TITLE_EDIT_FORM_LABEL':string}}
	 */
	public static function getUserFieldList(array $filter = [], array $getList = []): array
	{
		$list = [];
		$userFields = \Bitrix\Main\UserFieldTable::getList(array_merge([
			'select'   =>   ['ID', 'FIELD_NAME', 'ENTITY_ID', 'USER_TYPE_ID', 'MULTIPLE', 'SETTINGS', 'TITLE'],
			'filter'   =>   array_merge([
				'=MAIN_USER_FIELD_TITLE_LANGUAGE_ID'   =>   LANGUAGE_ID
			], $filter),
			'runtime'   =>   [
				'TITLE'         =>   [
					'data_type'      =>   \Bitrix\Main\UserFieldLangTable::getEntity(),
					'reference'      =>   [
						'=this.ID'      =>   'ref.USER_FIELD_ID',
					],
				],
			],
		], $getList));

		while ($arUserField = $userFields->fetch()) {
			$list[] = $arUserField;
		}
		return $list;
	}

	/**
	 * @param string $entityId
	 * @param string $fieldName
	 * @param string|array|mixed $value
	 * @return float|int|float[]|int[]|array
	 */
	public static function normalizeUfFieldValue(string $entityId, string $fieldName, $value)
	{
		// select structure of uf-props
		static $entityIdPropList = [];
		if (empty($entityIdPropList)) {
			$list = self::getUserFieldList([
				'=ENTITY_ID' => $entityId
			]);
			foreach ($list as $prop) {
				if ($prop['USER_TYPE_ID'] == 'enumeration') {
					$prop['VALUES'] = [];
					$rs = \CUserFieldEnum::GetList(['VALUE' => 'ASC'], ['USER_FIELD_ID' => $prop['ID']]);
					while ($enum = $rs->Fetch()) {
						$prop['VALUES'][ $enum['ID'] ] = $enum['VALUE'];
					}
				}
				$entityIdPropList[ $prop['ENTITY_ID'] ][ $prop['FIELD_NAME'] ] = $prop;
			}
		}

		$propSettings = $entityIdPropList[$entityId][$fieldName] ?? null;
		if ($propSettings === null) {
			return $value;
		}

		// modification raw-value relay to prop type
		$modificator = null;
		switch ($propSettings['USER_TYPE_ID']) {
			case 'integer':
				$modificator = static function ($val) {
					return (int)str_replace([' '], [''], (string)$val);
				};
				break;
			case 'double':
				$modificator = static function ($val) {
					return (float)str_replace([' ', ','], ['', '.'], (string)$val);
				};
				break;
			case 'file':
				$modificator = static function ($val) {
					return \CFile::MakeFileArray($val);
				};
				break;
			case 'enumeration':
				$modificator = static function ($val) use ($propSettings, &$entityIdPropList) {
					if (!in_array($val, $propSettings['VALUES'], false)) {
						$obEnum = new \CUserFieldEnum();
						$obEnum->SetEnumValues($propSettings['ID'], [
							'n0' => [
								'VALUE' => $val,
								'SORT'  => count($propSettings['VALUES']) * 100 + 100
							]
						]);

						// reselect enum items
						$propSettings['VALUES'] = [];
						$rs = \CUserFieldEnum::GetList(['VALUE' => 'ASC'], ['USER_FIELD_ID' => $propSettings['ID']]);
						while ($enum = $rs->Fetch()) {
							$propSettings['VALUES'][ $enum['ID'] ] = $enum['VALUE'];
						}

						$etalonProp =& $entityIdPropList[ $propSettings['ENTITY_ID'] ][ $propSettings['FIELD_NAME'] ];
						$etalonProp['VALUES'] = $propSettings['VALUES'];
					}
					return array_search($val, $propSettings['VALUES'], false);
				};
				break;
			default:            // @todo in this case has more field-types
				break;
		}

		if (is_callable($modificator)) {
			if (is_array($value)) {
				foreach ($value as &$v) {
					$v = $modificator($v);
				}
				unset($v);
			} else {
				$value = $modificator($value);
			}
		}

		return $value;
	}
}