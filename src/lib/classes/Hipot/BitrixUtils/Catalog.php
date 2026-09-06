<?php

namespace Hipot\BitrixUtils;

use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Loader;
use Bitrix\Iblock;
use Hipot\BitrixUtils\Iblock as IblockUtils;

Loader::includeModule('catalog');

/**
 * Class Catalog
 *
 * This class provides utility methods for managing product-related tasks such as handling barcodes,
 * unit of measurement ratios, discounts, and product updates in the catalog.
 */
final class Catalog
{
	/**
	 * Retrieves the barcode for a given product.
	 * @param int $productId The ID of the product.
	 * @return string The barcode for the product, or null if no barcode is found.
	 */
	public static function getProductBarCode(int $productId): string
	{
		if ($productId <= 0) {
			return false;
		}
		$dbBarCode = \CCatalogStoreBarCode::getList([], ["PRODUCT_ID" => $productId], false, false, ["ID", "BARCODE", 'PRODUCT_ID']);
		$arBarCode = $dbBarCode->GetNext();
		return $arBarCode['BARCODE'] ?? '';
	}

	/**
	 * Sets the barcode for a product.
	 *
	 * @param int    $productId The ID of the product.
	 * @param string $barcode The barcode to set.
	 * @param int    $userId The ID of the user performing the action.
	 *
	 * @return bool
	 */
	public static function setProductBarCode(int $productId, string $barcode, int $userId): bool
	{
		if ($productId <= 0) {
			return false;
		}

		$dbBarCode = \CCatalogStoreBarCode::getList([], ["PRODUCT_ID" => $productId], false, false, ["ID", "BARCODE", 'PRODUCT_ID']);
		$arBarCode = $dbBarCode->GetNext();
		if (!$arBarCode) {
			$dbBarCode = \CCatalogStoreBarCode::Add(["PRODUCT_ID" => $productId, "BARCODE" => $barcode, "CREATED_BY" => $userId]);
		} elseif ($arBarCode["ID"]["BARCODE"] != $barcode) {
			$dbBarCode = \CCatalogStoreBarCode::Update($arBarCode["ID"], ["BARCODE" => $barcode, "MODIFIED_BY" => $userId]);
		}
		return true;
	}

	/**
	 * Получить коэффициент единицы измерения продукта
	 *
	 * @param int $productId Идентификатор продукта
	 *
	 * @return float|false Возвращает коэффициент измерения продукта или false, если коэффициент не найден
	 */
	public static function getProductMeasureRatio(int $productId)
	{
		if ($productId <= 0) {
			return false;
		}
		$ratioIterator = \CCatalogMeasureRatio::getList([], ['PRODUCT_ID' => $productId], false, false, ['ID', 'PRODUCT_ID', 'RATIO']);
		if ($currentRatio = $ratioIterator->Fetch()) {
			return $currentRatio['RATIO'];
		}
		return false;
	}

	/**
	 * Получить скидки у продукта из инфоблока
	 *
	 * @param int $productId
	 * @param int $iblockId = 0
	 *
	 * @return array
	 */
	public static function getDiscountForProduct(int $productId, int $iblockId = 0): array
	{
		if ($iblockId == 0) {
			$iblockId = IblockUtils::getElementIblockId($productId);
		}

		static $arMainCatalog;
		if (!isset($arMainCatalog[$iblockId])) {
			$arMainCatalog[$iblockId] = \CCatalogSku::GetInfoByIBlock($iblockId);

			$siteList = [];
			$iterator = Iblock\IblockSiteTable::getList([
				'select' => ['SITE_ID'],
				'filter' => ['=IBLOCK_ID' => $iblockId],
				'cache'  => ['ttl' => 3600 * 24 * 7, 'cache_joins' => true]
			]);
			while ($row = $iterator->fetch()) {
				$siteList[] = $row['SITE_ID'];
			}
			unset($row, $iterator);
			$arMainCatalog[$iblockId]['SITE_ID'] = $siteList;
		}

		$arParams = [];
		if (\CCatalogSku::TYPE_OFFERS == $arMainCatalog['CATALOG_TYPE']) {
			$arParams['SKU']        = 'Y';
			$arParams['SKU_PARAMS'] = [
				'IBLOCK_ID' => $arMainCatalog['IBLOCK_ID'],
				'PRODUCT_IBLOCK_ID' => $arMainCatalog['PRODUCT_IBLOCK_ID'],
				'SKU_PROPERTY_ID' => $arMainCatalog['SKU_PROPERTY_ID'],
			];
		}
		$arParams['DISCOUNT_FIELDS'] = [
			"ACTIVE_FROM", 'ACTIVE_TO', 'NAME', 'ID', 'SITE_ID', 'SORT', 'NAME', 'VALUE_TYPE', 'VALUE'
		];
		$arParams['SITE_ID']         = $arMainCatalog[$iblockId]['SITE_ID'];

		$arDiscountList = \CCatalogDiscount::GetDiscountForProduct(['ID' => $productId, 'IBLOCK_ID' => $iblockId], $arParams);
		if (PHP_SAPI == 'cli') {
			\CCatalogDiscount::ClearDiscountCache([
				'PRODUCT' => true,
				'SECTIONS' => true,
				'PROPERTIES' => true
			]);
		}
		return $arDiscountList;
	}

	/**
	 * Updates a product.
	 *
	 * @param int   $productId The ID of the product.
	 * @param array $arItemProduct An optional array of product data.
	 *
	 * @return \Bitrix\Main\ORM\Data\Result
	 */
	public static function updateProduct(int $productId, array $arItemProduct = []): \Bitrix\Main\ORM\Data\Result
	{
		// Create the product
		$res = ProductTable::getList([
			'filter' => [
				"ID" => $productId,
			],
			'select' => ['*']
		]);
		if (!$arProduct = $res->fetch()) {
			$arFields        = [
				'ID'             => $productId,
				'QUANTITY_TRACE' => ProductTable::STATUS_DEFAULT,
				'CAN_BUY_ZERO'   => ProductTable::STATUS_DEFAULT,
			];
			if ($arItemProduct['QUANTITY']) {
				$arFields['QUANTITY'] = $arItemProduct['QUANTITY'];
			} else {
				$arFields['QUANTITY'] = 0;
			}
			if (isset($arItemProduct['AVAILABLE'])) {
				$arFields['AVAILABLE'] = $arItemProduct['AVAILABLE'];
			} else {
				$arFields['AVAILABLE'] = ProductTable::calculateAvailable($arFields);
			}
			$arFields += $arItemProduct;
			return ProductTable::add($arFields);
		}

		$arFields = [
			'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
			'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
		];
		if (isset($arItemProduct['QUANTITY'])) {
			$arFields['QUANTITY'] = $arItemProduct['QUANTITY'];
		} else {
			$arFields['QUANTITY'] = 0;
		}
		if (isset($arItemProduct['AVAILABLE'])) {
			$arFields['AVAILABLE'] = $arItemProduct['AVAILABLE'];
		} else {
			$arFields['AVAILABLE'] = ProductTable::calculateAvailable($arFields);
		}
		$arFields += $arItemProduct;
		return ProductTable::update($arProduct['ID'], $arFields);
	}
}