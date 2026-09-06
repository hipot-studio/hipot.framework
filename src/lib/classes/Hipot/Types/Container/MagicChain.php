<?php

namespace Hipot\Types\Container;

/**
 * To use chain set-values, ex $item->prop1->prop2->prop3 = '';
 * unsafe and set rule to use ONLY isset($item->prop1->prop2) on props to avoid it create in check $item->prop1->prop2 !== 'null'
 */
trait MagicChain
{
	protected function onBeforeGetContainer(string $key): void
	{
		if ($this->useMagicChain !== true) {
			return;
		}
		if (! $this->doContainsContainer($key)) {
			$this->setFromArray([
				$key => new self()
			]);
		}
	}
}