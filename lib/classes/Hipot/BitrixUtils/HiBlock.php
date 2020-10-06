<?
namespace Hipot\BitrixUtils;

use Bitrix\Highloadblock as HL;
use CModule;
use CUserTypeEntity;
use RuntimeException;

CModule::IncludeModule('highloadblock');

/**
 * Удобный класс для работы с HL-инфоблоками
 */
class HiBlock
{
	/**
	 * @internal
	 * @var int
	 */
	public const CACHE_TTL = 3600;

	/**
	 * Получить сущность DataManager HL-инфоблока
	 *
	 * @param int $hlblockId код HL-инфоблока
	 *
	 * @return bool | \Bitrix\Main\Entity\DataManager
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getDataManagerByHiId($hlblockId)
	{
		if ((int)$hlblockId <= 0) {
			return false;
		}

		$hlblock   = HL\HighloadBlockTable::getByPrimary($hlblockId, ['cache' => ["ttl" => self::CACHE_TTL]])->fetch();
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

		$hlblock	= HL\HighloadBlockTable::getList([
			'filter'    => ['NAME' => $hiBlockName],
			'cache'     => ["ttl" => self::CACHE_TTL]
		])->fetch();
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
	 * @return array
	 *
	 * @throws \Exception ERROR - VOID tableName, ERROR - VOID hiBlockName
	 * @uses $DB
	 */
	public static function installCustomSettingsHiBlock($tableName = 'hi_custom_settings', $hiBlockName = 'CustomSettings')
	{
		$tableName		= trim($tableName);
		$hiBlockName	= trim($hiBlockName);

		if ($tableName == '') {
			throw new RuntimeException('ERROR - VOID tableName');
		}

		if ($hiBlockName == '') {
			throw new RuntimeException('ERROR - VOID hiBlockName');
		}

		$result = HL\HighloadBlockTable::add([
			'NAME'			=> $hiBlockName,
			'TABLE_NAME'	=> $tableName
		]);
		$ID_hiBlock = $result->getId();

		if ((int)$ID_hiBlock <= 0) {
			throw new RuntimeException('ERROR - CREATE hiBlockName');
		}

		$arUfFields = [
			[
				'CODE' => 'NAME', 'SORT' => 100, 'NAME' => 'Имя параметра', 'HELP' => '',
				'SETTINGS' => ["SIZE" => 60, "ROWS" => 1], 'REQUIRED' => 'Y'
			],
			[
				'CODE' => 'CODE', 'SORT' => 200,
				'NAME' => 'Код параметра (не менять!)', 'HELP' => 'Используется для идентификации параметра',
				'SETTINGS' => ["SIZE" => 60, "ROWS" => 1], 'REQUIRED' => 'Y'
			],
			[
				'CODE' => 'VALUE', 'SORT' => 300, 'NAME' => 'Значение параметра', 'HELP' => '',
				'SETTINGS' => ["SIZE" => 60, "ROWS" => 3], 'REQUIRED' => 'N'
			],
		];

		$ID_props = [];

		foreach ($arUfFields as $arFields) {
			$obUserField 	= new CUserTypeEntity;
			$ID_props[ $arFields['CODE'] ]	= $obUserField->Add([
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
				"EDIT_FORM_LABEL"	=> ["ru" => $arFields['NAME']],
				"LIST_COLUMN_LABEL" => ["ru" => $arFields['NAME']],
				"LIST_FILTER_LABEL" => ["ru" => $arFields['NAME']],
				"ERROR_MESSAGE"		=> ["ru" => $arFields['NAME']],
				"HELP_MESSAGE"		=> ["ru" => $arFields['HELP']],
			]);
		}

		return $ID_props;
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

		return PhpCacher::cache('hi_custom_settings', $cacheTime, static function() use ($hiBlockName, $self) {
			$arParams = [];

			/* @var $dm \Bitrix\Main\Entity\DataManager */
			$dm		= $self::getDataManagerByHiCode($hiBlockName);
			$res	= $dm::getList([
				'select' 	=> ['UF_CODE', 'UF_VALUE'],
				'order'		=> ['UF_CODE' => 'ASC']
			]);

			while ($ar = $res->fetch()) {
				$arParams[ $ar['UF_CODE'] ] = $ar['UF_VALUE'];
			}
			return $arParams;
		});
	}

	/**
	 * Показать постер по активной дате UF_DATE_FROM до UF_DATE_TO
	 * @param string $hlBlockname = 'SupportPoster'
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	static function ShowPostersHtml($hlBlockname = 'SupportPoster')
	{
		$dm			= __getHl($hlBlockname);
		$arPosters 	= $dm::getList(array(
			'select' 	=> ['*'],
			'filter' 	=> [
				">=UF_DATE_TO" 				=> [date('d.m.Y H:i:s'), false],
				"<=UF_DATE_FROM"			=> [date('d.m.Y H:i:s'), false]
			],
			'order'		=> ['ID' => 'DESC'],
			'limit'		=> 1
		))->fetchAll();

		if (count($arPosters) > 0) {
			echo '<div class="global_alert_message">';
		}
		foreach ($arPosters as $poster) {
			echo $poster['UF_MESSAGE'];
		}
		if (count($arPosters) > 0) {
			echo '</div>';
		}
	}

} // end class


?>