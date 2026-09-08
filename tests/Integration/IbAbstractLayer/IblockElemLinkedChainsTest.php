<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Hipot\IbAbstractLayer\IblockElemLinkedChains;
use Hipot\IbAbstractLayer\Types\IblockElementItem;
use Hipot\IbAbstractLayer\Types\IblockElementItemPropertyValue;
use Hipot\Types\Collection\Collection;

$fixtureElementIds = [];
$fixtureSectionId = 0;
$linkPropertyCode = '';

beforeAll(function () use (&$linkPropertyCode): void {
	Loader::requireModule('iblock');

	$property = CIBlockProperty::GetList(
		['ID' => 'ASC'],
		[
			'IBLOCK_ID' => OFFERS_IBLOCK_ID,
			'ACTIVE' => 'Y',
			'PROPERTY_TYPE' => 'E',
			'LINK_IBLOCK_ID' => CATALOG_IBLOCK_ID,
		]
	)->Fetch();

	expect($property)
		->not->toBeFalse('В инфоблоке предложений не найдено свойство привязки к каталогу.')
		->and($property['CODE'])->not->toBeEmpty();

	$linkPropertyCode = (string)$property['CODE'];
});

beforeEach(function () use (&$fixtureElementIds, &$fixtureSectionId, &$linkPropertyCode): void {
	$fixtureElementIds = [];
	$fixtureSectionId = 0;
	$fixtureSuffix = bin2hex(random_bytes(6));
	$section = new CIBlockSection();
	$element = new CIBlockElement();
	$sectionId = $section->Add([
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'ACTIVE' => 'Y',
		'NAME' => "Linked chains section {$fixtureSuffix}",
		'CODE' => "linked-chains-section-{$fixtureSuffix}",
	]);

	expect($sectionId)->not->toBeFalse($section->LAST_ERROR);
	$fixtureSectionId = (int)$sectionId;

	$catalogElementId = $element->Add([
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'IBLOCK_SECTION_ID' => $fixtureSectionId,
		'ACTIVE' => 'Y',
		'NAME' => "Linked chains product {$fixtureSuffix}",
		'CODE' => "linked-chains-product-{$fixtureSuffix}",
		'XML_ID' => "linked-chains-product-{$fixtureSuffix}",
	]);

	expect($catalogElementId)->not->toBeFalse($element->LAST_ERROR);
	$fixtureElementIds['catalog'] = (int)$catalogElementId;

	$offerElementId = $element->Add([
		'IBLOCK_ID' => OFFERS_IBLOCK_ID,
		'ACTIVE' => 'Y',
		'NAME' => "Linked chains offer {$fixtureSuffix}",
		'CODE' => "linked-chains-offer-{$fixtureSuffix}",
		'XML_ID' => "linked-chains-offer-{$fixtureSuffix}",
		'PROPERTY_VALUES' => [
			$linkPropertyCode => $fixtureElementIds['catalog'],
		],
	]);

	expect($offerElementId)->not->toBeFalse($element->LAST_ERROR);
	$fixtureElementIds['offer'] = (int)$offerElementId;
});

afterEach(function () use (&$fixtureElementIds, &$fixtureSectionId): void {
	foreach (array_reverse($fixtureElementIds) as $elementId) {
		CIBlockElement::Delete($elementId);
	}
	if ($fixtureSectionId > 0) {
		CIBlockSection::Delete($fixtureSectionId);
	}

	$fixtureElementIds = [];
	$fixtureSectionId = 0;
});

it('builds a linked element chain up to the initialized depth', function () use (&$fixtureElementIds, &$linkPropertyCode): void {
	$chainBuilder = new IblockElemLinkedChains();
	$chainBuilder->init(2);

	$offer = $chainBuilder->getChains_r($fixtureElementIds['offer'], ['XML_ID']);

	expect($offer)
		->toBeArray()
		->and((int)$offer['ID'])->toBe($fixtureElementIds['offer'])
		->and((int)$offer['IBLOCK_ID'])->toBe(OFFERS_IBLOCK_ID)
		->and($offer['XML_ID'])->toStartWith('linked-chains-offer-')
		->and($offer['PROPERTIES'][$linkPropertyCode]['CHAIN'])->toBeArray()
		->and((int)$offer['PROPERTIES'][$linkPropertyCode]['CHAIN']['ID'])->toBe($fixtureElementIds['catalog'])
		->and((int)$offer['PROPERTIES'][$linkPropertyCode]['CHAIN']['IBLOCK_ID'])->toBe(CATALOG_IBLOCK_ID);
});

it('stops building a chain when the initialized depth is zero', function () use (&$fixtureElementIds): void {
	$chainBuilder = new IblockElemLinkedChains();
	$chainBuilder->init(0);

	expect($chainBuilder->getChains_r($fixtureElementIds['offer']))->toBeNull();
});

it('converts a chain array to an abstract-layer element object', function () use (&$fixtureElementIds): void {
	$item = IblockElemLinkedChains::chainArrayToChainObject([
		'ID' => $fixtureElementIds['catalog'],
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'NAME' => 'Converted product',
	]);

	expect($item)
		->toBeInstanceOf(IblockElementItem::class)
		->and((int)$item->ID)->toBe($fixtureElementIds['catalog'])
		->and((int)$item->IBLOCK_ID)->toBe(CATALOG_IBLOCK_ID)
		->and($item->NAME)->toBe('Converted product');
});

it('returns linked offers as abstract-layer objects', function () use (&$fixtureElementIds, &$linkPropertyCode): void {
	$items = IblockElemLinkedChains::getList(
		['ID' => 'ASC'],
		['IBLOCK_ID' => OFFERS_IBLOCK_ID, 'ID' => $fixtureElementIds['offer']],
		false,
		false,
		['NAME', 'XML_ID'],
	);

	expect($items)
		->toBeInstanceOf(Collection::class)
		->toHaveCount(1);

	/** @var IblockElementItem $offer */
	$offer = $items[0];
	$linkProperty = $offer->PROPERTIES->{$linkPropertyCode};

	expect($offer)
		->toBeInstanceOf(IblockElementItem::class)
		->and((int)$offer->ID)->toBe($fixtureElementIds['offer'])
		->and((int)$offer->IBLOCK_ID)->toBe(OFFERS_IBLOCK_ID)
		->and($linkProperty)->toBeInstanceOf(IblockElementItemPropertyValue::class)
		->and((int)$linkProperty->VALUE)->toBe($fixtureElementIds['catalog'])
		->and($linkProperty->CHAIN)->toBeInstanceOf(IblockElementItem::class)
		->and((int)$linkProperty->CHAIN->ID)->toBe($fixtureElementIds['catalog'])
		->and((int)$linkProperty->CHAIN->IBLOCK_ID)->toBe(CATALOG_IBLOCK_ID);
});

it('returns the native element count for an empty group-by array', function () use (&$fixtureElementIds): void {
	$count = IblockElemLinkedChains::getList(
		[],
		['IBLOCK_ID' => OFFERS_IBLOCK_ID, 'ID' => $fixtureElementIds['offer']],
		[],
	);

	expect($count)->toBe('1');
});
