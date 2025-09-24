<?php
namespace Hipot\IbAbstractLayer\GenerateScheme;

use Bitrix\Main\EventManager;

/**
 * Управленец обновлением схемы
 */
final class IblockGenerateSchemeManager
{
	private static string $fileToGenerate;

	/**
	 * Генератор схемы по классам и подсказки по ним
	 * @return bool
	 */
	public static function updateSchema(string $fileToGenerate): bool
	{
		self::setLastScheme($fileToGenerate);
		self::deleteScheme(self::getLastScheme());
		return (new IblockGenerateScheme(self::getLastScheme()))->generate();
	}

	/**
	 * Удаление схемы
	 * @return bool
	 */
	private static function deleteScheme(string $fileToGenerate): bool
	{
		if (file_exists($fileToGenerate)) {
			return unlink($fileToGenerate);
		}
		return true;
	}

	private static function setLastScheme(string $fileToGenerate): void
	{
		self::$fileToGenerate = $fileToGenerate;
	}
	private static function getLastScheme(): string
	{
		return self::$fileToGenerate;
	}

	/**
	 * Событие добавления инфоблока
	 *
	 * @param array $arFields
	 */
	public static function OnAfterIBlockAddHandler(&$arFields)
	{
		if ($arFields["ID"] > 0) {
			self::deleteScheme(self::getLastScheme());
		}
	}

	/**
	 * Событие обновления инфоблока
	 * @param array $arFields
	 */
	public static function OnAfterIBlockUpdateHandler(&$arFields)
	{
		if ($arFields["RESULT"]) {
			self::deleteScheme(self::getLastScheme());
		}
	}

	/**
	 * Событие в момент удаления инфоблока
	 * @param int $ID
	 */
	public static function OnIBlockDeleteHandler($ID)
	{
		self::deleteScheme(self::getLastScheme());
	}

	/**
	 * событие добавления свойства
	 * @param array $arFields
	 */
	public static function OnAfterIBlockPropertyAddHandler(&$arFields)
	{
		if ($arFields["ID"] > 0) {
			self::deleteScheme(self::getLastScheme());
		}
	}

	/**
	 * событие обновления свойства
	 * @param array $arFields
	 */
	public static function OnAfterIBlockPropertyUpdateHandler(&$arFields)
	{
		if ($arFields["RESULT"]) {
			self::deleteScheme(self::getLastScheme());
		}
	}

	/**
	 * Событие в момент удаления свойства (не отрабатывает?)
	 * @param int $ID
	 */
	public static function OnIBlockPropertyDeleteHandler($ID)
	{
		self::deleteScheme(self::getLastScheme());
	}

	public static function OnAfterUserTypeAddHandler($arFields)
	{
		if ((int)$arFields['ID'] > 0) {
			self::deleteScheme(self::getLastScheme());
		}
	}

	public static function OnAfterUserTypeUpdateHandler($arFields, $ID)
	{
		self::deleteScheme(self::getLastScheme());
	}

	/**
	 * Установка событий обновления схемы
	 */
	public static function setUpdateHandlers($fileToGenerateSxema): void
	{
		self::setLastScheme($fileToGenerateSxema);

		$arEvents = [
			'iblock' => [
				"OnAfterIBlockAdd", "OnAfterIBlockUpdate", "OnIBlockDelete",
				"OnAfterIBlockPropertyAdd", "OnAfterIBlockPropertyUpdate", "OnIBlockPropertyDelete"
			],
			'main' => [
				'OnAfterUserTypeAdd', 'OnAfterUserTypeUpdate'
			]
		];
		foreach ($arEvents as $module => $events) {
			foreach ($events as $e) {
				if (is_callable([__CLASS__, $e . 'Handler'])) {
					EventManager::getInstance()->addEventHandler($module, $e, [__CLASS__, $e . 'Handler']);
				}
			}
		}
	}
}
