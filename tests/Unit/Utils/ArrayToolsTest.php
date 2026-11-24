<?php

declare(strict_types=1);

use Hipot\Utils\Helper\ArrayTools;
use Hipot\Utils\UUtils;

function arrayToolsFixture(): object
{
	return UUtils::createObjectFromTrait(ArrayTools::class);
}

it('removes blank values recursively while preserving keys and empty branches', function (): void {
	$result = arrayToolsFixture()::trimRecursive([
		'blank'  => ' ',
		'branch' => [null, ' value ', false, 0],
		'empty'  => [null],
	]);

	expect($result)->toBe([
		'branch' => [1 => ' value ', 3 => 0],
		'empty'  => [],
	]);
});

it('resolves dot paths and preserves direct key precedence and null values', function (): void {
	$tools = arrayToolsFixture();
	$data = [
		'user'      => ['id' => 7, 'name' => 'Alice', 'email' => null],
		'user.id'   => 0,
		'user.name' => null,
	];

	expect($tools::getDot($data, null))->toBe($data)
		->and($tools::getDot($data, 'user.id', -1))->toBe(0)
		->and($tools::getDot($data, 'user.name'))->toBe('Alice')
		->and($tools::getDot($data, 'user.email', 'default'))->toBeNull()
		->and($tools::getDot($data, 'user.email.domain', 'default'))->toBe('default');
});

it('flattens nested arrays and preserves list indexes', function (): void {
	$result = arrayToolsFixture()::flatten([
		'user' => [
			'name' => 'Alice',
			'roles' => ['admin', 'editor'],
		],
		'enabled' => true,
	]);

	expect($result)->toBe([
		'user.name' => 'Alice',
		'user.roles.0' => 'admin',
		'user.roles.1' => 'editor',
		'enabled' => true,
	]);
});

it('unflattens compound keys to arbitrary depth', function (): void {
	$result = arrayToolsFixture()::unflatten([
		'form.address.city' => 'Bucharest',
		'form.address.zip' => '010101',
		'form.tags.0' => 'php',
		'form.tags.1' => 'bitrix',
	]);

	expect($result)->toBe([
		'form' => [
			'address' => [
				'city' => 'Bucharest',
				'zip' => '010101',
			],
			'tags' => ['php', 'bitrix'],
		],
	]);
});

it('supports a custom separator and prefix', function (): void {
	$flat = arrayToolsFixture()::flatten(
		['name' => 'Alice', 'contacts' => ['email' => 'alice@example.com']],
		'/',
		'user',
	);

	expect($flat)->toBe([
		'user/name' => 'Alice',
		'user/contacts/email' => 'alice@example.com',
	])->and(arrayToolsFixture()::unflatten($flat, '/'))->toBe([
		'user' => [
			'name' => 'Alice',
			'contacts' => ['email' => 'alice@example.com'],
		],
	]);
});

it('preserves empty nested arrays during a round trip', function (): void {
	$source = [
		'filters' => [],
		'page' => ['items' => []],
	];

	expect(arrayToolsFixture()::unflatten(arrayToolsFixture()::flatten($source)))
		->toBe($source);
});

it('handles empty input', function (): void {
	expect(arrayToolsFixture()::flatten([]))->toBe([])
		->and(arrayToolsFixture()::unflatten([]))->toBe([]);
});

it('rejects an empty separator', function (): void {
	arrayToolsFixture()::flatten(['key' => 'value'], '');
})->throws(InvalidArgumentException::class);

