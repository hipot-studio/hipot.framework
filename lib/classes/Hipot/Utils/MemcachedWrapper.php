<?php
namespace Hipot\Utils;

use Bitrix\Main\Config\Configuration,
	Memcache, ArrayAccess;


/**
 * A lightweight wrapper around the PHP Memcached extension with three goals:
 *
 *  - You can specify a prefix to prepend to all keys.
 *  - You can use it exactly like a regular Memcached object.
 *  - You can access the cache like an array.
 *
 * Example:
 *
 * $cache = new MemcachedWrapper('foo');
 * $cache['bar'] = 'x';        // sets 'foobar' to 'x'
 * isset($cache['bar']);       // returns true
 * unset($cache['bar']);       // deletes 'foobar'
 * $cache->set('bar', 'x')     // sets 'foobar' to 'x'
 */
class MemcachedWrapper implements ArrayAccess
{
	/**
	 * Memcached methods that take key(s) as arguments, and the argument
	 * position of those key(s).
	 */
	protected $keyArgMethods = [
		'add'           => 0,
		'addByKey'      => 1,
		'append'        => 0,
		'appendByKey'   => 0,
		'cas'           => 1,
		'casByKey'      => 2,
		'decrement'     => 0,
		'delete'        => 0,
		'deleteByKey'   => 1,
		'get'           => 0,
		'getByKey'      => 1,
		'getDelayed'    => 0,
		'getDelayedByKey' => 1,
		'getMulti'      => 0,
		'getMultiByKey' => 1,
		'increment'     => 0,
		'prepend'       => 0,
		'prependByKey'  => 1,
		'replace'       => 0,
		'replaceByKey'  => 1,
		'set'           => 0,
		'setByKey'      => 1,
		'setMulti'      => 0,
		'setMultiByKey' => 1,
	];
	protected $prefix;

	/**
	 * The underlying Memcached object, which you can access in order to
	 * override the prefix prepending if you really want.
	 */
	public $mc;

	public function __construct($prefix = '')
	{
		$this->prefix = $prefix;
		$this->mc = new Memcache();

		$cacheConfig = Configuration::getValue("cache");
		$v = (isset($cacheConfig["memcache"])) ? $cacheConfig["memcache"] : null;

		if ($v != null && isset($v["port"])) {
			$port = (int)$v["port"];
		} else {
			$port = 11211;
		}

		if (trim($v["host"]) == '') {
			$v["host"] = 'localhost';
		}

		if (! $this->mc->pconnect($v["host"], $port)) {
			throw new MemcachedWrapperError("Cant connect to memmcached: " . $v["host"]);
		}
	}

	public function __call($name, $args)
	{
		if (!is_callable(array($this->mc, $name))) {
			throw new MemcachedWrapperError("Unknown method: $name");
		}

		// find the position of the argument with key(s), if any
		if (isset($this->keyArgMethods[$name])) {
			$pos = $this->keyArgMethods[$name];
			// prepend prefix to key(s)
			if (strpos($name, 'setMulti') !== false) {
				$new = array();
				foreach ($args[$pos] as $k => $v) {
					$new[$this->prefix . $k] = $v;
				}
				$args[$pos] = $new;
			} else if (strpos($name, 'Multi') !== false || strpos($name, 'Delayed') !== false) {
				$new = array();
				foreach ($args[$pos] as $k) {
					$new[] = $this->prefix . $k;
				}
				$args[$pos] = $new;
			} else {
				$args[$pos] = $this->prefix . $args[$pos];
			}
		}
		$result = call_user_func_array([$this->mc, $name], $args);
		// process keys in return value if necessary
		$prefixLen = strlen($this->prefix);
		$process = function ($r) use ($prefixLen) {
			$r['key'] = substr($r['key'], $prefixLen);
			return $r;
		};
		if ($name == 'fetch' && is_array($result)) {
			return $process($result);
		} else if ($name == 'fetchAll' && is_array($result)) {
			return array_map($process, $result);
		} else if (strpos($name, 'getMulti') === 0 && is_array($result)) {
			$new = array();
			foreach ($result as $k => $v) {
				$new[substr($k, strlen($this->prefix))] = $v;
			}
			return $new;
		} else {
			return $result;
		}

	}

	public function offsetExists($offset)
	{
		if ($this->mc->get($this->prefix . $offset)) {
			return true;
		} else if ($this->mc->getResultCode() != Memcached::RES_NOTFOUND) {
			return true;
		} else {
			return false;
		}
	}

	public function offsetGet($offset)
	{
		return $this->mc->get($this->prefix . $offset);
	}

	public function offsetSet($offset, $value)
	{
		if ($offset === null) {
			throw new MemcachedWrapperError("Tried to set null offset");
		}
		return $this->mc->set($this->prefix . $offset, $value);
	}

	public function offsetUnset($offset)
	{
		return $this->mc->delete($this->prefix . $offset);
	}
}

