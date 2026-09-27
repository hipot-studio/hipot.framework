<?php

declare(strict_types=1);

use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\UserFieldTable;
use Hipot\BitrixUtils\HiBlock;
use Hipot\BitrixUtils\HiBlockApps;
use Hipot\Types\Enums\UserFieldTypes;

require_once dirname(__DIR__, 2) . '/Stubs/HiBlockBitrix.php';

beforeEach(function (): void {
	HighloadBlockTable::$rows = [
		3 => ['ID' => 3, 'NAME' => 'Countries', 'TABLE_NAME' => 'b_hlbd_countries'],
		8 => ['ID' => 8, 'NAME' => 'Cities', 'TABLE_NAME' => 'b_hlbd_cities'],
	];
	HighloadBlockTable::$lastPrimaryCall = [];
	HighloadBlockTable::$lastListQuery = [];
	HighloadBlockTable::$lastAddedFields = [];
	HighloadBlockTable::$nextId = 100;
	HighloadBlockLangTable::$rows = [];
	HighloadBlockLangTable::$lastListQuery = [];
	HighloadBlockLangTable::$lastAddedFields = [];
	UserFieldTable::$rows = [];
	UserFieldTable::$lastQuery = [];
	CUserTypeEntity::$listRows = [];
	CUserTypeEntity::$lastListOrder = [];
	CUserTypeEntity::$lastListFilter = [];
	CUserTypeEntity::$lastAddedFields = [];
	CUserTypeEntity::$addedFields = [];
	CUserTypeEntity::$deletedIds = [];
	CUserTypeEntity::$addResult = 501;
	CUserFieldEnum::$rows = [];
});

it('loads the highloadblock module', function (): void {
	class_exists(HiBlock::class);
	expect(Loader::$includedModules)->toContain('highloadblock');
});

it('finds a highload block by id with the configured cache ttl', function (): void {
	$row = HiBlock::getHightloadBlockTable(3, 'Ignored');

	expect($row)->toBe(HighloadBlockTable::$rows[3])
		->and(HighloadBlockTable::$lastPrimaryCall)->toBe([
			'id' => 3,
			'parameters' => ['cache' => ['ttl' => HiBlock::CACHE_TTL]],
		]);
});

it('finds a highload block by name or returns null without lookup criteria', function (): void {
	expect(HiBlock::getHightloadBlockTable(0, ' Cities '))->toBeFalse()
		->and(HiBlock::getHightloadBlockTable(0, 'Cities'))->toBe(HighloadBlockTable::$rows[8])
		->and(HighloadBlockTable::$lastListQuery)->toBe([
			'filter' => ['=NAME' => 'Cities'],
			'cache' => ['ttl' => HiBlock::CACHE_TTL],
		])
		->and(HiBlock::getHightloadBlockTable(0, ''))->toBeNull();
});

it('returns compiled data managers and rejects empty identifiers', function (): void {
	expect(HiBlock::getDataManagerByHiId(3))->toBe(HighloadBlockTable::$compiledDataClass)
		->and(HiBlock::getDataManagerByHiId('0'))->toBeFalse()
		->and(HiBlock::getDataManagerByHiCode('Cities'))->toBe(HighloadBlockTable::$compiledDataClass)
		->and(HiBlock::getDataManagerByHiCode('  '))->toBeFalse();
});

it('adds a highload block with additional fields', function (): void {
	$result = HiBlock::addHiBlock('Events', 'b_hlbd_events', ['LANG' => ['en' => ['NAME' => 'Events']]]);

	expect($result)->toBeInstanceOf(AddResult::class)
		->and($result->getId())->toBe(100)
		->and(HighloadBlockTable::$lastAddedFields)->toBe([
			'NAME' => 'Events',
			'TABLE_NAME' => 'b_hlbd_events',
			'LANG' => ['en' => ['NAME' => 'Events']],
		]);
});

it('adds a string user field with defaults and localized labels', function (): void {
	$fieldId = HiBlock::addHiBlockField([
		'HLBLOCK_ID' => 3,
		'CODE' => 'TITLE',
		'SORT' => 200,
		'REQUIRED' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'NAME' => '',
		'HELP' => 'Displayed title',
	]);

	expect($fieldId)->toBe(501)
		->and(CUserTypeEntity::$lastAddedFields)->toMatchArray([
			'ENTITY_ID' => 'HLBLOCK_3',
			'FIELD_NAME' => 'UF_TITLE',
			'USER_TYPE_ID' => 'string',
			'SORT' => 200,
			'MANDATORY' => 'Y',
			'IS_SEARCHABLE' => 'N',
			'SETTINGS' => ['SIZE' => 60, 'ROWS' => 2],
			'EDIT_FORM_LABEL' => ['en' => 'TITLE'],
			'HELP_MESSAGE' => ['en' => 'Displayed title'],
		]);
});

