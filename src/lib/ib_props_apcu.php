<?php
/**
 * @see https://dev.1c-bitrix.ru/community/webdev/user/17890/blog/8910/?commentId=49110#com49110
 * @see iblock 23.200.0 https://dev.1c-bitrix.ru/docs/versions.php?lang=ru&module=iblock
 * Define HIPOT_IBLOCK_CACHE_PROPERTY_ENABLED=false before including the framework
 * to keep only BX_IBLOCK_PROP_CACHE enabled.
 *
 * @version 2.2
 * @author hipot, 2026
 */
defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader,
	Bitrix\Main\Application,
	Bitrix\Iblock\IblockTable,
	Hipot\Services\ApcuNestedArrayWrapper,
	Hipot\Services\GlobalsCacher,
	Hipot\Utils\UUtils,
	Hipot\Services\BitrixEngine,
	Bitrix\Main\SystemException;

(static function () {
	// region Speed up \CIBlockProperty::GetPropertyArray and \CIblockElement::SetPropertyValues(Ex)
	if (!class_exists(GlobalsCacher::class)) {
		if (class_exists(UUtils::class)) {
			UUtils::logException(new SystemException('No GlobalsCacher class for ' . basename(__FILE__)));
		}
		return;
	}

	try {
		$apcuAvailable = class_exists(ApcuNestedArrayWrapper::class) && ApcuNestedArrayWrapper::isAvailable();
		if (!$apcuAvailable) {
			return;
		}
		
		$namespaceProvider = static function (): string {
			static $cacheNamespace = null;
			if ($cacheNamespace !== null) {
				return $cacheNamespace;
			}
			
			// Keep caches for different iblock storage versions isolated.
			$iblockVersions = [];
			$result = IblockTable::query()
			                     ->setSelect(['ID', 'VERSION'])
			                     ->setOrder(['ID' => 'ASC'])
			                     ->setCacheTtl(3600 * 24 * 3)
			                     ->exec();
			while ($iblock = $result->fetch()) {
				$iblockVersions[$iblock['ID']] = $iblock['VERSION'];
			}
			
			$serverName = (string)Application::getInstance()
			                                 ->getContext()
			                                 ->getServer()
			                                 ->getServerName();
			
			$cacheNamespace = md5(serialize($iblockVersions) . $serverName);
			
			return $cacheNamespace;
		};
		$globals = [];
		
		$globals['IBLOCK_CACHE_PROPERTY'] = [
			static function (): void {
				Loader::includeModule('iblock');
				// Initialize the global in iblock/classes/general/iblockproperty.php by autoload.
				$property = new CIBlockProperty();
				unset($property);
			},
			static fn(): \ArrayAccess => new ApcuNestedArrayWrapper(
				'IBLOCK_CACHE_PROPERTY_' . $namespaceProvider(),
			),
		];

		$globals['BX_IBLOCK_PROP_CACHE'] = [
			static function (): void {
				Loader::includeModule('iblock');
				// Initialize the global in iblock/classes/general/iblockelement.php by autoload.
				$element = new CIBlockElement();
				unset($element);
			},
			static fn(): \ArrayAccess => new ApcuNestedArrayWrapper(
				'BX_IBLOCK_PROP_CACHE_' . $namespaceProvider(),
			),
		];

		if ($globals !== []) {
			(new GlobalsCacher($globals))->cache();
		}

		if (isset($globals['BX_IBLOCK_PROP_CACHE'])) {
			BitrixEngine::getInstance()->eventManager->addEventHandler(
				'iblock',
				'OnAfterIBlockPropertyDelete',
				static function (array $property): void {
					$iblockId = (int)($property['IBLOCK_ID'] ?? 0);
					/** @noinspection GlobalVariableUsageInspection */
					if ($iblockId > 0 && isset($GLOBALS['BX_IBLOCK_PROP_CACHE'])) {
						/** @noinspection GlobalVariableUsageInspection */
						unset($GLOBALS['BX_IBLOCK_PROP_CACHE'][$iblockId]);
					}
				},
			);
		}
	} catch (Throwable $e) {
		if (class_exists(UUtils::class)) {
			UUtils::logException($e);
		}
	}
	// endregion
	
	// _tests:
	/*
	if (isset($_REQUEST['IBLOCK_CACHE_PROPERTY_TEST'])) {
		\Bitrix\Main\Diag\Debug::dump($GLOBALS['IBLOCK_CACHE_PROPERTY']);
		\Bitrix\Main\Diag\Debug::dump($GLOBALS['BX_IBLOCK_PROP_CACHE']);
		
		// \Bitrix\Main\Diag\Debug::dump( UUtils::getPrivateProperty(new CIBlockElement(), 'elementIblock') );
		exit;
	}
	*/
})();
