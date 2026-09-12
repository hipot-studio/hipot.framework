<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Hipot\Services\IblockWorkCalendarProvider;

require_once dirname(__DIR__, 2) . '/Stubs/WorkCalendarBitrix.php';

beforeEach(function (): void {
	CIBlockElement::$rows = [];
	CIBlockElement::$lastQuery = [];
	CIBlockElement::$queryCount = 0;
	Loader::$includedModules = [];
});

it('loads and normalizes calendar exceptions from an iblock once', function (): void {
	CIBlockElement::$rows = [
		['PROPERTY_DATE_VALUE' => '14.09.2026', 'PROPERTY_TYPE_VALUE' => 'Выходной день'],
		['PROPERTY_DATE_VALUE' => '19.09.2026', 'PROPERTY_TYPE_VALUE' => 'Рабочий день'],
	];
	$provider = new IblockWorkCalendarProvider(17);

	expect($provider->getHolidays())->toBe(['2026-09-14'])
		->and($provider->getWorkdays())->toBe(['2026-09-19'])
		->and(CIBlockElement::$queryCount)->toBe(1)
		->and(Loader::$includedModules)->toContain('iblock')
		->and(CIBlockElement::$lastQuery['filter'])->toBe(['IBLOCK_ID' => 17, 'ACTIVE' => 'Y'])
		->and(CIBlockElement::$lastQuery['select'])->toBe([
			'PROPERTY_DATE',
			'PROPERTY_TYPE',
		]);
});

it('supports custom property values and an Y-m-d source format', function (): void {
	CIBlockElement::$rows = [
		['PROPERTY_DAY_VALUE' => '2026-12-31', 'PROPERTY_KIND_VALUE' => 'HOLIDAY'],
		['PROPERTY_DAY_VALUE' => '2026-12-26', 'PROPERTY_KIND_VALUE' => 'WORKDAY'],
	];
	$provider = new IblockWorkCalendarProvider(
		iblockId: 4,
		datePropertyCode: 'DAY',
		typePropertyCode: 'KIND',
		holidayType: 'HOLIDAY',
		workdayType: 'WORKDAY',
		sourceDateFormat: 'Y-m-d',
	);

	expect($provider->getHolidays())->toBe(['2026-12-31'])
		->and($provider->getWorkdays())->toBe(['2026-12-26']);
});
