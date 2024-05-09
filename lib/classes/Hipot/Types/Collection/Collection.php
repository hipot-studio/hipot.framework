<?php

namespace Hipot\Types\Collection;

class Collection extends \ArrayObject
{
	public function lastKey(): int
	{
		return array_key_last($this->getArrayCopy());
	}
}