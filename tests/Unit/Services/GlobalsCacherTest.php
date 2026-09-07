<?php

declare(strict_types=1);

use Hipot\Services\GlobalsCacher;
use Hipot\Services\MemcacheWrapper;

final class GlobalsCacherMemcacheFake extends Memcache
{
	/** @var array<string, mixed> */
	public array $values = [];

	public function get(array|string $key, mixed &$flags = null, mixed &$cas = null): mixed
	{
		return $this->values[(string)$key] ?? false;
	}

	public function set(
		array|string $key,
		mixed $value = null,
		int $flags = 0,
		int $exptime = 0,
		int $cas = 0,
	): bool {
		$this->values[(string)$key] = $value;

		return true;
	}
}

afterEach(function (): void {
	unset($GLOBALS['TEST_GLOBAL_ONE'], $GLOBALS['TEST_GLOBAL_TWO']);
});

it('initializes each global before replacing it with a cache wrapper', function (): void {
	$connection = new stdClass();
	$calls = [];

	$cacher = new GlobalsCacher(
		$connection,
		[
			'TEST_GLOBAL_ONE' => [
				function () use (&$calls): void {
					$calls[] = 'initialize-one';
					$GLOBALS['TEST_GLOBAL_ONE'] = [];
				},
				function () use (&$calls): string {
					$calls[] = 'prefix-one';
					return 'one:';
				},
			],
			'TEST_GLOBAL_TWO' => [
				function () use (&$calls): void {
					$calls[] = 'initialize-two';
					$GLOBALS['TEST_GLOBAL_TWO'] = [];
				},
				static fn(): string => 'two:',
			],
		],
		function (string $prefix, object $receivedConnection) use (&$calls, $connection): ArrayAccess {
			$calls[] = 'factory-' . $prefix;
			expect($receivedConnection)->toBe($connection);

			return new ArrayObject();
		},
	);

	$cacher->cache();

	expect($calls)->toBe([
		'initialize-one',
		'prefix-one',
		'factory-one:',
		'initialize-two',
		'factory-two:',
	])
		->and($GLOBALS['TEST_GLOBAL_ONE'])->toBeInstanceOf(ArrayObject::class)
		->and($GLOBALS['TEST_GLOBAL_TWO'])->toBeInstanceOf(ArrayObject::class);
});

it('creates MemcacheWrapper by default', function (): void {
	$memcache = new GlobalsCacherMemcacheFake();
	$cacher = new GlobalsCacher(
		$memcache,
		[
			'TEST_GLOBAL_ONE' => [
				static function (): void {},
				static fn(): string => 'global:',
			],
		],
	);

	$cacher->cache();
	$GLOBALS['TEST_GLOBAL_ONE']['key'] = 'value';

	expect($GLOBALS['TEST_GLOBAL_ONE'])->toBeInstanceOf(MemcacheWrapper::class)
		->and($memcache->values)->toBe(['global:key' => 'value']);
});

it('validates global configurations in the constructor', function (): void {
	new GlobalsCacher(
		new stdClass(),
		['TEST_GLOBAL_ONE' => [static function (): void {}]],
		static fn(): ArrayAccess => new ArrayObject(),
	);
})->throws(InvalidArgumentException::class);
