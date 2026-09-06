<?php

namespace Hipot\Types;

/**
 * ProxyTrait
 */
trait Proxy
{
	use Singleton;

	/**
	 * Set or replace instance
	 *
	 * @param mixed $instance
	 *
	 * @return void
	 */
	public static function setInstance($instance): void
	{
		static::$instance = $instance;
	}

	/**
	 * Handle dynamic, static calls to the object.
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return mixed
	 */
	public static function __callStatic($method, $args)
	{
		if ($instance = static::getInstance()) {
			return $instance->$method(...$args);
		}
		return false;
	}
}
