<?php
namespace Hipot\IbAbstractLayer\GenerateSxem;

use Bitrix\Main\EventManager;

/**
 * Управленец обновлением схемы
 */
final class IblockGenerateSxemManager
{
	private static string $fileToGenerateSxema;

	/**
	 * Генератор схемы по классам и подсказки по ним
	 * @return bool
	 */
	public static function updateSchema(string $fileToGenerateSxema): bool
	{
		self::setLastSxem($fileToGenerateSxema);
		self::deleteSxem(self::getLastSxem());
		return (new IblockGenerateSxem(self::getLastSxem()))->generate();
	}

	/**
	 * удаление схемы
	 * @return bool
	 */
	private static function deleteSxem(string $fileToGenerateSxema): bool
	{
		if (file_exists($fileToGenerateSxema)) {
			return unlink($fileToGenerateSxema);
		}
		return true;
	}

	private static function setLastSxem(string $fileToGenerateSxema): void
	{
		self::$fileToGenerateSxema = $fileToGenerateSxema;
	}
	private static function getLastSxem(): string
	{
		return self::$fileToGenerateSxema;
	}

	/**
	 * Событие добавления инфоблока
	 *
	 * @param array $arFields
	 */
	public static function OnAfterIBlockAddHandler(&$arFields)
	{
		if ($arFields["ID"] > 0) {
			self::deleteSxem(self::getLastSxem());
		}
	}

	/**
	 * Событие обновления инфоблока
	 * @param array $arFields
	 */
	public static function OnAfterIBlockUpdateHandler(&$arFields)
	{
		if ($arFields["RESULT"]) {
			self::deleteSxem(self::getLastSxem());
		}
	}

	/**
	 * Событие в момент удаления инфоблока
	 * @param int $ID
	 */
	public static function OnIBlockDeleteHandler($ID)
	{
		self::deleteSxem(self::getLastSxem());
	}

	/**
	 * событие добавления свойства
	 * @param array $arFields
	 */
	public static function OnAfterIBlockPropertyAddHandler(&$arFields)
	{
		if ($arFields["ID"] > 0) {
			self::deleteSxem(self::getLastSxem());
		}
	}

	/**
	 * событие обновления свойства
	 * @param array $arFields
	 */
	public static function OnAfterIBlockPropertyUpdateHandler(&$arFields)
	{
		if ($arFields["RESULT"]) {
			self::deleteSxem(self::getLastSxem());
		}
	}

	/**
	 * Событие в момент удаления свойства (не отрабатывает?)
	 * @param int $ID
	 */
	public static function OnIBlockPropertyDeleteHandler($ID)
	{
		self::deleteSxem(self::getLastSxem());
	}

	/**
	 * Установка событий обновления схемы
	 */
	public static function setUpdateHandlers($fileToGenerateSxema): void
	{
		self::setLastSxem($fileToGenerateSxema);

		$events = [
			"OnAfterIBlockAdd", "OnAfterIBlockUpdate", "OnIBlockDelete",
			"OnAfterIBlockPropertyAdd", "OnAfterIBlockPropertyUpdate", "OnIBlockPropertyDelete"
		];
		foreach ($events as $e) {
			if (is_callable([__CLASS__, $e . 'Handler'])) {
				EventManager::getInstance()->addEventHandler("iblock", $e, [__CLASS__, $e . 'Handler']);
			}
		}
	}
}
