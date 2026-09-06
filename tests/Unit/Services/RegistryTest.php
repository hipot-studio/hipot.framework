<?php

declare(strict_types=1);

use Hipot\Services\Registry;
use Hipot\Types\Registry as RegistryInstance;

beforeEach(function (): void {
	Registry::resetInstance();
});

afterEach(function (): void {
	Registry::resetInstance();
});

it('stores and retrieves values through the static facade', function (): void {
	$value = new stdClass();
	$value->id = 42;

	Registry::set('current_item', $value);

	expect(Registry::contains('current_item'))->toBeTrue()
		->and(Registry::get('current_item'))->toBe($value)
		->and(Registry::getInstance())->toBeInstanceOf(RegistryInstance::class);
});

it('distinguishes a stored null from an absent key', function (): void {
	Registry::set('nullable_value', null);

	expect(Registry::contains('nullable_value'))->toBeTrue()
		->and(Registry::get('nullable_value'))->toBeNull()
		->and(Registry::contains('missing_value'))->toBeFalse()
		->and(Registry::get('missing_value'))->toBeNull();
});

it('overwrites and deletes a value', function (): void {
	Registry::set('status', 'new');
	Registry::set('status', 'processed');

	expect(Registry::get('status'))->toBe('processed');

	Registry::delete('status');

	expect(Registry::contains('status'))->toBeFalse()
		->and(Registry::get('status'))->toBeNull();
});

it('creates an empty registry after resetting the singleton', function (): void {
	Registry::set('request_value', 15);
	$previousInstance = Registry::getInstance();

	Registry::resetInstance();
	$currentInstance = Registry::getInstance();

	expect($currentInstance)->not->toBe($previousInstance)
		->and(Registry::contains('request_value'))->toBeFalse();
});
