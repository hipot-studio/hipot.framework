<?php
namespace Hipot\IbAbstractLayer\GenerateScheme;

use Bitrix\Main\Entity\Event;
use Bitrix\Main\EventManager;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\EventResult;
use Bitrix\Main\ORM\Data\DataManager;

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

	// region hiloadblock
	/**
	 * событие очистки, нужен обработчик на CustomSettingsOnAfterUpdate, CustomSettingsOnAfterAdd, CustomSettingsOnAfterDelete
	 *
	 * @param \Bitrix\Main\Entity\Event $event
	 */
	public static function OnAfterHighloadBlockUpdateHandler(Event $event): void
	{
		foreach ($event->getResults() as $eventResult) {
			if ($eventResult->getType() !== EventResult::SUCCESS) {
				return;
			}
		}

		self::deleteScheme(self::getLastScheme());
	}
	public static function OnAfterHighloadBlockAddHandler(Event $event): void
	{
		foreach ($event->getResults() as $eventResult) {
			if ($eventResult->getType() !== EventResult::SUCCESS) {
				return;
			}
		}

		self::deleteScheme(self::getLastScheme());
	}
	public static function OnAfterHighloadBlockDeleteHandler(Event $event): void
	{
		foreach ($event->getResults() as $eventResult) {
			if ($eventResult->getType() !== EventResult::SUCCESS) {
				return;
			}
		}

		self::deleteScheme(self::getLastScheme());
	}
	// endregion

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
			],
			'highloadblock' => [
				'\\' . HighloadBlockTable::class . '::' . DataManager::EVENT_ON_AFTER_UPDATE => 'OnAfterHighloadBlockUpdate',
				'\\' . HighloadBlockTable::class . '::' . DataManager::EVENT_ON_AFTER_ADD    => 'OnAfterHighloadBlockAdd',
				'\\' . HighloadBlockTable::class . '::' . DataManager::EVENT_ON_AFTER_DELETE => 'OnAfterHighloadBlockDelete',
			]
		];
		foreach ($arEvents as $module => $events) {
			foreach ($events as $eKey => $e) {
				if (is_callable([__CLASS__, $e . 'Handler'])) {
					EventManager::getInstance()->addEventHandler(
						$module,
						!is_numeric($eKey) ? str_replace('Table', '', $eKey) : $e,
						[__CLASS__, $e . 'Handler']
					);
				}
			}
		}
	}
}
