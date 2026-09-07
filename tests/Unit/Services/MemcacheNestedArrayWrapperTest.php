<?php

declare(strict_types=1);

use Hipot\Services\MemcacheNestedArrayWrapper;

final class NestedArrayMemcacheFake extends Memcache
{
	/** @var array<string, mixed> */
	public array $values = [];

	/** @var list<string> */
	public array $getCalls = [];

	/** @var list<array{key: string, value: mixed}> */
	public array $setCalls = [];

	/** @var list<string> */
	public array $deleteCalls = [];

	public bool $setResult = true;

	public function get(array|string $key, mixed &$flags = null, mixed &$cas = null): mixed
	{
		$key = (string)$key;
		$this->getCalls[] = $key;

		return $this->values[$key] ?? false;
	}

	public function set(
		array|string $key,
		mixed $value = null,
		int $flags = 0,
		int $exptime = 0,
		int $cas = 0,
	): bool {
		$key = (string)$key;
		$this->setCalls[] = ['key' => $key, 'value' => $value];
		if ($this->setResult) {
			$this->values[$key] = $value;
		}

		return $this->setResult;
	}

	public function delete(array|string $key, int $exptime = 0): array|bool
	{
		$key = (string)$key;
		$this->deleteCalls[] = $key;
		unset($this->values[$key]);

		return true;
	}
}

it('persists nested writes made through an array reference', function (): void {
	$memcache = new NestedArrayMemcacheFake();
	$memcache->values['properties:7'] = [
		'7|ACTIVE:Y' => [10 => ['ID' => 10]],
	];
	$cache = new MemcacheNestedArrayWrapper(' properties: ', $memcache);

	$cache[7]['7|SetPropertyValuesEx'][20] = ['ID' => 20];
	$result = $cache->flush();

	expect($result)->toBeTrue()
		->and($memcache->getCalls)->toBe(['properties:7'])
		->and($memcache->values['properties:7'])->toBe([
			'7|ACTIVE:Y' => [10 => ['ID' => 10]],
			'7|SetPropertyValuesEx' => [20 => ['ID' => 20]],
		])
		->and($memcache->setCalls)->toHaveCount(1);

	$cache->flush();
	expect($memcache->setCalls)->toHaveCount(1);
});

it('supports the initialization sequence used by Bitrix', function (): void {
	$memcache = new NestedArrayMemcacheFake();
	$cache = new MemcacheNestedArrayWrapper('properties:', $memcache);

	expect(isset($cache[12]))->toBeFalse();
	$cache[12] = [];
	expect(isset($cache[12]['12|SetPropertyValuesEx']))->toBeFalse();
	$cache[12]['12|SetPropertyValuesEx'] = [0 => []];
	$cache[12]['12|SetPropertyValuesEx'][0]['CODE'] = 42;

	$cache->flush();

	expect($memcache->values['properties:12'])->toBe([
		'12|SetPropertyValuesEx' => [0 => ['CODE' => 42]],
	]);
});

it('deletes a bucket immediately and does not restore it during flush', function (): void {
	$memcache = new NestedArrayMemcacheFake();
	$memcache->values['properties:5'] = ['filter' => ['ID' => 1]];
	$cache = new MemcacheNestedArrayWrapper('properties:', $memcache);

	$loaded = $cache[5];
	unset($cache[5]);
	$cache->flush();

	expect($loaded)->toBe(['filter' => ['ID' => 1]])
		->and($memcache->deleteCalls)->toBe(['properties:5'])
		->and($memcache->values)->not->toHaveKey('properties:5')
		->and($memcache->setCalls)->toBe([]);
});

it('keeps failed buckets dirty so an explicit retry can persist them', function (): void {
	$memcache = new NestedArrayMemcacheFake();
	$memcache->setResult = false;
	$cache = new MemcacheNestedArrayWrapper('properties:', $memcache);
	$cache[3]['filter'] = ['ID' => 3];

	expect($cache->flush())->toBeFalse();

	$memcache->setResult = true;
	expect($cache->flush())->toBeTrue()
		->and($memcache->setCalls)->toHaveCount(2)
		->and($memcache->values['properties:3'])->toBe(['filter' => ['ID' => 3]]);
});

it('rejects non-array buckets and append operations', function (): void {
	$cache = new MemcacheNestedArrayWrapper('properties:', new NestedArrayMemcacheFake());

	$cache[1] = 'invalid';
})->throws(RuntimeException::class, 'A nested cache bucket must be an array');

it('rejects an append operation without a bucket key', function (): void {
	$cache = new MemcacheNestedArrayWrapper('properties:', new NestedArrayMemcacheFake());

	$cache[] = [];
})->throws(RuntimeException::class, 'Tried to set null offset');
