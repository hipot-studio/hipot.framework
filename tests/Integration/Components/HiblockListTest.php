<?php

declare(strict_types=1);

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\UserFieldTable;
use Hipot\BitrixUtils\HiBlock;
use Hipot\Components\HiblockList;
use function Opis\Closure\serialize as serializeClosure;

$fixtureHiBlockId = 0;
$fixtureHiBlockName = '';
$fixtureDataClass = null;
$fixtureRowIds = [];

beforeAll(function (): void {
	Loader::requireModule('highloadblock');
	Loader::requireModule('iblock');
});

beforeEach(function () use (
	&$fixtureHiBlockId,
	&$fixtureHiBlockName,
	&$fixtureDataClass,
	&$fixtureRowIds,
): void {
	$suffix = bin2hex(random_bytes(6));
	$fixtureHiBlockName = 'ComponentTestHiBlock' . $suffix;
	$fixtureRowIds = [];

	$addResult = HiBlock::addHiBlock(
		$fixtureHiBlockName,
		'b_hlbd_component_test_' . $suffix,
	);
	expect($addResult->isSuccess())->toBeTrue(implode('; ', $addResult->getErrorMessages()));
	$fixtureHiBlockId = (int)$addResult->getId();

	foreach (['TITLE' => 100, 'STATUS' => 200] as $code => $sort) {
		$fieldId = HiBlock::addHiBlockField([
			'HLBLOCK_ID' => $fixtureHiBlockId,
			'CODE' => $code,
			'SORT' => $sort,
			'REQUIRED' => 'N',
			'IS_SEARCHABLE' => 'Y',
			'NAME' => ucfirst(strtolower($code)),
			'HELP' => "Component test {$code}",
		]);
		expect($fieldId)->toBeInt()->toBeGreaterThan(0);
	}

	$hlblock = HighloadBlockTable::getByPrimary($fixtureHiBlockId)->fetch();
	expect($hlblock)->toBeArray();
	$fixtureDataClass = HighloadBlockTable::compileEntity($hlblock)->getDataClass();

	foreach ([
		['UF_TITLE' => "A visible {$suffix}", 'UF_STATUS' => 'visible'],
		['UF_TITLE' => "B visible {$suffix}", 'UF_STATUS' => 'visible'],
		['UF_TITLE' => "C hidden {$suffix}", 'UF_STATUS' => 'hidden'],
	] as $row) {
		$rowResult = $fixtureDataClass::add($row);
		expect($rowResult->isSuccess())->toBeTrue(implode('; ', $rowResult->getErrorMessages()));
		$fixtureRowIds[] = (int)$rowResult->getId();
	}
});

afterEach(function () use (
	&$fixtureHiBlockId,
	&$fixtureHiBlockName,
	&$fixtureDataClass,
	&$fixtureRowIds,
): void {
	if ($fixtureDataClass !== null) {
		foreach (array_reverse($fixtureRowIds) as $rowId) {
			$fixtureDataClass::delete($rowId);
		}
	}

	if ($fixtureHiBlockId > 0) {
		$fields = UserFieldTable::getList([
			'filter' => ['=ENTITY_ID' => 'HLBLOCK_' . $fixtureHiBlockId],
			'select' => ['ID'],
		]);
		$userField = new CUserTypeEntity();
		while ($field = $fields->fetch()) {
			$userField->Delete((int)$field['ID']);
		}

		HighloadBlockTable::delete($fixtureHiBlockId);
	}

	$fixtureHiBlockId = 0;
	$fixtureHiBlockName = '';
	$fixtureDataClass = null;
	$fixtureRowIds = [];
});

