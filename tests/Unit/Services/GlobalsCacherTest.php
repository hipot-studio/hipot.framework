<?php

declare(strict_types=1);

use Hipot\Services\GlobalsCacher;

afterEach(function (): void {
	unset($GLOBALS['TEST_GLOBAL_ONE'], $GLOBALS['TEST_GLOBAL_TWO']);
});

it('initializes each global before creating and installing its wrapper', function (): void {
	$calls = [];
	$firstWrapper = new ArrayObject();
	$secondWrapper = new ArrayObject();
	$cacher = new GlobalsCacher([
		'TEST_GLOBAL_ONE' => [
			function () use (&$calls): void {
				$calls[] = 'initialize-one';
				$GLOBALS['TEST_GLOBAL_ONE'] = [];
			},
			function () use (&$calls, $firstWrapper): ArrayAccess {
				$calls[] = 'factory-one';

				return $firstWrapper;
			},
		],
		'TEST_GLOBAL_TWO' => [
			function () use (&$calls): void {
				$calls[] = 'initialize-two';
				$GLOBALS['TEST_GLOBAL_TWO'] = [];
			},
			function () use (&$calls, $secondWrapper): ArrayAccess {
				$calls[] = 'factory-two';

				return $secondWrapper;
			},
		],
	]);

	$cacher->cache();

	expect($calls)->toBe([
		'initialize-one',
		'factory-one',
		'initialize-two',
		'factory-two',
	])
		->and($GLOBALS['TEST_GLOBAL_ONE'])->toBe($firstWrapper)
		->and($GLOBALS['TEST_GLOBAL_TWO'])->toBe($secondWrapper);
});

it('rejects a wrapper factory result without ArrayAccess', function (): void {
	$cacher = new GlobalsCacher([
		'TEST_GLOBAL_ONE' => [
			static function (): void {},
			static fn(): object => new stdClass(),
		],
	]);

	$cacher->cache();
})->throws(UnexpectedValueException::class, 'must return an ArrayAccess instance');

it('validates global configurations in the constructor', function (): void {
	new GlobalsCacher([
		'TEST_GLOBAL_ONE' => [static function (): void {}],
	]);
})->throws(InvalidArgumentException::class);
