<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Hipot\Components\MedialibraryCollectionList;
use Hipot\Components\MedialibraryItemsList;

$fixtureCollectionId = 0;
$fixtureItemId = 0;
$fixtureFileId = 0;

beforeAll(function (): void {
	Loader::requireModule('fileman');
});

beforeEach(function () use (&$fixtureCollectionId, &$fixtureItemId, &$fixtureFileId): void {
	$fixtureCollectionId = 0;
	$fixtureItemId = 0;
	$fixtureFileId = 0;
});

afterEach(function () use (&$fixtureCollectionId, &$fixtureItemId, &$fixtureFileId): void {
	$connection = Application::getConnection();
	if ($fixtureCollectionId > 0 && $fixtureItemId > 0) {
		$connection->queryExecute(
			'DELETE FROM b_medialib_collection_item WHERE COLLECTION_ID = '
			. $fixtureCollectionId . ' AND ITEM_ID = ' . $fixtureItemId,
		);
	}
	if ($fixtureItemId > 0) {
		$connection->queryExecute('DELETE FROM b_medialib_item WHERE ID = ' . $fixtureItemId);
	}
	if ($fixtureCollectionId > 0) {
		$connection->queryExecute('DELETE FROM b_medialib_collection WHERE ID = ' . $fixtureCollectionId);
	}
	if ($fixtureFileId > 0) {
		$connection->queryExecute('DELETE FROM b_file WHERE ID = ' . $fixtureFileId);
	}
});

it('loads media items and collections through OOP components', function () use (
	&$fixtureCollectionId,
	&$fixtureItemId,
	&$fixtureFileId,
): void {
	/** @var CMain $APPLICATION */
	global $APPLICATION;

	$suffix = bin2hex(random_bytes(6));
	$connection = Application::getConnection();
	$fixtureFileId = (int)$connection->add('b_file', [
		'TIMESTAMP_X' => new DateTime(),
		'MODULE_ID' => 'fileman',
		'HEIGHT' => 0,
		'WIDTH' => 0,
		'FILE_SIZE' => 128,
		'CONTENT_TYPE' => 'application/pdf',
		'SUBDIR' => 'integration-tests',
		'FILE_NAME' => "media-{$suffix}.pdf",
		'ORIGINAL_NAME' => "media-{$suffix}.pdf",
	]);

	$fixtureCollectionId = (int)$connection->add('b_medialib_collection', [
		'NAME' => "Media collection {$suffix}",
		'DESCRIPTION' => "Media description {$suffix}",
		'ACTIVE' => 'Y',
		'DATE_UPDATE' => new DateTime(),
		'OWNER_ID' => null,
		'PARENT_ID' => 0,
		'SITE_ID' => SITE_ID,
		'KEYWORDS' => '',
		'ITEMS_COUNT' => 1,
		'ML_TYPE' => 0,
	]);
	$fixtureItemId = (int)$connection->add('b_medialib_item', [
		'NAME' => "Media item {$suffix}",
		'ITEM_TYPE' => '',
		'DESCRIPTION' => '',
		'DATE_CREATE' => new DateTime(),
		'DATE_UPDATE' => new DateTime(),
		'SOURCE_ID' => $fixtureFileId,
		'KEYWORDS' => '',
		'SEARCHABLE_CONTENT' => '',
	]);
	$connection->add('b_medialib_collection_item', [
		'COLLECTION_ID' => $fixtureCollectionId,
		'ITEM_ID' => $fixtureItemId,
	], null);

	$items = $APPLICATION->IncludeComponent(
		'hipot:medialibrary.items.list',
		'',
		[
			'COLLECTION_IDS' => $fixtureCollectionId,
			'SELECT_FILE_INFO' => 'Y',
			'ONLY_RETURN_ITEMS' => 'Y',
			'CACHE_TYPE' => 'N',
			'CACHE_TIME' => 0,
		],
		false,
		['HIDE_ICONS' => 'Y'],
		true,
	);

	ob_start();
	try {
		$collections = $APPLICATION->IncludeComponent(
			'hipot:medialibrary.collection.list',
			'docs',
			[
				'ORDER' => ['ID' => 'ASC'],
				'FILTER' => ['ID' => $fixtureCollectionId],
				'SELECT_WITH_ITEMS' => 'Y',
				'CACHE_TYPE' => 'N',
				'CACHE_TIME' => 0,
			],
			false,
			['HIDE_ICONS' => 'Y'],
			true,
		);
	} finally {
		$rendered = (string)ob_get_clean();
	}

	expect(class_exists(MedialibraryItemsList::class, false))
		->toBeTrue()
		->and(class_exists(MedialibraryCollectionList::class, false))->toBeTrue()
		->and($items['ITEMS'])->toHaveCount(1)
		->and((int)$items['ITEMS'][0]['ID'])->toBe($fixtureItemId)
		->and((int)$items['ITEMS'][0]['COLLECTION_ID'])->toBe($fixtureCollectionId)
		->and($items['ITEMS'][0]['NAME'])->toBe("Media item {$suffix}")
		->and((int)$items['arFileInfo'][$fixtureFileId]['ID'])->toBe($fixtureFileId)
		->and($items['arFileInfo'][$fixtureFileId]['CONTENT_TYPE'])->toBe('application/pdf')
		->and($collections['COLLECTIONS'])->toHaveCount(1)
		->and((int)$collections['COLLECTIONS'][0]['ID'])->toBe($fixtureCollectionId)
		->and($collections['COLLECTIONS'][0]['ITEMS'])->toHaveCount(1)
		->and((int)$collections['COLLECTIONS'][0]['ITEMS'][0]['ID'])->toBe($fixtureItemId)
		->and($rendered)->toContain("Media collection {$suffix}", "Media item {$suffix}");
});
