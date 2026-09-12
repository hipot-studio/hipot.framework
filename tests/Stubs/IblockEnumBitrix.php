<?php

declare(strict_types=1);

final class IblockEnumResult
{
	public function __construct(private array $rows)
	{
	}

	public function Fetch(): array|false
	{
		return array_shift($this->rows) ?? false;
	}

	public function GetNext(): array|false
	{
		return $this->Fetch();
	}
}

final class CIBlockPropertyEnum
{
	public static array $rows = [];
	public static array $lastOrder = [];
	public static array $lastFilter = [];
	public static int $queryCount = 0;

	public static function GetList(array $order = [], array $filter = []): IblockEnumResult
	{
		self::$queryCount++;
		self::$lastOrder = $order;
		self::$lastFilter = $filter;
		$rows = array_values(array_filter(
			self::$rows,
			static function (array $row) use ($filter): bool {
				foreach ($filter as $field => $value) {
					if (($row[$field] ?? null) != $value) {
						return false;
					}
				}

				return true;
			},
		));

		return new IblockEnumResult($rows);
	}
}
