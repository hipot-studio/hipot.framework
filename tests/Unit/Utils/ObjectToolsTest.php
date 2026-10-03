<?php

declare(strict_types=1);

use Hipot\Utils\Helper\ObjectTools;

trait ObjectToolsFactoryFixtureTrait
{
	public function fixtureValue(): string
	{
		return 'fixture value';
	}
}

function objectToolsFixture(): object
{
	return new class {
		use ObjectTools;
	};
}

class ObjectToolsParentFixture
{
	private string $inheritedPrivate = 'parent private';

	protected string $inheritedProtected = 'parent protected';
}

class ObjectToolsStaticFixture
{
	private static string $value = 'initial';

	public static function value(): string
	{
		return self::$value;
	}

	public static function reset(): void
	{
		self::$value = 'initial';
	}
}

beforeEach(function (): void {
	ObjectToolsStaticFixture::reset();
});

it('creates separate objects of one anonymous class from a trait name', function (): void {
	$first = objectToolsFixture()::createObjectFromTrait(ObjectToolsFactoryFixtureTrait::class);
	$second = objectToolsFixture()::createObjectFromTrait(ObjectToolsFactoryFixtureTrait::class);

	expect($first)->not->toBe($second)
		->and($first::class)->toBe($second::class)
		->and($first->fixtureValue())->toBe('fixture value');
});

it('rejects a class name instead of a trait name', function (): void {
	objectToolsFixture()::createObjectFromTrait(ObjectToolsStaticFixture::class);
})->throws(InvalidArgumentException::class, "The trait 'ObjectToolsStaticFixture' was not found.");

it('rejects an unknown trait name', function (): void {
	objectToolsFixture()::createObjectFromTrait('MissingTrait');
})->throws(InvalidArgumentException::class, "The trait 'MissingTrait' was not found.");

it('gets and sets private and protected object properties', function (): void {
	$target = new class {
		private string $privateValue = 'private';

		protected int $protectedValue = 10;
	};
	$utils = objectToolsFixture();

	expect($utils::getPrivateProperty($target, 'privateValue'))->toBe('private')
		->and($utils::getPrivateProperty($target, 'protectedValue'))->toBe(10);

	$utils::setPrivateProperty($target, 'privateValue', 'changed');
	$utils::setPrivateProperty($target, 'protectedValue', 42);

	expect($utils::getPrivateProperty($target, 'privateValue'))->toBe('changed')
		->and($utils::getPrivateProperty($target, 'protectedValue'))->toBe(42);
});

it('finds private and protected properties declared by a parent class', function (): void {
	$target = new class extends ObjectToolsParentFixture {};
	$utils = objectToolsFixture();

	expect($utils::getPrivateProperty($target, 'inheritedPrivate'))->toBe('parent private')
		->and($utils::getPrivateProperty($target, 'inheritedProtected'))->toBe('parent protected');

	$utils::setPrivateProperty($target, 'inheritedPrivate', 'changed private');
	$utils::setPrivateProperty($target, 'inheritedProtected', 'changed protected');

	expect($utils::getPrivateProperty($target, 'inheritedPrivate'))->toBe('changed private')
		->and($utils::getPrivateProperty($target, 'inheritedProtected'))->toBe('changed protected');
});

it('gets and sets a private static property by class name', function (): void {
	$utils = objectToolsFixture();

	expect($utils::getPrivateProperty(ObjectToolsStaticFixture::class, 'value'))->toBe('initial');

	$utils::setPrivateProperty(ObjectToolsStaticFixture::class, 'value', 'changed');

	expect(ObjectToolsStaticFixture::value())->toBe('changed')
		->and($utils::getPrivateProperty(ObjectToolsStaticFixture::class, 'value'))->toBe('changed');
});

it('reports a missing property', function (): void {
	objectToolsFixture()::getPrivateProperty(new stdClass(), 'missing');
})->throws(RuntimeException::class, "The property 'missing' was not found.");
