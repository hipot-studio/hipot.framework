<?php

declare(strict_types=1);

use Hipot\Services\ApcuNestedArrayWrapper;

function apcuNestedArrayPrefix(): string
{
	return 'hipot-test-' . bin2hex(random_bytes(8)) . ':';
}

it('persists nested writes between wrapper instances', function (): void {
	$prefix = apcuNestedArrayPrefix();
	$first = new ApcuNestedArrayWrapper($prefix);
	$first[7]['7|SetPropertyValuesEx'][20] = ['ID' => 20];

	expect($first->flush())->toBeTrue();

	$second = new ApcuNestedArrayWrapper($prefix);
	expect($second[7])->toBe([
		'7|SetPropertyValuesEx' => [20 => ['ID' => 20]],
	]);

	apcu_delete($prefix . '7');
})->skip(fn(): bool => !ApcuNestedArrayWrapper::isAvailable(), 'APCu is unavailable');

it('supports the initialization sequence used by Bitrix', function (): void {
	$prefix = apcuNestedArrayPrefix();
	$cache = new ApcuNestedArrayWrapper($prefix);

	expect(isset($cache[12]))->toBeFalse();
	$cache[12] = [];
	expect(isset($cache[12]['12|SetPropertyValuesEx']))->toBeFalse();
	$cache[12]['12|SetPropertyValuesEx'] = [0 => []];
	$cache[12]['12|SetPropertyValuesEx'][0]['CODE'] = 42;

	expect($cache->flush())->toBeTrue()
		->and(apcu_fetch($prefix . '12'))->toBe([
			'12|SetPropertyValuesEx' => [0 => ['CODE' => 42]],
		]);

	apcu_delete($prefix . '12');
})->skip(fn(): bool => !ApcuNestedArrayWrapper::isAvailable(), 'APCu is unavailable');

it('keeps the first bucket published by concurrent builders', function (): void {
	$prefix = apcuNestedArrayPrefix();
	$first = new ApcuNestedArrayWrapper($prefix);
	$second = new ApcuNestedArrayWrapper($prefix);

	expect(isset($first[3]))->toBeFalse()
		->and(isset($second[3]))->toBeFalse();

	$first[3]['property'] = ['ID' => 1];
	$second[3]['property'] = ['ID' => 2];

	expect($first->flush())->toBeTrue()
		->and($second->flush())->toBeTrue()
		->and(apcu_fetch($prefix . '3'))->toBe(['property' => ['ID' => 1]]);

	apcu_delete($prefix . '3');
})->skip(fn(): bool => !ApcuNestedArrayWrapper::isAvailable(), 'APCu is unavailable');

it('does not rewrite an unchanged bucket after reading it by reference', function (): void {
	$prefix = apcuNestedArrayPrefix();
	apcu_store($prefix . '4', ['property' => ['ID' => 1]]);
	$cache = new ApcuNestedArrayWrapper($prefix);

	$loaded = $cache[4];
	apcu_store($prefix . '4', ['property' => ['ID' => 2]]);

	expect($cache->flush())->toBeTrue()
		->and($loaded)->toBe(['property' => ['ID' => 1]])
		->and(apcu_fetch($prefix . '4'))->toBe(['property' => ['ID' => 2]]);

	apcu_delete($prefix . '4');
})->skip(fn(): bool => !ApcuNestedArrayWrapper::isAvailable(), 'APCu is unavailable');

it('deletes a bucket immediately and does not restore it during flush', function (): void {
	$prefix = apcuNestedArrayPrefix();
	apcu_store($prefix . '5', ['filter' => ['ID' => 1]]);
	$cache = new ApcuNestedArrayWrapper($prefix);

	$loaded = $cache[5];
	unset($cache[5]);

	expect($cache->flush())->toBeTrue()
		->and($loaded)->toBe(['filter' => ['ID' => 1]])
		->and(apcu_exists($prefix . '5'))->toBeFalse();
})->skip(fn(): bool => !ApcuNestedArrayWrapper::isAvailable(), 'APCu is unavailable');

it('rejects non-array buckets and append operations', function (): void {
	$cache = new ApcuNestedArrayWrapper(apcuNestedArrayPrefix());
	$cache[1] = 'invalid';
})->throws(RuntimeException::class, 'A nested cache bucket must be an array');

it('rejects an append operation without a bucket key', function (): void {
	$cache = new ApcuNestedArrayWrapper(apcuNestedArrayPrefix());
	$cache[] = [];
})->throws(RuntimeException::class, 'Tried to set null offset');
