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

it('preserves quantity when updating other product fields', function (): void {
	$section = CIBlockSection::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => CATALOG_IBLOCK_ID], false, ['ID'])->Fetch();
	expect($section)->toBeArray();

	$element = new CIBlockElement();
	$productId = (int)$element->Add([
		'IBLOCK_ID'         => CATALOG_IBLOCK_ID,
		'IBLOCK_SECTION_ID' => (int)$section['ID'],
		'NAME'              => 'Product quantity regression',
		'CODE'              => 'product-quantity-' . bin2hex(random_bytes(6)),
		'ACTIVE'            => 'N',
	]);
	expect($productId)->toBeGreaterThan(0, $element->LAST_ERROR);

	try {
		$created = Catalog::updateProduct($productId, ['QUANTITY' => 7]);
		expect($created->isSuccess())->toBeTrue(implode('; ', $created->getErrorMessages()));

		$updated = Catalog::updateProduct($productId, ['QUANTITY_TRACE' => 'Y']);
		expect($updated->isSuccess())->toBeTrue(implode('; ', $updated->getErrorMessages()));

		$product = \Bitrix\Catalog\ProductTable::getByPrimary($productId)->fetch();
		expect((float)$product['QUANTITY'])->toBe(7.0)
			->and($product['QUANTITY_TRACE'])->toBe('Y');
	} finally {
		CIBlockElement::Delete($productId);
	}
});
