<?php

declare(strict_types=1);

use Bitrix\Main\GroupTable;
use Hipot\BitrixUtils\UserGroup;

require_once dirname(__DIR__, 2) . '/Stubs/UserGroupBitrix.php';

beforeEach(function (): void {
	GroupTable::$queryCount = 0;
	GroupTable::$ttl = 0;
	GroupTable::$rows = [
		['ID' => '9', 'STRING_ID' => 'EDITORS', 'NAME' => 'Second', 'ACTIVE' => 'Y', 'C_SORT' => '200'],
		['ID' => '4', 'STRING_ID' => 'EDITORS', 'NAME' => 'First', 'ACTIVE' => 'N', 'C_SORT' => '100'],
		['ID' => '2', 'STRING_ID' => 'OTHER', 'NAME' => 'Other', 'ACTIVE' => 'Y', 'C_SORT' => '10'],
	];
});

it('looks up exact STRING_ID with trimming, deterministic ordering and ORM cache', function (): void {
	$group = UserGroup::getInfoByCode(' EDITORS ');
	expect($group['ID'])->toBe(4)
		->and($group['C_SORT'])->toBe(100)
		->and($group['NAME'])->toBe('First')
		->and($group['ACTIVE'])->toBe('N')
		->and(GroupTable::$ttl)->toBe(UserGroup::CACHE_TTL)
		->and(UserGroup::getIdByCode('EDITORS'))->toBe(4);
});

it('returns null for missing groups and treats wildcard characters literally', function (): void {
	expect(UserGroup::getInfoByCode('MISSING'))->toBeNull()
		->and(UserGroup::getIdByCode('EDIT%'))->toBeNull();
});

it('does not query for empty codes', function (): void {
	expect(UserGroup::getInfoByCode('  '))->toBeNull()
		->and(UserGroup::getIdByCode(''))->toBeNull()
		->and(GroupTable::$queryCount)->toBe(0);
});
