<?php

namespace Hipot\Services;

use Hipot\Types\Proxy;
use Hipot\Types\Registry as Instance;

/**
 * Proxy to Registry
 *
 * Example of usage
 * <code>
 *     use Hipot\Services\Registry;
 *
 *     Registry::set('key', 'value');
 *     Registry::get('key');
 * </code>
 *
 * @method   static Instance getInstance()
 *
 * @method   static void  set($key, $value)
 * @see      Instance::set()
 *
 * @method   static mixed get($key)
 * @see      Instance::get()
 *
 * @method   static bool  contains($key)
 * @see      Instance::contains()
 *
 * @method   static void  delete($key)
 * @see      Instance::delete()
 */
final class Registry
{
	use Proxy;

	/**
	 * Init instance to use some presets
	 * @return Instance
	 */
	private static function initInstance(): Instance
	{
		$instance = new Instance();
		return $instance;
	}
}
