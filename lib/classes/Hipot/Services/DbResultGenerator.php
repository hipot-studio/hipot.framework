<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 30.11.2022 04:05
 * @version pre 1.0
 */
namespace Hipot\Services;

use Bitrix\Main\ORM\Query\Result;
use CDBResult;
use Hipot\Types\ObjectArItem;
use IteratorAggregate;

/**
 * Provides an iterator for database result sets, allowing iteration
 * over data rows with optional transformations and additional information.
 */
class DbResultGenerator implements IteratorAggregate
{
	/**
	 * @var CDBResult | Result
	 */
	private $result;
	private bool $getExtra;
	private bool $returnObjects;

	/** @noinspection MissingParameterTypeDeclarationInspection */
	public function __construct(object $result, bool $returnObjects = false, bool $getExtra = false)
	{
		if (!is_a($result, CDBResult::class) && !is_subclass_of($result, CDBResult::class)
			&& !is_subclass_of($result, IteratorAggregate::class)
		) {
			throw new \InvalidArgumentException('Wrong type of $result :: ' . get_class($result));
		}
		$this->result        = $result;
		$this->getExtra      = $getExtra;
		$this->returnObjects = $returnObjects;
	}

	final public function getIterator(): \Generator
	{
		return (function () {
			if (is_subclass_of($this->result, IteratorAggregate::class)) {
				return $this->result->getIterator();
			}

			if ($this->getExtra) {
				while ($item = $this->result->GetNext(true, true)) {
					$item = $this->makeItem($item);
					yield $item;
				}
			} else {
				while ($item = $this->result->Fetch()) {
					$item = $this->makeItem($item);
					yield $item;
				}
			}
		})();
	}

	final public function getSelectedRowsCount(): int
	{
		if (method_exists($this->result, 'SelectedRowsCount')) {
			return (int)$this->result->SelectedRowsCount();
		}
		if (method_exists($this->result, 'getSelectedRowsCount')) {
			return (int)$this->result->getSelectedRowsCount();
		}
		return 0;
	}

	/**
	 * @noinspection MethodShouldBeFinalInspection
	 */
	public function makeItem(array $item)
	{
		if ($this->returnObjects) {
			return ObjectArItem::fromArr($item);
		}
		return $item;
	}

	/**
	 * Возвращает все записи выборки как массив.
	 * Если включён returnObjects, элементы будут объектами ObjectArItem.
	 * Для ORM-результатов (IteratorAggregate) будет выполнен полный проход по итератору.
	 * @return array
	 */
	public function fetchAll(): array
	{
		$all = [];

		// ORM-результат Bitrix (IteratorAggregate)
		if (is_subclass_of($this->result, IteratorAggregate::class)) {
			foreach ($this->result as $item) {
				if (is_array($item)) {
					$all[] = $this->makeItem($item);
				} else {
					$all[] = $this->makeItem( ObjectArItem::toArr($item) );
				}
			}
			return $all;
		}

		// Обычный CDBResult
		if ($this->getExtra) {
			while ($item = $this->result->GetNext(true, true)) {
				$all[] = $this->makeItem($item);
			}
		} else {
			while ($item = $this->result->Fetch()) {
				$all[] = $this->makeItem($item);
			}
		}

		return $all;
	}
}