it('selects filters sorts and modifies highload-block rows by id', function () use (&$fixtureHiBlockId): void {
	/** @var CMain $APPLICATION */
	global $APPLICATION;

	$modifyItem = static function (array &$item): void {
		$item['PRE'] = ($item['~UF_TITLE'] ?? 'missing-title') . '|modified';
	};

	ob_start();
	try {
		$result = $APPLICATION->IncludeComponent(
			'hipot:hiblock.list',
			'.default',
			[
				'HLBLOCK_ID' => $fixtureHiBlockId,
				'HLBLOCK_CODE' => '',
				'ORDER' => ['UF_TITLE' => 'DESC'],
				'SELECT' => ['UF_TITLE', 'UF_STATUS'],
				'FILTER' => ['=UF_STATUS' => 'visible'],
				'GROUP_BY' => [],
				'NTOPCOUNT' => 2,
				'PAGESIZE' => 0,
				'NAV_TEMPLATE' => '',
				'NAV_SHOW_ALWAYS' => 'N',
				'NAV_SHOW_ALL' => 'N',
				'NAV_TITLE' => '',
				'SET_404' => 'N',
				'ALWAYS_INCLUDE_TEMPLATE' => 'Y',
				'SET_CACHE_KEYS' => ['ITEMS'],
				'~MODIFY_ITEM' => serializeClosure($modifyItem),
				'CACHE_TYPE' => 'N',
				'CACHE_TIME' => 0,
				'CACHE_TIME_ORM' => 0,
			],
			false,
			['HIDE_ICONS' => 'Y'],
			true,
		);
	} finally {
		$rendered = (string)ob_get_clean();
	}

	expect(class_exists(HiblockList::class, false))
		->toBeTrue()
		->and($result)->toBeArray()
		->and($result['ITEMS'])->toHaveCount(2)
		->and($result['ITEMS'][0]['~UF_TITLE'])->toStartWith('B visible ')
		->and($result['ITEMS'][1]['~UF_TITLE'])->toStartWith('A visible ')
		->and($result['ITEMS'][0]['~UF_STATUS'])->toBe('visible')
		->and($result['ITEMS'][0]['fields'])->toHaveKeys(['UF_TITLE', 'UF_STATUS'])
		->and($rendered)->toContain(
			$result['ITEMS'][0]['~UF_TITLE'] . '|modified',
			$result['ITEMS'][1]['~UF_TITLE'] . '|modified',
		)
		->and($rendered)->not->toContain('C hidden ', 'missing-title');
});

it('resolves a highload block by symbolic code', function () use (&$fixtureHiBlockName, &$fixtureRowIds): void {
	/** @var CMain $APPLICATION */
	global $APPLICATION;

	$modifyItem = static function (array &$item): void {
		$item['PRE'] = 'resolved:' . ($item['~UF_TITLE'] ?? 'missing-title');
	};

	ob_start();
	try {
		$result = $APPLICATION->IncludeComponent(
			'hipot:hiblock.list',
			'.default',
			[
				'HLBLOCK_ID' => '',
				'HLBLOCK_CODE' => $fixtureHiBlockName,
				'ORDER' => ['ID' => 'ASC'],
				'SELECT' => ['UF_TITLE'],
				'FILTER' => ['=ID' => $fixtureRowIds[0]],
				'GROUP_BY' => [],
				'NTOPCOUNT' => 1,
				'PAGESIZE' => 0,
				'NAV_TEMPLATE' => '',
				'NAV_SHOW_ALWAYS' => 'N',
				'NAV_SHOW_ALL' => 'N',
				'NAV_TITLE' => '',
				'SET_404' => 'N',
				'ALWAYS_INCLUDE_TEMPLATE' => 'Y',
				'SET_CACHE_KEYS' => ['ITEMS'],
				'~MODIFY_ITEM' => serializeClosure($modifyItem),
				'CACHE_TYPE' => 'N',
				'CACHE_TIME' => 0,
				'CACHE_TIME_ORM' => 0,
			],
			false,
			['HIDE_ICONS' => 'Y'],
			true,
		);
	} finally {
		$rendered = (string)ob_get_clean();
	}

	expect($result)->toBeArray()
		->and($result['ITEMS'])->toHaveCount(1)
		->and((int)$result['ITEMS'][0]['ID'])->toBe($fixtureRowIds[0])
		->and($result['ITEMS'][0]['~UF_TITLE'])->toStartWith('A visible ')
		->and($rendered)->toContain('resolved:A visible ')
		->and($rendered)->not->toContain('missing-title');
});
