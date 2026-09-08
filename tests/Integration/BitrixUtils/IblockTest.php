<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Hipot\BitrixUtils\Iblock;
use Hipot\Types\UpdateResult;

$fixtureElementIds = [];
$fixtureSectionIds = [];

beforeAll(function (): void {
	Loader::requireModule('iblock');
});

beforeEach(function () use (&$fixtureElementIds, &$fixtureSectionIds): void {
	$fixtureElementIds = [];
	$fixtureSectionIds = [];
});

afterEach(function () use (&$fixtureElementIds, &$fixtureSectionIds): void {
	foreach (array_reverse($fixtureElementIds) as $elementId) {
		CIBlockElement::Delete($elementId);
	}
	foreach (array_reverse($fixtureSectionIds) as $sectionId) {
		CIBlockSection::Delete($sectionId);
	}

	$fixtureElementIds = [];
	$fixtureSectionIds = [];
});

it('creates updates finds and selects a catalog element', function () use (&$fixtureElementIds, &$fixtureSectionIds): void {
	$suffix = bin2hex(random_bytes(6));
	$sectionCode = "iblock-test-section-{$suffix}";
	$elementCode = "iblock-test-element-{$suffix}";
	$elementXmlId = "iblock-test-xml-{$suffix}";
	$elementName = "Iblock O'Reilly element {$suffix}";
	$updatedName = "Updated Iblock element {$suffix}";

	$sectionResult = Iblock::addSectionToDb([
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'ACTIVE' => 'Y',
		'NAME' => "Iblock test section {$suffix}",
		'CODE' => $sectionCode,
	]);

	expect($sectionResult)
		->toBeInstanceOf(UpdateResult::class)
		->and($sectionResult->STATUS)->toBe(UpdateResult::STATUS_OK);
	$fixtureSectionIds[] = (int)$sectionResult->RESULT;

	$elementResult = Iblock::addElementToDb([
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'IBLOCK_SECTION_ID' => $fixtureSectionIds[0],
		'ACTIVE' => 'Y',
		'NAME' => $elementName,
		'CODE' => $elementCode,
		'XML_ID' => $elementXmlId,
	]);

	expect($elementResult)
		->toBeInstanceOf(UpdateResult::class)
		->and($elementResult->STATUS)->toBe(UpdateResult::STATUS_OK);
	$fixtureElementIds[] = (int)$elementResult->RESULT;
	$elementId = $fixtureElementIds[0];

	expect(Iblock::checkElementExistsByNameOrCode($elementName, CATALOG_IBLOCK_ID))
		->toBe($elementId)
		->and(Iblock::checkElementExistsByNameOrCode($elementCode, CATALOG_IBLOCK_ID, 'code'))->toBe($elementId)
		->and(Iblock::checkExistsByNameOrCode($elementXmlId, CATALOG_IBLOCK_ID, 'xml_id'))->toBe($elementId)
		->and(Iblock::getElementIblockId($elementId))->toBe(CATALOG_IBLOCK_ID);

	$updateResult = Iblock::updateElementToDb($elementId, ['NAME' => $updatedName]);

	expect($updateResult)
		->toBeInstanceOf(UpdateResult::class)
		->and($updateResult->STATUS)->toBe(UpdateResult::STATUS_OK)
		->and((int)$updateResult->RESULT)->toBe($elementId)
		->and(Iblock::checkElementExistsByNameOrCode($updatedName, CATALOG_IBLOCK_ID))->toBe($elementId)
		->and(Iblock::checkElementExistsByNameOrCode($elementName, CATALOG_IBLOCK_ID))->toBeFalse();

	$selected = Iblock::selectElementsByFilterArray(
		['ID' => 'ASC'],
		['IBLOCK_ID' => CATALOG_IBLOCK_ID, 'ID' => $elementId],
		false,
		false,
		['NAME', 'CODE', 'XML_ID'],
	);

	expect((int)$selected['ID'])
		->toBe($elementId)
		->and((int)$selected['IBLOCK_ID'])->toBe(CATALOG_IBLOCK_ID)
		->and($selected['NAME'])->toBe($updatedName)
		->and($selected['CODE'])->toBe($elementCode)
		->and($selected['XML_ID'])->toBe($elementXmlId)
		->and(Iblock::checkExistsByNameOrCode($elementName, CATALOG_IBLOCK_ID, 'unknown'))->toBeFalse();
});

