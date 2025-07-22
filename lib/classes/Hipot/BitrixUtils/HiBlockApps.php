<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 14.04.2021 12:19
 * @version pre 1.0
 */

namespace Hipot\BitrixUtils;

use Bitrix\Main\Entity\Event;
use Bitrix\Main\Composite\Data\MemcachedStorage;
use Bitrix\Main\Composite\Page as CompositePage;
use RuntimeException;


/**
 * Реализованный различный функционал и интерфейсы (визуалки) на hi-блоках
 */
final class HiBlockApps extends HiBlock
{
	/**
	 * тег сохранения настроек через PhpCacher
	 */
	public const string CS_CACHE_TAG = 'hi_custom_settings';
	public const string CS_HIBLOCK_NAME = 'CustomSettings';
	public const string CS_TABLE_NAME = 'hi_custom_settings';

	public const string SUPPORT_POSTER_HIBLOCK_NAME = 'SupportPoster';

	/**
	 * Установить таблицу и HL-блок с произвольными настройками сайта
	 *
	 * @param string $tableName = 'we_custom_settings'
	 * @param string $hiBlockName = 'CustomSettings'
	 *
	 * @return array
	 *
	 * @throws \RuntimeException ERROR - VOID tableName, ERROR - VOID hiBlockName
	 * @uses $DB
	 */
	public static function installCustomSettingsHiBlock(string $tableName = self::CS_TABLE_NAME, string $hiBlockName = self::CS_HIBLOCK_NAME): array
	{
		$tableName   = trim($tableName);
		$hiBlockName = trim($hiBlockName);

		if ($tableName == '') {
			throw new RuntimeException('ERROR - VOID tableName');
		}

		if ($hiBlockName == '') {
			throw new RuntimeException('ERROR - VOID hiBlockName');
		}

		$result     = self::addHiBlock($hiBlockName, $tableName);
		$ID_hiBlock = $result->getId();

		if ((int)$ID_hiBlock <= 0) {
			throw new RuntimeException('ERROR - CREATE hiBlockName');
		}

		$arUfFields = [
			[
				'CODE'     => 'NAME', 'SORT' => 100, 'NAME' => 'Имя параметра', 'HELP' => '',
				'SETTINGS' => ["SIZE" => 60, "ROWS" => 1], 'REQUIRED' => 'Y'
			],
			[
				'CODE'     => 'CODE', 'SORT' => 200,
				'NAME'     => 'Код параметра (не менять!)', 'HELP' => 'Используется для идентификации параметра',
				'SETTINGS' => ["SIZE" => 60, "ROWS" => 1], 'REQUIRED' => 'Y'
			],
			[
				'CODE'     => 'VALUE', 'SORT' => 300, 'NAME' => 'Значение параметра', 'HELP' => '',
				'SETTINGS' => ["SIZE" => 60, "ROWS" => 3], 'REQUIRED' => 'N'
			],
		];

		$ID_props = [];

		foreach ($arUfFields as $arFields) {
			$ID_props[$arFields['CODE']] = self::addHiBlockField([
				'HLBLOCK_ID'    => $ID_hiBlock,
				'CODE'          => $arFields['CODE'],
				'SORT'          => $arFields["SORT"],
				'REQUIRED'      => $arFields["REQUIRED"],
				'IS_SEARCHABLE' => 'N',
				'SETTINGS'      => $arFields['SETTINGS'],
				'NAME'          => $arFields['NAME'],
				'HELP'          => $arFields['HELP']
			]);
		}

		return $ID_props;
	}

	/**
	 * Получить список произвольных настроек
	 *
	 * @param string $hiBlockName = 'CustomSettings'
	 *
	 * @return boolean | array{CODE: 'VALUE'}
	 * @uses \Hipot\BitrixUtils\PhpCacher
	 */
	public static function getCustomSettingsList(string $hiBlockName = self::CS_HIBLOCK_NAME, $cacheTtl = false)
	{
		if ($cacheTtl === false) {
			$cacheTtl = self::CACHE_TTL;
		}
		return PhpCacher::cache(self::CS_CACHE_TAG, $cacheTtl, static function () use ($hiBlockName) {
			$arParams = [];

			/* @var $dm \Bitrix\Main\Entity\DataManager */
			$dm  = self::getDataManagerByHiCode($hiBlockName);
			$res = $dm::getList([
				'select' => ['UF_CODE', 'UF_VALUE'],
				'order'  => ['UF_CODE' => 'ASC']
			]);

			while ($ar = $res->fetch()) {
				$arParams[$ar['UF_CODE']] = $ar['UF_VALUE'];
			}
			return $arParams;
		});
	}

	/**
	 * Показать постер по активной дате UF_DATE_FROM до UF_DATE_TO
	 *
	 * @param string $hlBlockname = 'SupportPoster'
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function ShowPostersHtml(string $hlBlockname = self::SUPPORT_POSTER_HIBLOCK_NAME): void
	{
		$dm        = self::getDataManagerByHiCode($hlBlockname);
		$arPosters = $dm::getList([
			'select' => ['*'],
			'filter' => [
				">=UF_DATE_TO"   => [date('d.m.Y H:i:s'), false],
				"<=UF_DATE_FROM" => [date('d.m.Y H:i:s'), false]
			],
			'order'  => ['ID' => 'DESC'],
			'limit'  => 1
		])->fetchAll();

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

	/**
	 * событие очистки, нужен обработчик на CustomSettingsOnAfterUpdate, CustomSettingsOnAfterAdd, CustomSettingsOnAfterDelete
	 *
	 * @param \Bitrix\Main\Entity\Event $event
	 */
	public static function clearCustomSettingsCacheHandler(Event $event): void
	{
		PhpCacher::clearDirByTag(self::CS_CACHE_TAG);

		// clear composite (if it's in the memcache)
		if (is_a(CompositePage::getInstance()?->getStorage(), MemcachedStorage::class)) {
			CompositePage::getInstance()->deleteAll();
		}

		// clear component cache
		\CBitrixComponent::clearComponentCache("hipot:hiblock.list");

		// clear entity cache
		self::clearEntityOrmCacheHandler($event);
	}

	/**
	 * Clears the ORM cache for the associated entity.
	 *
	 * @param Event $event The event object containing the entity to clean the cache for.
	 * @return void
	 */
	public static function clearEntityOrmCacheHandler(Event $event): void
	{
		if (method_exists($event->getEntity(), 'cleanCache')) {
			$event->getEntity()->cleanCache();
		}
	}

} // end class