<?php

declare(strict_types=1);

use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\UserFieldTable;
use Hipot\BitrixUtils\HiBlock;

$fixtureHiBlockId = 0;

beforeAll(function (): void {
	Loader::requireModule('highloadblock');
});

beforeEach(function () use (&$fixtureHiBlockId): void {
	$fixtureHiBlockId = 0;
});

afterEach(function () use (&$fixtureHiBlockId): void {
	if ($fixtureHiBlockId <= 0) {
		return;
	}

	$fields = UserFieldTable::getList([
		'filter' => ['=ENTITY_ID' => 'HLBLOCK_' . $fixtureHiBlockId],
		'select' => ['ID'],
	]);
	$userField = new CUserTypeEntity();
	while ($field = $fields->fetch()) {
		$userField->Delete((int)$field['ID']);
	}

	HighloadBlockLangTable::delete([
		'ID' => $fixtureHiBlockId,
		'LID' => LANGUAGE_ID,
	]);
	HighloadBlockTable::delete($fixtureHiBlockId);
	$fixtureHiBlockId = 0;
});

it('creates and uses a highload block with string and enumeration fields', function () use (&$fixtureHiBlockId): void {
	$suffix = bin2hex(random_bytes(6));
	$name = 'TestHiBlock' . $suffix;
	$tableName = 'b_hlbd_test_' . $suffix;
	$localizedName = 'Integration HL block ' . $suffix;

	$addResult = HiBlock::addHiBlock($name, $tableName);
	expect($addResult->isSuccess())->toBeTrue(implode('; ', $addResult->getErrorMessages()));
	$fixtureHiBlockId = (int)$addResult->getId();

	$langResult = HighloadBlockLangTable::add([
		'ID' => $fixtureHiBlockId,
		'LID' => LANGUAGE_ID,
		'NAME' => $localizedName,
	]);
	expect($langResult->isSuccess())->toBeTrue(implode('; ', $langResult->getErrorMessages()));

	$titleFieldId = HiBlock::addHiBlockField([
		'HLBLOCK_ID' => $fixtureHiBlockId,
		'CODE' => 'TITLE',
		'SORT' => 100,
		'REQUIRED' => 'Y',
		'IS_SEARCHABLE' => 'Y',
		'NAME' => 'Title',
		'HELP' => 'Integration test title',
	]);
	expect($titleFieldId)->toBeInt()->toBeGreaterThan(0);

	$enumField = new CUserTypeEntity();
	$statusFieldId = $enumField->Add([
		'ENTITY_ID' => 'HLBLOCK_' . $fixtureHiBlockId,
		'FIELD_NAME' => 'UF_STATUS',
		'USER_TYPE_ID' => 'enumeration',
		'XML_ID' => '',
		'SORT' => 200,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'EDIT_FORM_LABEL' => [LANGUAGE_ID => 'Status'],
	]);
	expect($statusFieldId)->toBeInt()->toBeGreaterThan(0);

	$enum = new CUserFieldEnum();
	$enumSaved = $enum->SetEnumValues((int)$statusFieldId, [
		'n0' => ['VALUE' => 'Active', 'DEF' => 'Y', 'SORT' => 100],
		'n1' => ['VALUE' => 'Archived', 'DEF' => 'N', 'SORT' => 200],
	]);
	expect($enumSaved)->toBeTrue();

	$byId = HiBlock::getHightloadBlockTable($fixtureHiBlockId, 'Ignored');
	$byName = HiBlock::getHightloadBlockTable(0, $name);
	$dataManagerById = HiBlock::getDataManagerByHiId($fixtureHiBlockId);
	$dataManagerByCode = HiBlock::getDataManagerByHiCode($name);

	expect((int)$byId['ID'])->toBe($fixtureHiBlockId)
		->and($byName['NAME'])->toBe($name)
		->and($dataManagerById)->toBe($dataManagerByCode);

	$enumValues = HiBlock::getEnumPropertyValues((int)$statusFieldId);
	expect($enumValues)->toHaveCount(2);
	$activeEnumId = (int)$enumValues[0]['ID'];

	$rowResult = $dataManagerById::add([
		'UF_TITLE' => 'Created through DataManager',
		'UF_STATUS' => $activeEnumId,
	]);
	expect($rowResult->isSuccess())->toBeTrue(implode('; ', $rowResult->getErrorMessages()));
	$rowId = (int)$rowResult->getId();
	$rowDeleted = false;

	try {
		$row = $dataManagerByCode::getByPrimary($rowId)->fetch();
		$list = HiBlock::getList(['=ID' => $fixtureHiBlockId], ['*'], true, false);

		expect($row['UF_TITLE'])->toBe('Created through DataManager')
			->and((int)$row['UF_STATUS'])->toBe($activeEnumId)
			->and($list)->toHaveCount(1)
			->and((int)$list[0]['ID'])->toBe($fixtureHiBlockId)
			->and($list[0]['LOC']['NAME'])->toBe($localizedName)
			->and($list[0]['PROPERTIES'])->toHaveCount(2);

		$statusProperty = array_values(array_filter(
			$list[0]['PROPERTIES'],
			static fn(array $property): bool => $property['FIELD_NAME'] === 'UF_STATUS',
		))[0];
		expect($statusProperty['VALUE_LIST'])->toHaveCount(2)
			->and(array_column($statusProperty['VALUE_LIST'], 'VALUE'))->toBe(['Active', 'Archived']);

		$deleteResult = $dataManagerById::delete($rowId);
		expect($deleteResult->isSuccess())->toBeTrue(implode('; ', $deleteResult->getErrorMessages()));
		$rowDeleted = true;

		HiBlock::deleteUserField($fixtureHiBlockId, 'UF_TITLE');
		expect(UserFieldTable::getList([
			'filter' => [
				'=ENTITY_ID' => 'HLBLOCK_' . $fixtureHiBlockId,
				'=FIELD_NAME' => 'UF_TITLE',
			],
		])->fetch())->toBeFalse();
	} finally {
		if (!$rowDeleted) {
			$dataManagerById::delete($rowId);
		}
	}
});
