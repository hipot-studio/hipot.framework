<?php

namespace Hipot\Types\Container;

trait Traversable
{
	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->toArray());
	}
}