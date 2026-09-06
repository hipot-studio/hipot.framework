<?php

namespace Hipot\Types;

/**
 * Singleton
 *
 * <code>
 * namespace Application;
 * class My {
 *      use Singleton;
 * }
 *
 * $my = My::getInstance();
 * </code>
 */
trait Singleton
{
	/**
	 * @var static singleton instance
	 */
	protected static $instance;

	/**
	 * Get instance
	 *
	 * @return static
	 */
	public static function getInstance()
	{
		return static::$instance ?? (static::$instance = static::initInstance());
	}

	/**
	 * Initialization of class instance
	 *
	 * @return static
	 */
	private static function initInstance()
	{
		return new static();
	}

	/**
	 * Reset instance
	 *
	 * @return void
	 */
	public static function resetInstance(): void
	{
		static::$instance = null;
	}

	/**
	 * Disabled by access level
	 */
	private function __construct()
	{
	}

	/**
	 * Disabled by access level
	 */
	private function __clone()
	{
	}
}
