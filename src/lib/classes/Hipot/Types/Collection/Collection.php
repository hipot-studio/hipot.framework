<?php

namespace Hipot\Types\Collection;

class Collection extends \ArrayObject
{
	public function isEmpty(): bool
	{
		return $this->count() === 0;
	}

	public function first(): mixed
	{
		$items = $this->getArrayCopy();
		$key = array_key_first($items);

		return $key === null ? null : $items[$key];
	}

	public function last(): mixed
	{
		$items = $this->getArrayCopy();
		$key = array_key_last($items);

		return $key === null ? null : $items[$key];
	}

	public function lastKey(): int|string|null
	{
		return array_key_last($this->getArrayCopy());
	}
}