it('keeps explicitly supplied user-field settings and name', function (): void {
	HiBlock::addHiBlockField([
		'HLBLOCK_ID' => 8,
		'CODE' => 'DESCRIPTION',
		'SORT' => 300,
		'REQUIRED' => 'N',
		'IS_SEARCHABLE' => 'Y',
		'SETTINGS' => ['SIZE' => 90, 'ROWS' => 5],
		'NAME' => 'Description',
		'HELP' => '',
	]);

	expect(CUserTypeEntity::$lastAddedFields['SETTINGS'])->toBe(['SIZE' => 90, 'ROWS' => 5])
		->and(CUserTypeEntity::$lastAddedFields['EDIT_FORM_LABEL'])->toBe(['en' => 'Description']);
});

it('adds a user field with an enum type and custom list settings', function (): void {
	HiBlock::addHiBlockField([
		'HLBLOCK_ID' => 8,
		'CODE' => 'PRIORITY',
		'TYPE' => UserFieldTypes::INTEGER,
		'MULTIPLE' => 'Y',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'N',
	]);

	expect(CUserTypeEntity::$lastAddedFields)->toMatchArray([
		'USER_TYPE_ID' => 'integer',
		'SORT' => 500,
		'MULTIPLE' => 'Y',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'N',
		'IS_SEARCHABLE' => 'N',
		'EDIT_FORM_LABEL' => ['en' => 'PRIORITY'],
		'HELP_MESSAGE' => ['en' => ''],
	]);
});

it('installs a configured highload block and returns field ids by code', function (): void {
	$fieldIds = HiBlockApps::installHiBlock(
		'MigrationExample',
		'hi_migration_example',
		[
			['CODE' => 'TITLE', 'NAME' => 'Title'],
			['CODE' => 'SORT', 'TYPE' => UserFieldTypes::INTEGER],
		],
		'Migration example',
	);

	expect($fieldIds)->toBe(['TITLE' => 501, 'SORT' => 501])
		->and(HighloadBlockTable::$lastAddedFields)->toBe([
			'NAME' => 'MigrationExample',
			'TABLE_NAME' => 'hi_migration_example',
		])
		->and(HighloadBlockLangTable::$lastAddedFields)->toBe([
			'ID' => 100,
			'LID' => 'en',
			'NAME' => 'Migration example',
		])
		->and(CUserTypeEntity::$addedFields)->toHaveCount(2)
		->and(CUserTypeEntity::$addedFields[0]['FIELD_NAME'])->toBe('UF_TITLE')
		->and(CUserTypeEntity::$addedFields[0]['USER_TYPE_ID'])->toBe('string')
		->and(CUserTypeEntity::$addedFields[1]['FIELD_NAME'])->toBe('UF_SORT')
		->and(CUserTypeEntity::$addedFields[1]['USER_TYPE_ID'])->toBe('integer');
});

it('builds custom settings through the universal installer', function (): void {
	$fieldIds = HiBlockApps::installCustomSettingsHiBlock();

	expect(array_keys($fieldIds))->toBe(['NAME', 'CODE', 'VALUE'])
		->and(HighloadBlockTable::$lastAddedFields['NAME'])->toBe(HiBlockApps::CS_HIBLOCK_NAME)
		->and(HighloadBlockTable::$lastAddedFields['TABLE_NAME'])->toBe(HiBlockApps::CS_TABLE_NAME)
		->and(HighloadBlockLangTable::$lastAddedFields['NAME'])->toBe('Различные настройки сайта')
		->and(CUserTypeEntity::$addedFields)->toHaveCount(3);
});

it('returns false when Bitrix cannot create a user field', function (): void {
	CUserTypeEntity::$addResult = false;

	$result = HiBlock::addHiBlockField([
		'HLBLOCK_ID' => 3,
		'CODE' => 'FAILED',
		'SORT' => 400,
		'REQUIRED' => 'N',
		'IS_SEARCHABLE' => 'N',
		'NAME' => 'Failed field',
		'HELP' => '',
	]);

	expect($result)->toBeFalse()
		->and(CUserTypeEntity::$lastAddedFields['ENTITY_ID'])->toBe('HLBLOCK_3')
		->and(CUserTypeEntity::$lastAddedFields['FIELD_NAME'])->toBe('UF_FAILED');
});

