<?php
namespace Hipot\Types\Container;

/**
 * Container implements ArrayAccess
 *
 * @see      ArrayAccess
 *
 * @method   void  doSetContainer(string $key, $value)
 * @method   mixed doGetContainer(string $key)
 * @method   bool  doContainsContainer(string $key)
 * @method   void  doDeleteContainer(string $key)
 */
trait ArrayAccess
{
	/**
	 * Offset to set
	 *
	 * @param  mixed $offset
	 * @param  mixed $value
	 *
	 */
	public function offsetSet($offset, $value): void
	{
		$this->doSetContainer($offset, $value);
	}

	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		return $this->doGetContainer($offset);
	}

	/**
	 * Whether a offset exists
	 *
	 * @param  mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		return $this->doContainsContainer($offset);
	}

	/**
	 * Offset to unset
	 *
	 * @param mixed $offset
	 */
	public function offsetUnset($offset): void
	{
		$this->doDeleteContainer($offset);
	}
}