<?php
namespace Hipot\Types\Container;

/**
 * Implements regular access to container
 *
 * @method   void  doSetContainer(string $key, $value)
 * @method   mixed doGetContainer(string $key)
 * @method   bool  doContainsContainer(string $key)
 * @method   void  doDeleteContainer(string $key)
 */
trait RegularAccess
{
	/**
	 * Set key/value pair
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public function set($key, $value): void
	{
		$this->doSetContainer($key, $value);
	}

	/**
	 * Get value by key
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get($key)
	{
		return $this->doGetContainer($key);
	}

	/**
	 * Check contains key in container
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function contains($key): bool
	{
		return $this->doContainsContainer($key);
	}

	/**
	 * Delete value by key
	 *
	 * @param string $key
	 *
	 * @return void
	 */
	public function delete($key): void
	{
		$this->doDeleteContainer($key);
	}
}
