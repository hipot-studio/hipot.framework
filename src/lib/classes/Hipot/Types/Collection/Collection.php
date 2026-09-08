<?php

namespace Hipot\Types\Collection;

class Collection extends \ArrayObject
{
	public function lastKey(): int|string|null
	{
		return array_key_last($this->getArrayCopy());
	}
}
