<?php

declare(strict_types=1);

use Bitrix\Main\Data\ManagedCache;
use Hipot\Services\ManagedCacheArrayWrapper;

final class ManagedCacheArrayWrapperFake extends ManagedCache
{
	/** @var array<string, mixed> */
	public array $values = [];

	/** @var list<array{0: string, 1: string|false}> */
	public array $cleaned = [];

	public function read($ttl, $uniqueId, $tableId = false): bool
	{
		return array_key_exists((string)$uniqueId, $this->values);
	}

	public function get($uniqueId): mixed
	{
		return $this->values[(string)$uniqueId] ?? false;
	}

	public function set($uniqueId, $value): void
	{
		$this->values[(string)$uniqueId] = $value;
	}

	public function clean($uniqueId, $tableId = false): void
	{
		$this->cleaned[] = [(string)$uniqueId, $tableId];
		unset($this->values[(string)$uniqueId]);
	}
}

it('stores false as an existing array value', function (): void {
	$managedCache = new ManagedCacheArrayWrapperFake();
	$cache = new ManagedCacheArrayWrapper('sku.offer.', $managedCache, 3600, 'orm_b_catalog_iblock');

	$cache[42] = false;

	expect(isset($cache[42]))->toBeTrue()
		->and($cache[42])->toBeFalse()
		->and(isset($cache[43]))->toBeFalse()
		->and($cache[43])->toBeNull();
});

it('uses the configured prefix and managed cache directory', function (): void {
	$managedCache = new ManagedCacheArrayWrapperFake();
	$cache = new ManagedCacheArrayWrapper('sku.product.', $managedCache, 3600, 'orm_b_catalog_iblock');

	$cache['12'] = ['IBLOCK_ID' => 12];
	unset($cache['12']);

	expect($managedCache->cleaned)->toBe([
		['sku.product.12', 'orm_b_catalog_iblock'],
	]);
});

it('rejects append writes because cache entries require stable keys', function (): void {
	$cache = new ManagedCacheArrayWrapper('sku.', new ManagedCacheArrayWrapperFake(), 3600);

	$cache[] = 'value';
})->throws(RuntimeException::class);
