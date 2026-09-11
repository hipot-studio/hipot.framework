<?php

declare(strict_types=1);

use Hipot\Types\Collection\Collection;

it('returns null for an empty collection', function (): void {
	$collection = new Collection();

	expect($collection->lastKey())->toBeNull();
});

it('returns the last numeric key', function (): void {
	$collection = new Collection([
		10 => 'first',
		20 => 'second',
	]);

	expect($collection->lastKey())->toBe(20);
});

it('returns the last string key', function (): void {
	$collection = new Collection([
		'first' => 10,
		'second' => 20,
	]);

	expect($collection->lastKey())->toBe('second');
});

it('reflects appends and removals', function (): void {
	$collection = new Collection(['first', 'second']);

	expect($collection->lastKey())->toBe(1);

	$collection->append('third');
	expect($collection->lastKey())->toBe(2);

	$collection->offsetUnset(2);
	expect($collection->lastKey())->toBe(1);
});

it('reports whether the collection is empty', function (): void {
	$collection = new Collection();

	expect($collection->isEmpty())->toBeTrue();

	$collection['item'] = null;
	expect($collection->isEmpty())->toBeFalse();
});

it('returns null for the first and last values of an empty collection', function (): void {
	$collection = new Collection();

	expect($collection->first())->toBeNull()
		->and($collection->last())->toBeNull();
});

it('returns the first and last values with numeric keys', function (): void {
	$collection = new Collection([
		10 => 'first',
		20 => 'last',
	]);

	expect($collection->first())->toBe('first')
		->and($collection->last())->toBe('last');
});

it('returns the first and last values with string keys', function (): void {
	$collection = new Collection([
		'alpha' => 'first',
		'omega' => 'last',
	]);

	expect($collection->first())->toBe('first')
		->and($collection->last())->toBe('last');
});
