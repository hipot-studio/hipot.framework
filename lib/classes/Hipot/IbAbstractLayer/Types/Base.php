<?php
namespace Hipot\IbAbstractLayer\Types;

use Hipot\Types\ObjectArItem;

class Base extends ObjectArItem
{
	/**
	 * Check if the given value is empty
	 *
	 * @param mixed $value The value to check
	 *
	 * @return bool Returns true if the value is empty, false otherwise
	 */
	protected function isEmptyValue($value): bool
	{
		return ($value === null) || ($value === '') || ($value === []);
	}
}
