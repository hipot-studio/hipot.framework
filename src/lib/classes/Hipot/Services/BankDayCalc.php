<?php

declare(strict_types=1);

namespace Hipot\Services;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

/** Calculates working days using explicit calendar exceptions. */
final class BankDayCalc
{
	/** @var array<string, true> */
	private array $holidays;
	
	/** @var array<string, true> */
	private array $workdays;
	
	/** @var array<int, true> ISO-8601 weekday numbers. */
	private array $weekends;
	
	/**
	 * @param list<string|DateTimeInterface> $holidays Explicit non-working dates in Y-m-d format.
	 * @param list<string|DateTimeInterface> $workdays Explicit working dates in Y-m-d format.
	 * @param list<int> $weekends ISO-8601 weekday numbers, 1 (Monday) through 7 (Sunday).
	 */
	public function __construct(
		array $holidays = [],
		array $workdays = [],
		array $weekends = [6, 7],
	) {
		$this->holidays = $this->normalizeDates($holidays);
		$this->workdays = $this->normalizeDates($workdays);
		$this->weekends = [];
		foreach ($weekends as $weekday) {
			if ($weekday < 1 || $weekday > 7) {
				throw new \InvalidArgumentException('Weekend day must be between 1 and 7.');
			}
			$this->weekends[$weekday] = true;
		}
	}
	
	/** @param list<int> $weekends */
	public static function fromProvider(WorkCalendarProviderInterface $provider, array $weekends = [6, 7]): self
	{
		return new self($provider->getHolidays(), $provider->getWorkdays(), $weekends);
	}
	
	public function isWeekend(DateTimeInterface $date): bool
	{
		return isset($this->weekends[(int)$date->format('N')]);
	}
	
	public function isHoliday(DateTimeInterface $date): bool
	{
		return isset($this->holidays[$date->format('Y-m-d')]);
	}
	
	public function isWorkDay(DateTimeInterface $date): bool
	{
		$key = $date->format('Y-m-d');
		if (isset($this->workdays[$key])) {
			return true;
		}
		
		return !isset($this->holidays[$key]) && !$this->isWeekend($date);
	}
	
	/** Returns the date reached after moving by the requested number of working days. */
	public function getEndDate(DateTimeInterface $start, int $days): DateTimeImmutable
	{
		$current = DateTimeImmutable::createFromInterface($start);
		$remaining = abs($days);
		$day = new DateInterval('P1D');
		
		while ($remaining > 0) {
			$current = $days < 0 ? $current->sub($day) : $current->add($day);
			if ($this->isWorkDay($current)) {
				$remaining--;
			}
		}
		
		return $current;
	}
	
	/** Counts working dates in the half-open interval [start, end). */
	public function getNumDays(DateTimeInterface $start, DateTimeInterface $end): int
	{
		$current = DateTimeImmutable::createFromInterface($start)->setTime(0, 0);
		$end = DateTimeImmutable::createFromInterface($end)->setTime(0, 0);
		if ($current > $end) {
			throw new \InvalidArgumentException('Start date must not be later than end date.');
		}
		
		$count = 0;
		$day = new DateInterval('P1D');
		while ($current < $end) {
			if ($this->isWorkDay($current)) {
				$count++;
			}
			$current = $current->add($day);
		}
		
		return $count;
	}
	
	/** @param list<string|DateTimeInterface> $dates @return array<string, true> */
	private function normalizeDates(array $dates): array
	{
		$result = [];
		foreach ($dates as $date) {
			if ($date instanceof DateTimeInterface) {
				$result[$date->format('Y-m-d')] = true;
				continue;
			}
			
			$parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
			$errors = DateTimeImmutable::getLastErrors();
			if ($parsed === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
				throw new \InvalidArgumentException(sprintf('Invalid calendar date "%s"; expected Y-m-d.', $date));
			}
			$result[$parsed->format('Y-m-d')] = true;
		}
		
		return $result;
	}
}
