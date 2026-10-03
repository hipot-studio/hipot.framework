<?php

declare(strict_types=1);

use Bitrix\Main\UserFieldTable;
use Hipot\Utils\Helper\UserFieldUtils;

function userFieldUtilsFixture(): object
{
	return new class {
		use UserFieldUtils;
	};
}

function addUserFieldUtilsField(string $entityId, string $fieldName, string $type, int $sort, string $label): int
{
	$userField = new CUserTypeEntity();
	$fieldId = $userField->Add([
		'ENTITY_ID' => $entityId,
		'FIELD_NAME' => $fieldName,
		'USER_TYPE_ID' => $type,
		'XML_ID' => '',
		'SORT' => $sort,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'EDIT_FORM_LABEL' => [LANGUAGE_ID => $label],
	]);

	if (!is_int($fieldId) || $fieldId <= 0) {
		throw new RuntimeException(sprintf('Failed to create user field %s.', $fieldName));
	}

	return $fieldId;
}

$userFieldUtilsEntityId = '';
$userFieldUtilsFieldIds = [];

beforeAll(function () use (&$userFieldUtilsEntityId, &$userFieldUtilsFieldIds): void {
	$userFieldUtilsEntityId = 'HIPOT_TEST_' . strtoupper(bin2hex(random_bytes(6)));
	$userFieldUtilsFieldIds = [
		'integer' => addUserFieldUtilsField($userFieldUtilsEntityId, 'UF_INTEGER', 'integer', 100, 'Integer value'),
		'double' => addUserFieldUtilsField($userFieldUtilsEntityId, 'UF_DOUBLE', 'double', 200, 'Double value'),
		'enumeration' => addUserFieldUtilsField($userFieldUtilsEntityId, 'UF_STATUS', 'enumeration', 300, 'Status'),
		'string' => addUserFieldUtilsField($userFieldUtilsEntityId, 'UF_TEXT', 'string', 400, 'Text value'),
	];

	$enum = new CUserFieldEnum();
	if (!$enum->SetEnumValues($userFieldUtilsFieldIds['enumeration'], [
		'n0' => ['VALUE' => 'Active', 'DEF' => 'Y', 'SORT' => 100],
	])) {
		throw new RuntimeException('Failed to create a user field enumeration value.');
	}
});

afterAll(function () use (&$userFieldUtilsEntityId): void {
	if ($userFieldUtilsEntityId === '') {
		return;
	}

	$fields = UserFieldTable::query()
		->setSelect(['ID'])
		->where('ENTITY_ID', $userFieldUtilsEntityId)
		->fetchAll();
	$userField = new CUserTypeEntity();

	foreach ($fields as $field) {
		$userField->Delete((int)$field['ID']);
	}
});

it('returns user fields with their localized titles', function () use (&$userFieldUtilsEntityId): void {
	$fields = userFieldUtilsFixture()::getUserFieldList(
		['=ENTITY_ID' => $userFieldUtilsEntityId],
		['order' => ['SORT' => 'ASC']],
	);

	expect($fields)->toHaveCount(4)
		->and(array_column($fields, 'FIELD_NAME'))->toBe([
			'UF_INTEGER',
			'UF_DOUBLE',
			'UF_STATUS',
			'UF_TEXT',
		])
		->and($fields[0])->toMatchArray([
			'ENTITY_ID' => $userFieldUtilsEntityId,
			'USER_TYPE_ID' => 'integer',
			'MULTIPLE' => 'N',
			'MAIN_USER_FIELD_TITLE_EDIT_FORM_LABEL' => 'Integer value',
		]);
});

it('normalizes scalar and multiple values according to the user field type', function () use (&$userFieldUtilsEntityId): void {
	$utils = userFieldUtilsFixture();

	expect($utils::normalizeUfFieldValue($userFieldUtilsEntityId, 'UF_INTEGER', '1 234'))->toBe(1234)
		->and($utils::normalizeUfFieldValue($userFieldUtilsEntityId, 'UF_INTEGER', ['10', '2 500']))->toBe([10, 2500])
		->and($utils::normalizeUfFieldValue($userFieldUtilsEntityId, 'UF_DOUBLE', '1 234,56'))->toBe(1234.56)
		->and($utils::normalizeUfFieldValue($userFieldUtilsEntityId, 'UF_DOUBLE', ['1,5', '2 000.25']))->toBe([1.5, 2000.25])
		->and($utils::normalizeUfFieldValue($userFieldUtilsEntityId, 'UF_TEXT', 'unchanged'))->toBe('unchanged')
		->and($utils::normalizeUfFieldValue($userFieldUtilsEntityId, 'UF_UNKNOWN', 'raw value'))->toBe('raw value');
});

it('returns an existing enum id and creates a missing enum value', function () use (&$userFieldUtilsEntityId, &$userFieldUtilsFieldIds): void {
	$utils = userFieldUtilsFixture();
	$activeId = $utils::normalizeUfFieldValue($userFieldUtilsEntityId, 'UF_STATUS', 'Active');
	$archivedId = $utils::normalizeUfFieldValue($userFieldUtilsEntityId, 'UF_STATUS', 'Archived');

	$values = [];
	$result = CUserFieldEnum::GetList(
		['SORT' => 'ASC'],
		['USER_FIELD_ID' => $userFieldUtilsFieldIds['enumeration']],
	);
	while ($value = $result->Fetch()) {
		$values[(int)$value['ID']] = $value['VALUE'];
	}

	expect($activeId)->toBeInt()->toBeGreaterThan(0)
		->and($archivedId)->toBeInt()->toBeGreaterThan(0)->not->toBe($activeId)
		->and($values[$activeId])->toBe('Active')
		->and($values[$archivedId])->toBe('Archived');
});