it('deletes an existing user field and ignores a missing one', function (): void {
	UserFieldTable::$rows = [['ID' => 77, 'ENTITY_ID' => 'HLBLOCK_3', 'FIELD_NAME' => 'UF_TITLE']];
	HiBlock::deleteUserField(3, 'UF_TITLE');

	expect(UserFieldTable::$lastQuery)->toBe([
		'filter' => ['=ENTITY_ID' => 'HLBLOCK_3', '=FIELD_NAME' => 'UF_TITLE'],
		'select' => ['ID'],
		'limit' => 1,
	])->and(CUserTypeEntity::$deletedIds)->toBe([77]);

	UserFieldTable::$rows = [];
	HiBlock::deleteUserField(3, 'UF_MISSING');
	expect(CUserTypeEntity::$deletedIds)->toBe([77]);
});

it('lists blocks with localization properties enums and cache parameters', function (): void {
	HighloadBlockLangTable::$rows = [
		['ID' => 3, 'LID' => 'en', 'NAME' => 'Localized countries'],
	];
	CUserTypeEntity::$listRows = [
		['ID' => 41, 'ENTITY_ID' => 'HLBLOCK_3', 'FIELD_NAME' => 'UF_STATUS', 'USER_TYPE_ID' => 'enumeration'],
		['ID' => 42, 'ENTITY_ID' => 'HLBLOCK_8', 'FIELD_NAME' => 'UF_TITLE', 'USER_TYPE_ID' => 'string'],
	];
	CUserFieldEnum::$rows = [
		['ID' => 2, 'VALUE' => 'Active'],
		['ID' => 4, 'VALUE' => 'Archived'],
	];

	$list = HiBlock::getList(['>ID' => 0], ['ID', 'NAME'], true, true);

	expect(HighloadBlockTable::$lastListQuery)->toBe([
		'order' => ['ID' => 'ASC'],
		'filter' => ['>ID' => 0],
		'select' => ['ID', 'NAME'],
		'cache' => ['ttl' => HiBlock::CACHE_TTL],
	])->and(HighloadBlockLangTable::$lastListQuery['filter'])->toBe([
		'ID' => [3, 8],
		'LID' => 'en',
	])->and($list[0]['LOC']['NAME'])->toBe('Localized countries')
		->and($list[1]['LOC'])->toBe(['NAME' => 'Cities'])
		->and($list[0]['PROPERTIES'][0]['VALUE_LIST'])->toBe(CUserFieldEnum::$rows)
		->and($list[1]['PROPERTIES'][0]['FIELD_NAME'])->toBe('UF_TITLE')
		->and(CUserFieldEnum::$lastFilter)->toBe(['USER_FIELD_ID' => 41]);
});

it('can list blocks without properties and without cache', function (): void {
	$list = HiBlock::getList(getProps: false);

	expect(HighloadBlockTable::$lastListQuery['cache'])->toBe([])
		->and(HighloadBlockLangTable::$lastListQuery['cache'])->toBe([])
		->and($list[0])->not->toHaveKey('PROPERTIES');
});

it('returns an empty list when no highload blocks match', function (): void {
	HighloadBlockTable::$rows = [];

	$list = HiBlock::getList(['=NAME' => 'Missing'], ['ID', 'NAME']);

	expect($list)->toBe([])
		->and(HighloadBlockLangTable::$lastListQuery['filter'])->toBe([
			'ID' => [],
			'LID' => 'en',
		])
		->and(CUserTypeEntity::$lastListFilter)->toBe([]);
});

it('returns sorted enumeration values and current language id', function (): void {
	CUserFieldEnum::$rows = [['ID' => 9, 'VALUE' => 'Ready']];

	expect(HiBlock::getEnumPropertyValues(55))->toBe(CUserFieldEnum::$rows)
		->and(CUserFieldEnum::$lastOrder)->toBe(['VALUE' => 'ASC'])
		->and(CUserFieldEnum::$lastFilter)->toBe(['USER_FIELD_ID' => 55])
		->and(HiBlock::getLanguageId())->toBe('en');
});
