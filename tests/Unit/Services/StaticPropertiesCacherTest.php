<?php

declare(strict_types=1);

use Hipot\Services\StaticPropertiesCacher;

final class StaticPropertiesCacherFixture
{
	protected static $cache = [];
	protected $instanceCache = [];

	public static function getCache(): mixed
	{
		return self::$cache;
	}
}

it('replaces a protected static property with an array wrapper', function (): void {
	$wrapper = new ArrayObject();
	$cacher = new StaticPropertiesCacher([
		StaticPropertiesCacherFixture::class => [
			'cache' => static fn(): ArrayAccess => $wrapper,
		],
	]);

	$cacher->cache();

	expect(StaticPropertiesCacherFixture::getCache())->toBe($wrapper);
});

it('rejects instance properties', function (): void {
	$cacher = new StaticPropertiesCacher([
		StaticPropertiesCacherFixture::class => [
			'instanceCache' => static fn(): ArrayAccess => new ArrayObject(),
		],
	]);

	$cacher->cache();
})->throws(UnexpectedValueException::class);

it('rejects wrappers without array access', function (): void {
	$cacher = new StaticPropertiesCacher([
		StaticPropertiesCacherFixture::class => [
			'cache' => static fn(): object => new stdClass(),
		],
	]);

	$cacher->cache();
})->throws(UnexpectedValueException::class);
