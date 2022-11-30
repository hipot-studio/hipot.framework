<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 30.11.2022 04:05
 * @version pre 1.0
 */
namespace Hipot\Types;

use IteratorAggregate;
use CDBResult,
	Bitrix\Main\ORM\Query\Result;

class DbResultGenerator implements IteratorAggregate
{
	/**
	 * @var CDBResult | Result
	 */
	private $result;
	private bool $getExtra;

	/** @noinspection MissingParameterTypeDeclarationInspection */
	public function __construct($result, bool $getExtra = false)
	{
		if (!is_subclass_of($result, CDBResult::class) && !is_subclass_of($result, IteratorAggregate::class)) {
			throw new \InvalidArgumentException('Wrong type of $result');
		}
		$this->result = $result;
		$this->getExtra = $getExtra;
	}

	final public function getIterator(): \Generator
	{
		return (function () {
			if (is_subclass_of($this->result, IteratorAggregate::class)) {
				return $this->result->getIterator();
			}

			if ($this->getExtra) {
				while ($ar = $this->result->GetNext(true, true)) {
					yield $ar;
				}
			} else {
				while ($ar = $this->result->Fetch()) {
					yield $ar;
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
}