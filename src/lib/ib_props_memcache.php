<?php
/**
 * @see https://dev.1c-bitrix.ru/community/webdev/user/17890/blog/8910/?commentId=49110#com49110
 * @see iblock 23.200.0 https://dev.1c-bitrix.ru/docs/versions.php?lang=ru&module=iblock
 * @version 2.1
 * @author hipot, 2026
 */
defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader,
	Bitrix\Main\Application,
	Bitrix\Main\Data\MemcacheConnection,
	Bitrix\Iblock\IblockTable,
	Hipot\Services\GlobalsCacher,
	Hipot\Services\ManagedCacheArrayWrapper,
	Hipot\Services\MemcacheNestedArrayWrapper,
	Hipot\Services\MemcacheWrapper,
	Hipot\Services\StaticPropertiesCacher,
	Hipot\Utils\UUtils,
	Hipot\Services\BitrixEngine;

(static function () {
	// region CCatalogSku managed cache
	try {
		if (
			class_exists(ManagedCacheArrayWrapper::class)
			&& class_exists(StaticPropertiesCacher::class)
			&& Loader::includeModule('catalog')
		) {
			$managedCache = Application::getInstance()->getManagedCache();
			$cacheTtl = 3600 * 24 * 30;
			$cacheTableId = 'orm_hipot_b_catalog_iblock';
			$wrapper = static fn(string $property): ManagedCacheArrayWrapper => new ManagedCacheArrayWrapper(
				'hipot.ccatalogsku.' . $property . '.v1.',
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
			]))->cache();
		}
	} catch (Throwable $exception) {
		if (class_exists(UUtils::class)) {
			UUtils::logException($exception);
		}
	}
	// endregion
	
	// region Speed up \CIBlockProperty::GetPropertyArray and \CIblockElement::SetPropertyValues(Ex) wia memcache
	if (
		!class_exists('Memcache')
		|| !class_exists(MemcacheWrapper::class)
		|| !class_exists(MemcacheNestedArrayWrapper::class)
		|| !class_exists(GlobalsCacher::class)
	) {
		UUtils::logException(new \Bitrix\Main\SystemException('no memcache classes to ' . basename(__FILE__)));
		return;
	}
	
	try {
		/** @var MemcacheConnection $mc */
		$mc = Application::getConnection('memcache');
		if (null !== $mc) {
			
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
			
			(new GlobalsCacher(
				$mc->getResource(),
				[
					'IBLOCK_CACHE_PROPERTY' => [
						static function (): void {
							Loader::includeModule('iblock');
							// Initialize the global in iblock/classes/general/iblockproperty.php by autoload.
							$property = new CIBlockProperty();
							unset($property);
						},
						static fn(): string => 'IBLOCK_CACHE_PROPERTY_' . $namespaceProvider(),
					],
					'BX_IBLOCK_PROP_CACHE' => [
						static function (): void {
							Loader::includeModule('iblock');
							// Initialize the global in iblock/classes/general/iblockelement.php by autoload.
							$element = new CIBlockElement();
							unset($element);
						},
						static fn(): string => 'BX_IBLOCK_PROP_CACHE_' . $namespaceProvider(),
					],
				],
				static function (string $prefix, object $connection): \ArrayAccess {
					if (!$connection instanceof Memcache) {
						throw new InvalidArgumentException('Memcache connection expected.');
					}
					
					if (str_starts_with($prefix, 'BX_IBLOCK_PROP_CACHE_')) {
						return new MemcacheNestedArrayWrapper($prefix, $connection);
					}
					
					return new MemcacheWrapper($prefix, $connection);
				},
			))->cache();
			
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
	} catch (Error $e) {
		UUtils::logException($e);
	}
	unset($mc);
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
		exit;
	}
	*/
})();