it('creates updates and selects a catalog section tree', function () use (&$fixtureSectionIds): void {
	$suffix = bin2hex(random_bytes(6));
	$parentCode = "iblock-test-parent-{$suffix}";
	$childCode = "iblock-test-child-{$suffix}";

	$parentResult = Iblock::addSectionToDb([
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'ACTIVE' => 'Y',
		'NAME' => "Iblock parent {$suffix}",
		'CODE' => $parentCode,
	]);
	expect($parentResult->STATUS)->toBe(UpdateResult::STATUS_OK);
	$fixtureSectionIds[] = (int)$parentResult->RESULT;

	$childResult = Iblock::addSectionToDb([
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'IBLOCK_SECTION_ID' => $fixtureSectionIds[0],
		'ACTIVE' => 'Y',
		'NAME' => "Iblock child {$suffix}",
		'CODE' => $childCode,
	]);
	expect($childResult->STATUS)->toBe(UpdateResult::STATUS_OK);
	$fixtureSectionIds[] = (int)$childResult->RESULT;
	$childId = $fixtureSectionIds[1];
	$updatedChildName = "Updated Iblock child {$suffix}";

	$updateResult = Iblock::updateSectionToDb($childId, ['NAME' => $updatedChildName]);
	$selected = Iblock::selectSectionsByFilterArray(
		['ID' => 'ASC'],
		['IBLOCK_ID' => CATALOG_IBLOCK_ID, 'ID' => $childId],
		false,
		['NAME', 'CODE'],
	);
	$descendants = Iblock::selectSubsectionByParentSection(
		['IBLOCK_ID' => CATALOG_IBLOCK_ID, 'ID' => $fixtureSectionIds[0]],
		['ID', 'NAME'],
	);

	expect($updateResult->STATUS)
		->toBe(UpdateResult::STATUS_OK)
		->and((int)$selected['ID'])->toBe($childId)
		->and((int)$selected['IBLOCK_ID'])->toBe(CATALOG_IBLOCK_ID)
		->and($selected['NAME'])->toBe($updatedChildName)
		->and($selected['CODE'])->toBe($childCode)
		->and(Iblock::checkSectionExistsByNameOrCode($childCode, CATALOG_IBLOCK_ID, 'code'))->toBe($childId)
		->and($descendants)->toHaveCount(1)
		->and((int)$descendants[0]['ID'])->toBe($childId);
});

it('returns the same offer from the find-or-create helper', function () use (&$fixtureElementIds): void {
	$name = 'Iblock helper ' . bin2hex(random_bytes(6));

	$firstId = Iblock::addToHelperAndReturnElementId($name, OFFERS_IBLOCK_ID);
	expect($firstId)->toBeInt()->toBeGreaterThan(0);
	$fixtureElementIds[] = $firstId;

	$secondId = Iblock::addToHelperAndReturnElementId($name, OFFERS_IBLOCK_ID);

	expect($secondId)
		->toBe($firstId)
		->and(Iblock::checkElementExistsByNameOrCode($name, OFFERS_IBLOCK_ID))->toBe($firstId);
});

it('toggles iblock tag-cache clearing idempotently', function (): void {
	$wasEnabled = CIBlock::isEnabledClearTagCache();

	try {
		expect(Iblock::disableIblockCacheClear())
			->toBeTrue()
			->and(CIBlock::isEnabledClearTagCache())->toBeFalse()
			->and(Iblock::disableIblockCacheClear())->toBeTrue()
			->and(Iblock::enableIblockCacheClear())->toBeTrue()
			->and(CIBlock::isEnabledClearTagCache())->toBeTrue()
			->and(Iblock::enableIblockCacheClear())->toBeTrue();
	} finally {
		$wasEnabled ? Iblock::enableIblockCacheClear() : Iblock::disableIblockCacheClear();
	}
});
