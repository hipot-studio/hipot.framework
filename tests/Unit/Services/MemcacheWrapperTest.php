<?php

declare(strict_types=1);

use Hipot\Services\MemcacheWrapper;

final class MemcacheFake extends Memcache
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
	public bool $deleteResult = true;

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

		if ($this->deleteResult) {
			unset($this->values[$key]);
		}

		return $this->deleteResult;
	}
}

it('trims and prepends the configured key prefix', function (): void {
	$memcache = new MemcacheFake();
	$cache = new MemcacheWrapper('  articles:  ', $memcache);

	$result = $cache->offsetSet('42', ['ID' => 42]);

	expect($result)->toBeTrue()
		->and($cache->getMc())->toBe($memcache)
		->and($memcache->setCalls)->toBe([
			['key' => 'articles:42', 'value' => ['ID' => 42]],
		])
		->and($cache['42'])->toBe(['ID' => 42])
		->and($memcache->getCalls)->toBe(['articles:42']);
});

it('uses Memcache false as the missing-value marker', function (): void {
	$memcache = new MemcacheFake();
	$memcache->values = [
		'cache:zero' => 0,
		'cache:empty-string' => '',
		'cache:empty-array' => [],
		'cache:false' => false,
	];
	$cache = new MemcacheWrapper('cache:', $memcache);

	expect(isset($cache['zero']))->toBeTrue()
		->and(isset($cache['empty-string']))->toBeTrue()
		->and(isset($cache['empty-array']))->toBeTrue()
		->and(isset($cache['false']))->toBeFalse()
		->and(isset($cache['missing']))->toBeFalse();
});

it('deletes the prefixed key through array access', function (): void {
	$memcache = new MemcacheFake();
	$memcache->values['sessions:user-1'] = 'payload';
	$cache = new MemcacheWrapper('sessions:', $memcache);

	$result = $cache->offsetUnset('user-1');

	expect($result)->toBeTrue()
		->and($memcache->deleteCalls)->toBe(['sessions:user-1'])
		->and($memcache->values)->not->toHaveKey('sessions:user-1');
});

it('rejects an append operation without an explicit key', function (): void {
	$cache = new MemcacheWrapper('cache:', new MemcacheFake());

	$cache[] = 'value';
})->throws(RuntimeException::class, 'Tried to set null offset');

it('returns failures from the underlying Memcache client', function (): void {
	$memcache = new MemcacheFake();
	$memcache->setResult = false;
	$memcache->deleteResult = false;
	$cache = new MemcacheWrapper('cache:', $memcache);

	expect($cache->offsetSet('key', 'value'))->toBeFalse()
		->and($cache->offsetUnset('key'))->toBeFalse()
		->and($memcache->values)->toBe([]);
});


