<?php

declare(strict_types=1);

namespace Hipot\Services;

use Bitrix\Main\Loader;
use DateTimeImmutable;

/** Loads explicit working and non-working dates from iblock properties. */
final class IblockWorkCalendarProvider implements WorkCalendarProviderInterface
{
	/** @var array{holidays: list<string>, workdays: list<string>}|null */
	private ?array $calendar = null;

	public function __construct(
		private readonly int $iblockId,
		private readonly string $datePropertyCode = 'DATE',
		private readonly string $typePropertyCode = 'TYPE',
		private readonly string $holidayType = 'выходной',
		private readonly string $workdayType = 'рабочий',
		private readonly string $sourceDateFormat = 'd.m.Y',
	) {
		if ($this->iblockId <= 0) {
			throw new \InvalidArgumentException('Iblock ID must be greater than zero.');
		}
		if (trim($this->datePropertyCode) === '' || trim($this->typePropertyCode) === '') {
			throw new \InvalidArgumentException('Property codes must not be empty.');
		}
		if (trim($this->holidayType) === '' || trim($this->workdayType) === '') {
			throw new \InvalidArgumentException('Calendar type values must not be empty.');
		}
		if ($this->sourceDateFormat === '') {
			throw new \InvalidArgumentException('Source date format must not be empty.');
		}
	}

	public function getHolidays(): array
	{
		return $this->load()['holidays'];
	}

	public function getWorkdays(): array
	{
		return $this->load()['workdays'];
	}

	/** @return array{holidays: list<string>, workdays: list<string>} */
	private function load(): array
	{
		if ($this->calendar !== null) {
			return $this->calendar;
		}

		Loader::requireModule('iblock');
		$dateSelect = 'PROPERTY_' . strtoupper(trim($this->datePropertyCode));
		$typeSelect = 'PROPERTY_' . strtoupper(trim($this->typePropertyCode));
		$dateField = $dateSelect . '_VALUE';
		$typeField = $typeSelect . '_VALUE';
		$calendar = ['holidays' => [], 'workdays' => []];
		$rows = \CIBlockElement::GetList(
			[],
			['IBLOCK_ID' => $this->iblockId, 'ACTIVE' => 'Y'],
			false,
			false,
			[$dateSelect, $typeSelect,  'ID', 'IBLOCK_ID'],
		);

		while ($row = $rows->Fetch()) {
			$date = $this->normalizeDate((string)($row[$dateField] ?? ''));
			$type = mb_strtolower(trim((string)($row[$typeField] ?? '')));
			if (str_starts_with($type, mb_strtolower(trim($this->holidayType)))) {
				$calendar['holidays'][] = $date;
			} elseif (str_starts_with($type, mb_strtolower(trim($this->workdayType)))) {
				$calendar['workdays'][] = $date;
			}
		}

		$calendar['holidays'] = array_values(array_unique($calendar['holidays']));
		$calendar['workdays'] = array_values(array_unique($calendar['workdays']));

		return $this->calendar = $calendar;
	}

	private function normalizeDate(string $date): string
	{
		$date = trim($date);
		$parsed = DateTimeImmutable::createFromFormat('!' . $this->sourceDateFormat, $date);
		$errors = DateTimeImmutable::getLastErrors();
		if ($parsed === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
			throw new \UnexpectedValueException(sprintf(
				'Invalid calendar date "%s"; expected format %s.',
				$date,
				$this->sourceDateFormat,
			));
		}

		return $parsed->format('Y-m-d');
	}
}
