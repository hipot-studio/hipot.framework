<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Hipot\BitrixUtils\Catalog;

beforeAll(function (): void {
	Loader::requireModule('catalog');
});

it('returns offer iblock metadata and filters the product link property', function (): void {
	$info = Catalog::getOffersIblockInfo(CATALOG_IBLOCK_ID);
	$properties = Catalog::getOfferProperties(CATALOG_IBLOCK_ID);

	expect($info)->not->toBeNull()
		->and($info['IBLOCK_ID'])->toBe(OFFERS_IBLOCK_ID)
		->and($info['PRODUCT_IBLOCK_ID'])->toBe(CATALOG_IBLOCK_ID)
		->and(Catalog::getOffersIblockId(CATALOG_IBLOCK_ID))->toBe(OFFERS_IBLOCK_ID)
		->and($properties)->not->toHaveKey('CML2_LINK');

	foreach ($properties as $property) {
		expect($property['ID'])->not->toBe($info['SKU_PROPERTY_ID']);
	}
});

it('returns empty offer data for an invalid product iblock', function (): void {
	expect(Catalog::getOffersIblockInfo(0))->toBeNull()
		->and(Catalog::getOffersIblockId(0))->toBeNull()
		->and(Catalog::getOfferProperties(0))->toBe([]);
});
