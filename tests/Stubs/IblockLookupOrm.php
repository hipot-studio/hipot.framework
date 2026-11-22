<?php

declare(strict_types=1);

namespace Bitrix\Iblock;

final class LookupQueryStub
{
	private array $filter = [];
	private array $order = [];
	private ?int $limit = null;

	public function setLimit(int $limit): self
	{
		$this->limit = $limit;
		return $this;
	}

	public function fetch(): array|false
	{
		return $this->fetchAll()[0] ?? false;
	}

	public function __construct(private string $table)
	{
	}

	public function setSelect(array $select): self
	{
		return $this;
	}

	public function where(string $field, mixed $value): self
	{
		$this->filter[$field] = $value;
		return $this;
	}

	public function setOrder(array $order): self
	{
		$this->order = $order;
		return $this;
	}

	public function setCacheTtl(int $ttl): self
	{
		$this->table::$ttl = $ttl;
		return $this;
	}

	public function cacheJoins(bool $enabled): self
	{
		$this->table::$cacheJoins = $enabled;
		return $this;
	}

	public function fetchAll(): array
	{
		$this->table::$queryCount++;
		$rows = array_values(array_filter($this->table::$rows, function (array $row): bool {
			foreach ($this->filter as $field => $value) {
				$field = match ($field) {
					'PROPERTY.IBLOCK_ID' => 'IBLOCK_ID',
					'PROPERTY.CODE'      => 'PROPERTY_CODE',
					default              => $field,
				};
				if (($row[$field] ?? null) != $value) {
					return false;
				}
			}
			return true;
		}));
		usort($rows, function (array $left, array $right): int {
			foreach ($this->order as $field => $direction) {
				$result = ($left[$field] ?? 0) <=> ($right[$field] ?? 0);
				if ($result !== 0) {
					return $direction === 'DESC' ? -$result : $result;
				}
			}
			return 0;
		});
		return $this->limit === null ? $rows : array_slice($rows, 0, $this->limit);
	}
}

trait LookupTableStub
{
	public static array $rows = [];
	public static int $queryCount = 0;
	public static int $ttl = 0;
	public static bool $cacheJoins = false;

	public static function query(): LookupQueryStub
	{
		return new LookupQueryStub(static::class);
	}
}

final class PropertyEnumerationTable
{
	use LookupTableStub;
}

final class PropertyTable
{
	use LookupTableStub;
}
