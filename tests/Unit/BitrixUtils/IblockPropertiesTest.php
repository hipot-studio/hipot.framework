<?php

declare(strict_types=1);

use Hipot\BitrixUtils\Iblock;
use Bitrix\Iblock\PropertyTable;

require_once dirname(__DIR__, 2) . '/Stubs/IblockLookupOrm.php';

if (!class_exists('_CIBElement', false)) {
	class _CIBElement
	{
	}
}

beforeEach(function (): void {
	Iblock::clearPropertiesByCodeCache();
	PropertyTable::$queryCount = 0;
	PropertyTable::$rows = [
		['ID' => '1', 'IBLOCK_ID' => '7', 'CODE' => 'STATUS', 'ACTIVE' => 'Y', 'SORT' => '10'],
		['ID' => '2', 'IBLOCK_ID' => '7', 'CODE' => 'OLD', 'ACTIVE' => 'N', 'SORT' => '20'],
	];
});

it('reuses property maps and separates active mode and iblock', function (): void {
	$map = Iblock::getPropertiesByCode(7);
	expect(array_keys($map))->toBe(['STATUS'])
		->and($map['STATUS']['ID'])->toBe(1)
		->and(Iblock::getPropertiesByCode(7))->toBe($map)
		->and(PropertyTable::$queryCount)->toBe(1)
		->and(PropertyTable::$ttl)->toBe(Iblock::CACHE_TTL)
		->and(Iblock::getPropertiesByCode(7, false))->toHaveCount(2)
		->and(Iblock::getPropertiesByCode(8))->toBe([])
		->and(Iblock::getPropertiesByCode(8))->toBe([])
		->and(PropertyTable::$queryCount)->toBe(3);
});

it('reloads the property map after invalidation and returns independent arrays', function (): void {
	$map = Iblock::getPropertiesByCode(7);
	$map['STATUS']['ID'] = 99;
	expect(Iblock::getPropertiesByCode(7)['STATUS']['ID'])->toBe(1);
	PropertyTable::$rows = [];
	Iblock::clearPropertiesByCodeCache();
	expect(Iblock::getPropertiesByCode(7))->toBe([])
		->and(PropertyTable::$queryCount)->toBe(2);
});
