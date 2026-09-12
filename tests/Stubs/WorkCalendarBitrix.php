<?php

declare(strict_types=1);

final class WorkCalendarIblockResult
{
	public function __construct(private array $rows)
	{
	}

	public function Fetch(): array|false
	{
		return array_shift($this->rows) ?? false;
	}
}

final class CIBlockElement
{
	public static array $rows = [];
	public static array $lastQuery = [];
	public static int $queryCount = 0;

	public static function GetList(
		array $order,
		array $filter,
		mixed $groupBy,
		mixed $navigation,
		array $select,
	): WorkCalendarIblockResult {
		self::$queryCount++;
		self::$lastQuery = compact('order', 'filter', 'groupBy', 'navigation', 'select');

		return new WorkCalendarIblockResult(self::$rows);
	}
}
