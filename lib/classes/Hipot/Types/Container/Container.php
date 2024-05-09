<?php
namespace Hipot\Types\Container;

/**
 * Container of data for object
 */
trait Container
{
	/**
	 * @var array Container of elements
	 */
	protected $container = [];


	/**
	 * Set key/value pair
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return void
	 */
	protected function doSetContainer(string $key, $value): void
	{
		$this->container[$key] = $value;
	}

	/**
	 * Get value by key
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	protected function doGetContainer(string $key)
	{
		if (method_exists($this, 'onBeforeGetContainer')) {
			$this->onBeforeGetContainer($key);
		}

		if ($this->doContainsContainer($key)) {
			return $this->container[$key];
		}
		return null;
	}

	/**
	 * Check contains key in container
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	protected function doContainsContainer(string $key): bool
	{
		return array_key_exists($key, $this->container);
	}

	/**
	 * Delete value by key
	 *
	 * @param string $key
	 *
	 * @return void
	 */
	protected function doDeleteContainer(string $key): void
	{
		unset($this->container[$key]);
	}

	/**
	 * Sets all data in the row from an array
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function setFromArray(array $data): void
	{
		foreach ($data as $key => $value) {
			$this->container[$key] = $value;
		}
	}

	/**
	 * Returns the column/value data as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->container;
	}

	/**
	 * Reset container array
	 *
	 * @return void
	 */
	public function resetArray(): void
	{
		foreach ($this->container as &$value) {
			$value = null;
		}
	}

	/**
	 * To use the Countable interface
	 * @return int
	 */
	public function count(): int
	{
		return count($this->container);
	}
}
