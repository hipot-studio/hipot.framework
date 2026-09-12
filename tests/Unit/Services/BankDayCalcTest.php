<?php

declare(strict_types=1);

use Hipot\Services\BankDayCalc;
use Hipot\Services\WorkCalendarProviderInterface;

it('applies explicit holiday weekdays and working weekends', function (): void {
	$calendar = new BankDayCalc(
		holidays: ['2026-09-14', '2026-09-19'],
		workdays: ['2026-09-19'],
	);

	expect($calendar->isWorkDay(new DateTimeImmutable('2026-09-14')))->toBeFalse()
		->and($calendar->isWeekend(new DateTimeImmutable('2026-09-19')))->toBeTrue()
		->and($calendar->isHoliday(new DateTimeImmutable('2026-09-19')))->toBeTrue()
		->and($calendar->isWorkDay(new DateTimeImmutable('2026-09-19')))->toBeTrue();
});

it('keeps holidays tied to a full calendar date', function (): void {
	$calendar = new BankDayCalc(holidays: ['2026-09-14']);

	expect($calendar->isHoliday(new DateTimeImmutable('2026-09-14')))->toBeTrue()
		->and($calendar->isHoliday(new DateTimeImmutable('2027-09-14')))->toBeFalse();
});

it('moves forward and backward by working days without changing the input object', function (): void {
	$calendar = new BankDayCalc(
		holidays: ['2026-09-14'],
		workdays: ['2026-09-19'],
	);
	$start = new DateTime('2026-09-11 15:30:00');

	expect($calendar->getEndDate($start, 2)->format('Y-m-d H:i:s'))->toBe('2026-09-16 15:30:00')
		->and($calendar->getEndDate(new DateTimeImmutable('2026-09-21'), -1)->format('Y-m-d'))->toBe('2026-09-19')
		->and($start->format('Y-m-d H:i:s'))->toBe('2026-09-11 15:30:00');
});

it('counts working days in a half-open date interval', function (): void {
	$calendar = new BankDayCalc(
		holidays: ['2026-09-14'],
		workdays: ['2026-09-19'],
	);

	expect($calendar->getNumDays(
		new DateTimeImmutable('2026-09-14'),
		new DateTimeImmutable('2026-09-21'),
	))->toBe(5);
});

it('creates a calendar from a provider', function (): void {
	$provider = new class implements WorkCalendarProviderInterface {
		public function getHolidays(): array
		{
			return ['2026-09-14'];
		}

		public function getWorkdays(): array
		{
			return [new DateTimeImmutable('2026-09-19')];
		}
	};
	$calendar = BankDayCalc::fromProvider($provider);

	expect($calendar->isWorkDay(new DateTimeImmutable('2026-09-14')))->toBeFalse()
		->and($calendar->isWorkDay(new DateTimeImmutable('2026-09-19')))->toBeTrue();
});

it('rejects partial or impossible calendar dates', function (string $date): void {
	new BankDayCalc(holidays: [$date]);
})->with(['09-14', '2026-02-30'])->throws(InvalidArgumentException::class);
