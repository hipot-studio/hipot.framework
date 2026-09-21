<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Hipot\BitrixUtils\Iblock;
use Hipot\Types\UpdateResult;

$fixtureElementIds = [];
$fixtureSectionIds = [];
$fixturePropertyIds = [];

beforeAll(function (): void {
	Loader::requireModule('iblock');
});

beforeEach(function () use (&$fixtureElementIds, &$fixtureSectionIds, &$fixturePropertyIds): void {
	$fixtureElementIds = [];
	$fixtureSectionIds = [];
	$fixturePropertyIds = [];
});

afterEach(function () use (&$fixtureElementIds, &$fixtureSectionIds, &$fixturePropertyIds): void {
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
	$fixtureSectionIds = [];
	$fixturePropertyIds = [];
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

it('creates updates and selects a catalog section tree', function () use (&$fixtureElementIds, &$fixtureSectionIds): void {
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
	$elementResult = Iblock::addElementToDb([
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'IBLOCK_SECTION_ID' => $childId,
		'ACTIVE' => 'Y',
		'NAME' => "Iblock section path element {$suffix}",
		'CODE' => "iblock-section-path-element-{$suffix}",
	]);
	$fixtureElementIds[] = (int)$elementResult->RESULT;
	$elementSections = Iblock::getElementSections($fixtureElementIds[0]);
	$elementSectionPaths = Iblock::getElementSectionPaths($fixtureElementIds[0]);

	expect($updateResult->STATUS)
		->toBe(UpdateResult::STATUS_OK)
		->and((int)$selected['ID'])->toBe($childId)
		->and((int)$selected['IBLOCK_ID'])->toBe(CATALOG_IBLOCK_ID)
		->and($selected['NAME'])->toBe($updatedChildName)
		->and($selected['CODE'])->toBe($childCode)
		->and(Iblock::checkSectionExistsByNameOrCode($childCode, CATALOG_IBLOCK_ID, 'code'))->toBe($childId)
		->and($descendants)->toHaveCount(1)
		->and((int)$descendants[0]['ID'])->toBe($childId)
		->and($elementSections)->toHaveCount(1)
		->and($elementSections[0]['ID'])->toBe($childId)
		->and($elementSectionPaths)->toBe([[$fixtureSectionIds[0], $childId]]);
});

it('looks up iblock metadata and properties by symbolic code', function (): void {
	$source = CIBlock::GetByID(CATALOG_IBLOCK_ID)->Fetch();
	$info = Iblock::getInfoByCode((string)$source['CODE'], (string)$source['IBLOCK_TYPE_ID']);
	$properties = Iblock::getPropertiesByCode(CATALOG_IBLOCK_ID, false);

	expect($info)->not->toBeNull()
		->and($info['ID'])->toBe(CATALOG_IBLOCK_ID)
		->and(Iblock::getIdByCode((string)$source['CODE'], (string)$source['IBLOCK_TYPE_ID']))
		->toBe(CATALOG_IBLOCK_ID);

	foreach ($properties as $code => $property) {
		expect($code)->not->toBe('')
			->and($property['CODE'])->toBe($code)
			->and($property['IBLOCK_ID'])->toBe(CATALOG_IBLOCK_ID);
	}
});

it('sets and clears a list property by enum XML_ID', function () use (&$fixtureElementIds, &$fixtureSectionIds, &$fixturePropertyIds): void {
	$suffix = strtoupper(bin2hex(random_bytes(6)));
	$propertyCode = "TEST_ENUM_{$suffix}";
	$xmlId = "VALUE_{$suffix}";
	$sectionResult = Iblock::addSectionToDb([
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'ACTIVE' => 'Y',
		'NAME' => "Enum property section {$suffix}",
		'CODE' => strtolower("enum-property-section-{$suffix}"),
	]);
	expect($sectionResult->STATUS)->toBe(UpdateResult::STATUS_OK);
	$fixtureSectionIds[] = (int)$sectionResult->RESULT;

	$property = new CIBlockProperty();
	$propertyId = (int)$property->Add([
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'ACTIVE' => 'Y',
		'NAME' => "Test enum {$suffix}",
		'CODE' => $propertyCode,
		'PROPERTY_TYPE' => 'L',
		'MULTIPLE' => 'N',
	]);
	expect($propertyId)->toBeGreaterThan(0);
	$fixturePropertyIds[] = $propertyId;

	$enumId = (int)CIBlockPropertyEnum::Add([
		'PROPERTY_ID' => $propertyId,
		'VALUE' => "Value {$suffix}",
		'XML_ID' => $xmlId,
	]);
	expect($enumId)->toBeGreaterThan(0);

	$elementResult = Iblock::addElementToDb([
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'IBLOCK_SECTION_ID' => $fixtureSectionIds[0],
		'ACTIVE' => 'Y',
		'NAME' => "Enum property element {$suffix}",
		'CODE' => strtolower("enum-property-element-{$suffix}"),
	]);
	$elementId = (int)$elementResult->RESULT;
	$fixtureElementIds[] = $elementId;

	$setResult = Iblock::setEnumPropertyByXmlId($elementId, CATALOG_IBLOCK_ID, $propertyCode, $xmlId);
	$stored = CIBlockElement::GetProperty(
		CATALOG_IBLOCK_ID,
		$elementId,
		['sort' => 'asc'],
		['CODE' => $propertyCode],
	)->Fetch();

	expect($setResult->isSuccess())->toBeTrue()
		->and($setResult->getData()['enumId'])->toBe($enumId)
		->and((int)$stored['VALUE'])->toBe($enumId)
		->and($stored['VALUE_XML_ID'])->toBe($xmlId);

	$clearResult = Iblock::setEnumPropertyByXmlId($elementId, CATALOG_IBLOCK_ID, $propertyCode, null);
	$cleared = CIBlockElement::GetProperty(
		CATALOG_IBLOCK_ID,
		$elementId,
		['sort' => 'asc'],
		['CODE' => $propertyCode],
	)->Fetch();

	expect($clearResult->isSuccess())->toBeTrue()
		->and($clearResult->getData()['enumId'])->toBeNull()
		->and($cleared === false || empty($cleared['VALUE']))->toBeTrue();
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
