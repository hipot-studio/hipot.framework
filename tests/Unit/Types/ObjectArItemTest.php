<?php

declare(strict_types=1);

use Hipot\Types\ObjectArItem;

final class ObjectArItemChild extends ObjectArItem
{
}

it('creates an empty array-like object', function (): void {
	$item = ObjectArItem::create();

	expect($item)->toBeInstanceOf(ArrayAccess::class)
		->and($item)->toBeInstanceOf(Countable::class)
		->and($item)->toBeInstanceOf(IteratorAggregate::class)
		->and($item->toArray())->toBe([])
		->and(count($item))->toBe(0);
});

it('shares values between array and property access', function (): void {
	$item = ObjectArItem::create();
	$item['ID'] = 42;
	$item->NAME = 'Article';

	expect($item->ID)->toBe(42)
		->and($item['NAME'])->toBe('Article')
		->and(isset($item['ID']))->toBeTrue()
		->and(isset($item->NAME))->toBeTrue();

	unset($item['ID'], $item->NAME);

	expect(isset($item->ID))->toBeFalse()
		->and(isset($item['NAME']))->toBeFalse()
		->and($item->ID)->toBeNull()
		->and(count($item))->toBe(0);
});

it('recursively converts nested arrays into ObjectArItem instances', function (): void {
	$item = ObjectArItem::fromArr([
		'ID' => 42,
		'META' => [
			'AUTHOR' => 'Editor',
		],
		'TAGS' => [
			['ID' => 1, 'NAME' => 'PHP'],
			['ID' => 2, 'NAME' => 'Bitrix'],
		],
	]);

	expect($item['META'])->toBeInstanceOf(ObjectArItem::class)
		->and($item->META->AUTHOR)->toBe('Editor')
		->and($item->TAGS)->toBeInstanceOf(ObjectArItem::class)
		->and($item->TAGS[0])->toBeInstanceOf(ObjectArItem::class)
		->and($item->TAGS[0]->NAME)->toBe('PHP')
		->and($item->TAGS[1]->ID)->toBe(2);
});

it('returns a shallow array representation', function (): void {
	$item = ObjectArItem::fromArr([
		'ID' => 42,
		'META' => ['ACTIVE' => true],
	]);

	$data = $item->toArray();

	expect($data['ID'])->toBe(42)
		->and($data['META'])->toBeInstanceOf(ObjectArItem::class)
		->and($data['META']['ACTIVE'])->toBeTrue();
});

it('recursively converts ordinary objects and arrays to arrays', function (): void {
	$source = (object)[
		'ID' => 42,
		'META' => (object)[
			'ACTIVE' => true,
			'TAGS' => ['php', 'bitrix'],
		],
	];

	expect(ObjectArItem::toArr($source))->toBe([
		'ID' => 42,
		'META' => [
			'ACTIVE' => true,
			'TAGS' => ['php', 'bitrix'],
		],
	])->and(ObjectArItem::toArr('unchanged'))->toBe('unchanged');
});

it('iterates values in their insertion order', function (): void {
	$item = ObjectArItem::fromArr([
		'ID' => 42,
		'NAME' => 'Article',
	]);

	expect(iterator_to_array($item))->toBe([
		'ID' => 42,
		'NAME' => 'Article',
	]);
});

it('sets existing values to null when the container is reset', function (): void {
	$item = ObjectArItem::fromArr([
		'ID' => 42,
		'NAME' => null,
	]);

	$item->resetArray();

	expect($item->toArray())->toBe([
		'ID' => null,
		'NAME' => null,
	])->and(count($item))->toBe(2)
		->and(isset($item->ID))->toBeTrue()
		->and(isset($item['NAME']))->toBeTrue();
});

it('does not create missing values when magic chain is disabled', function (): void {
	$item = ObjectArItem::create();

	expect($item->MISSING)->toBeNull()
		->and($item->toArray())->toBe([]);
});

it('creates one missing intermediate object when magic chain is enabled', function (): void {
	$item = ObjectArItem::create(useMagicChain: true);

	expect(isset($item->VIEW))->toBeFalse()
		->and(count($item))->toBe(0);

	$item->VIEW->TITLE = 'Product card';

	expect($item->VIEW)->toBeInstanceOf(ObjectArItem::class)
		->and($item->VIEW->TITLE)->toBe('Product card')
		->and(count($item))->toBe(1);
});

it('honors the static return type when called through a subclass', function (): void {
	$empty = ObjectArItemChild::create();
	$filled = ObjectArItemChild::fromArr(['ID' => 42]);

	expect($empty)->toBeInstanceOf(ObjectArItemChild::class)
		->and($filled)->toBeInstanceOf(ObjectArItemChild::class)
		->and($filled->ID)->toBe(42);
});

