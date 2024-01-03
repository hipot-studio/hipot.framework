<?php
namespace Hipot\Types\Container;

/**
 * @see https://www.php.net/manual/ru/class.arrayaccess.php
 */
trait ArrayAccess
{
	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->{$offset});
	}

	/**
	 * @param mixed $offset
	 *
	 * @return mixed|null
	 */
	public function offsetGet($offset)
	{
		if ($this->offsetExists($offset)) {
			return $this->{$offset};
		}
		return null;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value): void
	{
		$this->{$offset} = $value;
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset): void
	{
		if ($this->offsetExists($offset)) {
			unset($this->{$offset});
		}
	}
}