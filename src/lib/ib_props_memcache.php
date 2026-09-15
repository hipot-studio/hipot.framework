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
	Bitrix\Main\Data\MemcacheConnection,
	Bitrix\Iblock\IblockTable,
	Hipot\Services\ApcuNestedArrayWrapper,
	Hipot\Services\GlobalsCacher,
	Hipot\Services\ManagedCacheArrayWrapper,
	Hipot\Services\MemcacheNestedArrayWrapper,
	Hipot\Services\MemcacheWrapper,
	Hipot\Services\StaticPropertiesCacher,
	Hipot\Utils\UUtils,
	Hipot\Services\BitrixEngine;

(static function () {
	// region Speed up \CIBlockProperty::GetPropertyArray and \CIblockElement::SetPropertyValues(Ex)
	if (!class_exists(GlobalsCacher::class)) {
		if (class_exists(UUtils::class)) {
			UUtils::logException(new \Bitrix\Main\SystemException('No GlobalsCacher class for ' . basename(__FILE__)));
		}
		return;
	}
	
	try {
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
		
		$cacheIblockProperties = !defined('HIPOT_IBLOCK_CACHE_PROPERTY_ENABLED')
			|| HIPOT_IBLOCK_CACHE_PROPERTY_ENABLED === true;
		$cacheElementProperties = !defined('HIPOT_BX_IBLOCK_PROP_CACHE_ENABLED')
			|| HIPOT_BX_IBLOCK_PROP_CACHE_ENABLED === true;
		$apcuAvailable = $cacheElementProperties
			&& class_exists(ApcuNestedArrayWrapper::class)
			&& ApcuNestedArrayWrapper::isAvailable();
		$memcache = null;
		
		if ($cacheIblockProperties || ($cacheElementProperties && !$apcuAvailable)) {
			try {
				if (class_exists('Memcache')) {
					/** @var MemcacheConnection $connection */
					$connection = Application::getConnection('memcache');
					$resource = $connection->getResource();
					if ($resource instanceof Memcache) {
						$memcache = $resource;
					}
				}
			} catch (Throwable $e) {
				if (class_exists(UUtils::class)) {
					UUtils::logException($e);
				}
				// APCu can still be used independently for BX_IBLOCK_PROP_CACHE.
			}
		}
		
		$globals = [];
		if ($cacheIblockProperties && $memcache instanceof Memcache && class_exists(MemcacheWrapper::class)) {
			$globals['IBLOCK_CACHE_PROPERTY'] = [
				static function (): void {
					Loader::includeModule('iblock');
					// Initialize the global in iblock/classes/general/iblockproperty.php by autoload.
					$property = new CIBlockProperty();
					unset($property);
				},
				static fn(): \ArrayAccess => new MemcacheWrapper(
					'IBLOCK_CACHE_PROPERTY_' . $namespaceProvider(),
					$memcache,
				),
			];
		}
		
		if ($cacheElementProperties && $apcuAvailable) {
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
		} elseif (
			$cacheElementProperties
			&& $memcache instanceof Memcache
			&& class_exists(MemcacheNestedArrayWrapper::class)
		) {
			$globals['BX_IBLOCK_PROP_CACHE'] = [
				static function (): void {
					Loader::includeModule('iblock');
					$element = new CIBlockElement();
					unset($element);
				},
				static fn(): \ArrayAccess => new MemcacheNestedArrayWrapper(
					'BX_IBLOCK_PROP_CACHE_' . $namespaceProvider(),
					$memcache,
				),
			];
		}
		
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
	
	// region CCatalogSku/CIBlockElement managed cache
	try {
		if (
			class_exists(ManagedCacheArrayWrapper::class)
			&& class_exists(StaticPropertiesCacher::class)
			&& Loader::includeModule('iblock')
			&& Loader::includeModule('catalog')
		) {
			$managedCache = BitrixEngine::getInstance()->getManagedCache();
			$cacheTtl = 3600 * 24 * 30;
			$cacheTableId = 'orm_hipot_b_catalog_iblock';
			$wrapper = static fn(string $property): ManagedCacheArrayWrapper => new ManagedCacheArrayWrapper(
				'hipot.static.property.cacher.' . $property . '.v1.',
				$managedCache,
				$cacheTtl,
				$cacheTableId,
			);
			
			(new StaticPropertiesCacher([
				CCatalogSku::class => [
					'arOfferCache' => static fn(): ManagedCacheArrayWrapper => $wrapper('offer'),
					'arProductCache' => static fn(): ManagedCacheArrayWrapper => $wrapper('product'),
					'arPropertyCache' => static fn(): ManagedCacheArrayWrapper => $wrapper('property'),
					'arIBlockCache' => static fn(): ManagedCacheArrayWrapper => $wrapper('iblock'),
				],
				/*
				// not tested:
				CIBlockElement::class => [
					'elementIblock' => static fn(): MemcacheWrapper => new MemcacheWrapper('CIBlockElement_elementIblock_', $mc->getResource()),
				],
				*/
			]))->cache();
		}
	} catch (Throwable $exception) {
		if (class_exists(UUtils::class)) {
			UUtils::logException($exception);
		}
	}
	// endregion
	
	// _tests:
	/*
	if (isset($_REQUEST['IBLOCK_CACHE_PROPERTY'])) {
		// var_dump( $GLOBALS['IBLOCK_CACHE_PROPERTY']->getMc()->getExtendedStats() );
	
		$keys = $GLOBALS['IBLOCK_CACHE_PROPERTY']->getMemcachedKeys( $mc->getConfiguration() );
		foreach ($keys as $k) {
			echo $k;
			\Bitrix\Main\Diag\Debug::dump($GLOBALS['IBLOCK_CACHE_PROPERTY'][$k]);
		}
	
		\Bitrix\Main\Diag\Debug::dump( UUtils::getPrivateProperty(new CIBlockElement(), 'elementIblock') );
		exit;
	}
	*/
})();
