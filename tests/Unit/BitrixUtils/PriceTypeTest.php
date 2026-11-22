<?php

declare(strict_types=1);

use Bitrix\Catalog\GroupTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Hipot\BitrixUtils\PriceType;

require_once dirname(__DIR__, 2) . '/Stubs/PriceTypeBitrix.php';

beforeEach(function (): void {
	Loader::$modules['catalog'] = true;
	GroupTable::$queryCount = 0;
	GroupTable::$ttl = 0;
	GroupTable::$rows = [
		['ID' => '9', 'NAME' => 'RETAIL', 'BASE' => 'N', 'SORT' => '200', 'XML_ID' => 'second'],
		['ID' => '4', 'NAME' => 'RETAIL', 'BASE' => 'Y', 'SORT' => '100', 'XML_ID' => 'first'],
	];
});

afterEach(function (): void {
	unset(Loader::$modules['catalog']);
});

it('finds a price type by exact NAME with ORM cache and normalized metadata', function (): void {
	$type = PriceType::getInfoByCode(' RETAIL ');
	expect($type['ID'])->toBe(4)
		->and($type['SORT'])->toBe(100)
		->and($type['BASE'])->toBe('Y')
		->and($type['XML_ID'])->toBe('first')
		->and(GroupTable::$ttl)->toBe(PriceType::CACHE_TTL)
		->and(PriceType::getIdByCode('RETAIL'))->toBe(4)
		->and(Loader::$includedModules)->toContain('catalog');
});

it('returns null for missing and empty codes without treating wildcards as patterns', function (): void {
	expect(PriceType::getInfoByCode(' '))->toBeNull()
		->and(PriceType::getIdByCode(''))->toBeNull()
		->and(GroupTable::$queryCount)->toBe(0)
		->and(PriceType::getInfoByCode('MISSING'))->toBeNull()
		->and(PriceType::getIdByCode('RET%'))->toBeNull();
});

it('reports a missing catalog module instead of treating it as a missing price type', function (): void {
	Loader::$modules['catalog'] = false;
	expect(fn() => PriceType::getInfoByCode('RETAIL'))->toThrow(LoaderException::class)
		->and(GroupTable::$queryCount)->toBe(0);
});
