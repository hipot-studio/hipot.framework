<?php
namespace Hipot\IbAbstractLayer\GenerateSxem;

/**
 * Управленец обновлением схемы
 */
class IblockGenerateSxemManager
{
	/**
	 * Генератор схемы по классам и подсказки по ним
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function updateSxem($fileToGenerateSxema)
	{
		self::deleteSxem();

		$oWeIblockGenerateSxem = new IblockGenerateSxem($fileToGenerateSxema);
		return $oWeIblockGenerateSxem->generate();
	}

	/**
	 * удаление схемы
	 * @return bool
	 */
	public static function deleteSxem()
	{
		global $fileToGenerateSxema;
		if (file_exists($fileToGenerateSxema)) {
			return unlink($fileToGenerateSxema);
		}
		return true;
	}

	/**
	 * Событие добавления инфоблока
	 * @param array $arFields
	 */
	public static function OnAfterIBlockAddHandler(&$arFields)
	{
		if ($arFields["ID"] > 0) {
			self::deleteSxem();
		}
	}

	/**
	 * Событие обновления инфоблока
	 * @param array $arFields
	 */
	public static function OnAfterIBlockUpdateHandler(&$arFields)
	{
		if ($arFields["RESULT"]) {
			self::deleteSxem();
		}
	}

	/**
	 * Событие в момент удаления инфоблока
	 * @param int $ID
	 */
	public static function OnIBlockDeleteHandler($ID)
	{
		self::deleteSxem();
	}

	/**
	 * событие добавления свойства
	 * @param array $arFields
	 */
	public static function OnAfterIBlockPropertyAddHandler(&$arFields)
	{
		if ($arFields["ID"] > 0) {
			self::deleteSxem();
		}
	}

	/**
	 * событие обновления свойства
	 * @param array $arFields
	 */
	public static function OnAfterIBlockPropertyUpdateHandler(&$arFields)
	{
		if ($arFields["RESULT"]) {
			self::deleteSxem();
		}
	}

	/**
	 * Событие в момент удаления свойства (не отрабатывает?)
	 * @param int $ID
	 */
	public static function OnIBlockPropertyDeleteHandler($ID)
	{
		self::deleteSxem();
	}


	/**
	 * Установка событий обновления схемы
	 */
	public static function setUpdateHandlers()
	{
		$events = [
			"OnAfterIBlockAdd", "OnAfterIBlockUpdate", "OnIBlockDelete",
			"OnAfterIBlockPropertyAdd", "OnAfterIBlockPropertyUpdate", "OnIBlockPropertyDelete"
		];
		foreach ($events as $e) {
			if (is_callable([__CLASS__, $e . 'Handler'])) {
				AddEventHandler("iblock", $e, [__CLASS__, $e . 'Handler']);
			}
		}
	}
}
