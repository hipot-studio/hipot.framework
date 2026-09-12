<?php

declare(strict_types=1);

use Hipot\BitrixUtils\Iblock\Element;

require_once dirname(__DIR__, 2) . '/Stubs/IblockEnumBitrix.php';

final class IblockEnumTestHarness
{
	use Element;
}

beforeEach(function (): void {
	CIBlockPropertyEnum::$rows = [
		[
			'ID' => '11',
			'IBLOCK_ID' => 7,
			'PROPERTY_CODE' => 'STATUS',
			'XML_ID' => 'ACTIVE',
			'VALUE' => 'Active & visible',
			'DEF' => 'N',
		],
		[
			'ID' => '12',
			'IBLOCK_ID' => 7,
			'PROPERTY_CODE' => 'STATUS',
			'XML_ID' => 'ARCHIVED',
			'VALUE' => 'Archived',
			'DEF' => 'Y',
		],
	];
	CIBlockPropertyEnum::$lastOrder = [];
	CIBlockPropertyEnum::$lastFilter = [];
	CIBlockPropertyEnum::$queryCount = 0;
});

it('resolves an enum ID by XML_ID with one focused query', function (): void {
	expect(IblockEnumTestHarness::getEnumIdByXmlId(7, 'ACTIVE', 'STATUS'))->toBe(11)
		->and(CIBlockPropertyEnum::$queryCount)->toBe(1)
		->and(CIBlockPropertyEnum::$lastFilter)->toBe([
			'IBLOCK_ID' => 7,
			'PROPERTY_CODE' => 'STATUS',
			'XML_ID' => 'ACTIVE',
		]);
});

it('resolves an enum XML_ID by ID', function (): void {
	expect(IblockEnumTestHarness::getEnumXmlIdById(7, 11, 'STATUS'))->toBe('ACTIVE');
});

it('returns the raw enum display value by XML_ID', function (): void {
	expect(IblockEnumTestHarness::getEnumValueByXmlId(7, 'ACTIVE', 'STATUS'))
		->toBe('Active & visible');
});

it('uses the default enum when a lookup value is null', function (): void {
	expect(IblockEnumTestHarness::getEnumIdByXmlId(7, null, 'STATUS'))->toBe(12)
		->and(IblockEnumTestHarness::getEnumXmlIdById(7, null, 'STATUS'))->toBe('ARCHIVED')
		->and(IblockEnumTestHarness::getEnumValueByXmlId(7, null, 'STATUS'))->toBe('Archived');
});

it('returns null for missing values and invalid lookup arguments', function (): void {
	expect(IblockEnumTestHarness::getEnumIdByXmlId(7, 'MISSING', 'STATUS'))->toBeNull()
		->and(IblockEnumTestHarness::getEnumXmlIdById(7, 999, 'STATUS'))->toBeNull()
		->and(IblockEnumTestHarness::getEnumValueByXmlId(0, 'ACTIVE', 'STATUS'))->toBeNull()
		->and(IblockEnumTestHarness::getEnumIdByXmlId(7, '', 'STATUS'))->toBeNull()
		->and(IblockEnumTestHarness::getEnumIdByXmlId(7, 'ACTIVE', ''))->toBeNull();
});
