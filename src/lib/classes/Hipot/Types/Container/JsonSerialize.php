<?php
namespace Hipot\Types\Container;

/**
 * Container implements JsonSerializable interface
 * @see      JsonSerializable
 */
trait JsonSerialize
{
	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}
}
