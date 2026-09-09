<?php

declare(strict_types=1);

namespace Bitrix\Main {
}

namespace Bitrix\Main\ORM\Data {
	class AddResult
	{
		public function __construct(private readonly int $id = 0)
		{
		}

		public function getId(): int
		{
			return $this->id;
		}
	}
}

namespace Bitrix\Highloadblock {
	use Bitrix\Main\ORM\Data\AddResult;

	final class HighloadBlockTable
	{
		public static array $rows = [];
		public static array $lastPrimaryCall = [];
		public static array $lastListQuery = [];
		public static array $lastAddedFields = [];
		public static string $compiledDataClass = 'Tests\\Fixtures\\HiBlockDataManager';
		public static int $nextId = 100;

		public static function getByPrimary(int $id, array $parameters = []): \HiBlockOrmResult
		{
			self::$lastPrimaryCall = compact('id', 'parameters');
			return new \HiBlockOrmResult(isset(self::$rows[$id]) ? [self::$rows[$id]] : []);
		}

		public static function getList(array $query): \HiBlockOrmResult
		{
			self::$lastListQuery = $query;
			$rows = array_values(self::$rows);
			if (isset($query['filter']['=NAME'])) {
				$rows = array_values(array_filter(
					$rows,
					static fn(array $row): bool => $row['NAME'] === $query['filter']['=NAME'],
				));
			}
			return new \HiBlockOrmResult($rows);
		}

		public static function compileEntity(array $row): object
		{
			return new class {
				public function getDataClass(): string
				{
					return HighloadBlockTable::$compiledDataClass;
				}
			};
		}

		public static function add(array $fields): AddResult
		{
			self::$lastAddedFields = $fields;
			return new AddResult(self::$nextId);
		}
	}

	final class HighloadBlockLangTable
	{
		public static array $rows = [];
		public static array $lastListQuery = [];

		public static function getList(array $query): \HiBlockOrmResult
		{
			self::$lastListQuery = $query;
			return new \HiBlockOrmResult(self::$rows);
		}
	}
}

namespace {
	defined('LANGUAGE_ID') || define('LANGUAGE_ID', 'en');

	final class HiBlockOrmResult
	{
		private int $index = 0;

		public function __construct(private readonly array $rows)
		{
		}

		public function fetch(): array|false
		{
			return $this->rows[$this->index++] ?? false;
		}

		public function fetchAll(): array
		{
			return $this->rows;
		}
	}

	final class HiBlockLegacyResult extends CDBResult
	{
		private int $index = 0;

		public function __construct(private readonly array $rows)
		{
		}

		public function Fetch(): array|false
		{
			return $this->rows[$this->index++] ?? false;
		}
	}

	class CUserTypeEntity
	{
		public static array $listRows = [];
		public static array $lastListOrder = [];
		public static array $lastListFilter = [];
		public static array $lastAddedFields = [];
		public static array $deletedIds = [];
		public static int|false $addResult = 501;

		public function Add(array $fields): int|false
		{
			self::$lastAddedFields = $fields;
			return self::$addResult;
		}

		public function Delete(int $id): bool
		{
			self::$deletedIds[] = $id;
			return true;
		}

		public static function GetList(array $order, array $filter): HiBlockLegacyResult
		{
			self::$lastListOrder = $order;
			self::$lastListFilter = $filter;
			return new HiBlockLegacyResult(self::$listRows);
		}
	}

}
