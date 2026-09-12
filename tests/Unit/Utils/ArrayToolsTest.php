<?php

declare(strict_types=1);

use Hipot\Utils\Helper\ArrayTools;

function arrayToolsFixture(): object
{
	return new class {
		use ArrayTools;
	};
}

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

