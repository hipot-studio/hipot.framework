<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Hipot\Components\IblockList;
use function Opis\Closure\serialize as serializeClosure;

$fixtureElementIds = [];
$fixturePropertyIds = [];
$fixtureSectionIds = [];

beforeAll(function (): void {
	Loader::requireModule('iblock');
});

beforeEach(function () use (&$fixtureElementIds, &$fixturePropertyIds, &$fixtureSectionIds): void {
	$fixtureElementIds = [];
	$fixturePropertyIds = [];
	$fixtureSectionIds = [];
});

afterEach(function () use (&$fixtureElementIds, &$fixturePropertyIds, &$fixtureSectionIds): void {
	foreach (array_reverse($fixtureElementIds) as $elementId) {
		CIBlockElement::Delete($elementId);
	}
	foreach (array_reverse($fixturePropertyIds) as $propertyId) {
		CIBlockProperty::Delete($propertyId);
	}
	foreach (array_reverse($fixtureSectionIds) as $sectionId) {
		CIBlockSection::Delete($sectionId);
	}

	$fixtureElementIds = [];
	$fixturePropertyIds = [];
	$fixtureSectionIds = [];
});

it('selects active iblock elements and prepares their properties', function () use (
	&$fixtureElementIds,
	&$fixturePropertyIds,
	&$fixtureSectionIds,
): void {
	/** @var CMain $APPLICATION */
	global $APPLICATION;

	$suffix = bin2hex(random_bytes(6));
	$propertyCode = 'COMPONENT_TEST_' . strtoupper($suffix);
	$section = new CIBlockSection();
	$sectionId = $section->Add([
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'ACTIVE' => 'Y',
		'NAME' => "Iblock list section {$suffix}",
		'CODE' => "iblock-list-section-{$suffix}",
	]);

	expect($sectionId)->not->toBeFalse($section->LAST_ERROR);
	$fixtureSectionIds[] = (int)$sectionId;

	$property = new CIBlockProperty();
	$propertyId = $property->Add([
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'ACTIVE' => 'Y',
		'NAME' => "Iblock list property {$suffix}",
		'CODE' => $propertyCode,
		'PROPERTY_TYPE' => 'S',
		'MULTIPLE' => 'N',
	]);

	expect($propertyId)->not->toBeFalse($property->LAST_ERROR);
	$fixturePropertyIds[] = (int)$propertyId;

	$element = new CIBlockElement();
	$fixtures = [
		['ACTIVE' => 'Y', 'NAME' => "A component item {$suffix}", 'PROPERTY_VALUE' => "value-a-{$suffix}"],
		['ACTIVE' => 'Y', 'NAME' => "B component item {$suffix}", 'PROPERTY_VALUE' => "value-b-{$suffix}"],
		['ACTIVE' => 'N', 'NAME' => "C inactive item {$suffix}", 'PROPERTY_VALUE' => "value-c-{$suffix}"],
	];

	foreach ($fixtures as $fixture) {
		$elementId = $element->Add([
			'IBLOCK_ID' => CATALOG_IBLOCK_ID,
			'IBLOCK_SECTION_ID' => (int)$sectionId,
			'ACTIVE' => $fixture['ACTIVE'],
			'NAME' => $fixture['NAME'],
			'CODE' => strtolower(str_replace(' ', '-', $fixture['NAME'])),
			'PROPERTY_VALUES' => [
				$propertyCode => $fixture['PROPERTY_VALUE'],
			],
		]);

		expect($elementId)->not->toBeFalse($element->LAST_ERROR);
		$fixtureElementIds[] = (int)$elementId;
	}

	$modifyItem = static function (array &$item) use ($propertyCode): void {
		$item['NAME'] .= '|' . ($item['PROPERTIES'][$propertyCode]['VALUE'] ?? 'missing-property');
	};

	ob_start();
	try {
		$result = $APPLICATION->IncludeComponent(
			'hipot:iblock.list',
			'edit_example',
			[
				'IBLOCK_ID' => CATALOG_IBLOCK_ID,
				'ORDER' => ['NAME' => 'DESC'],
				'FILTER' => [
					'SECTION_ID' => (int)$sectionId,
					'INCLUDE_SUBSECTIONS' => 'N',
				],
				'SELECT' => ['CODE'],
				'GET_PROPERTY' => 'Y',
				'~MODIFY_ITEM' => serializeClosure($modifyItem),
				'NTOPCOUNT' => 0,
				'PAGESIZE' => 0,
				'NAV_TEMPLATE' => '',
				'NAV_SHOW_ALWAYS' => 'N',
				'NAV_SHOW_ALL' => 'N',
				'NAV_PAGEWINDOW' => 3,
				'SET_404' => 'N',
				'ALWAYS_INCLUDE_TEMPLATE' => 'Y',
				'SELECT_CHAINS' => 'N',
				'SELECT_CHAINS_DEPTH' => 3,
				'CACHE_TYPE' => 'N',
				'CACHE_TIME' => 0,
				'CACHE_GROUPS' => 'N',
			],
			false,
			['HIDE_ICONS' => 'Y'],
			true,
		);
	} finally {
		$rendered = (string)ob_get_clean();
	}

	$firstRenderedItem = "B component item {$suffix}|value-b-{$suffix}";
	$secondRenderedItem = "A component item {$suffix}|value-a-{$suffix}";

	expect(class_exists(IblockList::class, false))
		->toBeTrue()
		->and($result)->toBeArray()
		->and($result['CNT_ITEMS'])->toBe(2)
		->and($rendered)->toContain($firstRenderedItem, $secondRenderedItem)
		->and($rendered)->not->toContain("C inactive item {$suffix}", 'missing-property')
		->and(strpos($rendered, $firstRenderedItem))->toBeLessThan(strpos($rendered, $secondRenderedItem));
});
