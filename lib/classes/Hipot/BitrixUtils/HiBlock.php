<?
namespace Hipot\BitrixUtils;

use Bitrix\Highloadblock as HL;

/**
 * Удобный класс для работы с HL-инфоблоками
 */
class HiBlock
{
	/**
	 * Получить сущность DataManager HL-инфоблока
	 *
	 * @param int $hlblockId код HL-инфоблока
	 *
	 * @return \Bitrix\Main\Entity\DataManager
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getDataManagerByHiId($hlblockId)
	{
		if ((int)$hlblockId <= 0) {
			return false;
		}

		\CModule::IncludeModule('highloadblock');

		$hlblock   = HL\HighloadBlockTable::getById( $hlblockId )->fetch();
		$entity    = HL\HighloadBlockTable::compileEntity( $hlblock );

		/* @var $entity_data_class \Bitrix\Main\Entity\DataManager */
		$entity_data_class = $entity->getDataClass();

		return $entity_data_class;
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
	public static function getDataManagerByHiCode($hiBlockName)
	{
		if (trim($hiBlockName) == '') {
			return false;
		}

		\CModule::IncludeModule('highloadblock');

		$hlblock	= HL\HighloadBlockTable::getList( array('filter' => array('NAME' => $hiBlockName)) )->fetch();
		$entity   	= HL\HighloadBlockTable::compileEntity( $hlblock );

		/* @var $entity_data_class \Bitrix\Main\Entity\DataManager */
		$entity_data_class = $entity->getDataClass();

		return $entity_data_class;
	}

	/**
	 * Установить таблицу и HL-блок с произвольными настройками сайта
	 *
	 * @param string $tableName = 'we_custom_settings'
	 * @param string $hiBlockName = 'CustomSettings'
	 * @return boolean
	 *
	 * @throws \Exception ERROR - VOID tableName, ERROR - VOID hiBlockName
	 * @uses $DB
	 */
	public static function installCustomSettingsHiBlock($tableName = 'hi_custom_settings', $hiBlockName = 'CustomSettings')
	{
		\CModule::IncludeModule('highloadblock');

		$tableName		= trim($tableName);
		$hiBlockName	= trim($hiBlockName);

		if ($tableName == '') {
			throw new \Exception('ERROR - VOID tableName');
		}

		if ($hiBlockName == '') {
			throw new \Exception('ERROR - VOID hiBlockName');
		}

		$result = HL\HighloadBlockTable::add(array(
			'NAME'			=> $hiBlockName,
			'TABLE_NAME'	=> $tableName
		));
		$ID_hiBlock = $result->getId();

		if ((int)$ID_hiBlock <= 0) {
			throw new \Exception('ERROR - CREATE hiBlockName');
		}

		$arUfFields = array(
			array(
				'CODE' => 'NAME', 'SORT' => 100, 'NAME' => 'Имя параметра', 'HELP' => '',
				'SETTINGS' => array("SIZE" => 60, "ROWS" => 1), 'REQUIRED' => 'Y'
			),
			array(
				'CODE' => 'CODE', 'SORT' => 200,
				'NAME' => 'Код параметра (не менять!)', 'HELP' => 'Используется для идентификации параметра',
				'SETTINGS' => array("SIZE" => 60, "ROWS" => 1), 'REQUIRED' => 'Y'
			),
			array(
				'CODE' => 'VALUE', 'SORT' => 300, 'NAME' => 'Значение параметра', 'HELP' => '',
				'SETTINGS' => array("SIZE" => 60, "ROWS" => 3), 'REQUIRED' => 'N'
			),
		);

		$ID_props = array();

		foreach ($arUfFields as $arFields) {
			$obUserField 	= new \CUserTypeEntity;
			$ID_props[ $arFields['CODE'] ]	= $obUserField->Add(array(
				"ENTITY_ID"			=> 'HLBLOCK_' . $ID_hiBlock,
				"FIELD_NAME"		=> 'UF_' . $arFields['CODE'],
				"USER_TYPE_ID"		=> 'string',
				"XML_ID"			=> '',
				"SORT"				=> $arFields["SORT"],
				"MULTIPLE"			=> 'N',
				"MANDATORY"			=> $arFields["REQUIRED"],
				"SHOW_FILTER"		=> 'Y',
				"SHOW_IN_LIST"		=> 'Y',
				"EDIT_IN_LIST"		=> 'Y',
				"IS_SEARCHABLE"		=> 'N',
				"SETTINGS"			=> $arFields['SETTINGS'],
				"EDIT_FORM_LABEL"	=> array("ru" => $arFields['NAME']),
				"LIST_COLUMN_LABEL" => array("ru" => $arFields['NAME']),
				"LIST_FILTER_LABEL" => array("ru" => $arFields['NAME']),
				"ERROR_MESSAGE"		=> array("ru" => $arFields['NAME']),
				"HELP_MESSAGE"		=> array("ru" => $arFields['HELP']),
			));
		}

		return true;
	}

	/**
	 * Получить список произвольных настроек
	 *
	 * @param string $hiBlockName = 'CustomSettings'
	 * @param int    $cacheTime = 3600 время, на которое закешировать данные
	 *
	 * @return boolean | array (CODE => VALUE)
	 *
	 * @uses \Hipot\BitrixUtils\PhpCacher
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getCustomSettingsList($hiBlockName = 'CustomSettings', $cacheTime = 3600)
	{
		$self = __CLASS__;

		$arParams = PhpCacher::returnCacheDataAndSave('hi_custom_settings', $cacheTime, function() use ($hiBlockName, $self) {
			$arParams = array();

			/* @var $dm \Bitrix\Main\Entity\DataManager */
			$dm		= $self::getDataManagerByHiCode($hiBlockName);
			$res	= $dm::getList(array(
				'select' 	=> array('UF_CODE', 'UF_VALUE'),
				'order'		=> array('UF_CODE' => 'ASC')
			));

			while ($ar = $res->fetch()) {
				$arParams[ $ar['UF_CODE'] ] = $ar['UF_VALUE'];
			}
			return $arParams;
		});

		return $arParams;
	}

} // end class


?>