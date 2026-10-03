<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Hipot\Types\UpdateResult;
use Hipot\Utils\Helper\WebFormUtils;

function webFormUtilsFixture(): object
{
	return new class {
		use WebFormUtils;
	};
}

$fixtureResultIds = [];

beforeAll(function (): void {
	Loader::requireModule('form');
});

beforeEach(function () use (&$fixtureResultIds): void {
	$fixtureResultIds = [];
});

afterEach(function () use (&$fixtureResultIds): void {
	foreach ($fixtureResultIds as $resultId) {
		CFormResult::Delete($resultId, 'N');
	}

	$fixtureResultIds = [];
});

it('returns a web form with its fields and answers', function (): void {
	$data = webFormUtilsFixture()::getFormWithFields(WEB_FORM_ID);

	expect($data)->toBeArray()
		->and((int)$data['FORM']['ID'])->toBe(WEB_FORM_ID)
		->and($data['FIELDS'])->not->toBeEmpty();

	foreach ($data['FIELDS'] as $sid => $field) {
		expect($sid)->toBe($field['SID'])
			->and((int)$field['FORM_ID'])->toBe(WEB_FORM_ID)
			->and($field['ANSWERS'])->toBeArray();
	}
});

it('returns null for an unknown web form', function (): void {
	expect(webFormUtilsFixture()::getFormWithFields(PHP_INT_MAX))->toBeNull();
});

it('adds a web form result and stores submitted field values', function () use (&$fixtureResultIds): void {
	$form = webFormUtilsFixture()::getFormWithFields(WEB_FORM_ID);
	$marker = 'web-form-utils-' . bin2hex(random_bytes(6));
	$values = [];
	$expectedText = [];
	$expectedAnswerIds = [];

	foreach ($form['FIELDS'] as $sid => $field) {
		foreach ($field['ANSWERS'] as $answer) {
			$answerId = (int)$answer['ID'];
			switch ($answer['FIELD_TYPE']) {
				case 'text':
				case 'textarea':
				case 'hidden':
				case 'password':
				case 'url':
					$value = $marker . '-' . $answerId;
					$values['form_' . $answer['FIELD_TYPE'] . '_' . $answerId] = $value;
					$expectedText[$sid][] = $value;
					break;

				case 'email':
					$value = $marker . '-' . $answerId . '@example.test';
					$values['form_email_' . $answerId] = $value;
					$expectedText[$sid][] = $value;
					break;

				case 'radio':
				case 'dropdown':
					if (!isset($values['form_' . $answer['FIELD_TYPE'] . '_' . $sid])) {
						$values['form_' . $answer['FIELD_TYPE'] . '_' . $sid] = $answerId;
						$expectedAnswerIds[$sid][] = $answerId;
					}
					break;

				case 'checkbox':
				case 'multiselect':
					$values['form_' . $answer['FIELD_TYPE'] . '_' . $sid][] = $answerId;
					$expectedAnswerIds[$sid][] = $answerId;
					break;
			}
		}
	}

	$result = webFormUtilsFixture()::formResultAddSimple(WEB_FORM_ID, $values);

	expect($result)->toBeInstanceOf(UpdateResult::class);
	$resultId = (int)$result->RESULT;
	if ($resultId > 0) {
		$fixtureResultIds[] = $resultId;
	}

	expect($result->STATUS)->toBe(UpdateResult::STATUS_OK)
		->and($resultId)->toBeGreaterThan(0);

	$resultData = [];
	$resultAnswers = [];
	$stored = CFormResult::GetDataByID(
		$resultId,
		array_keys($form['FIELDS']),
		$resultData,
		$resultAnswers,
	);

	expect($stored)->toBeArray()
		->and((int)$resultData['FORM_ID'])->toBe(WEB_FORM_ID);

	foreach ($expectedText as $sid => $expectedValues) {
		$actualValues = array_column($stored[$sid] ?? [], 'USER_TEXT');
		expect($actualValues)->toEqualCanonicalizing($expectedValues);
	}

	foreach ($expectedAnswerIds as $sid => $expectedIds) {
		$actualIds = array_map('intval', array_column($stored[$sid] ?? [], 'ANSWER_ID'));
		expect($actualIds)->toEqualCanonicalizing($expectedIds);
	}
});

it('does not add a result for a non-positive web form id', function (): void {
	expect(webFormUtilsFixture()::formResultAddSimple(0))->toBeFalse();
});
