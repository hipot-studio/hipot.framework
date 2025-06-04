<?php
namespace Hipot\Utils;

use Bitrix\Main\Config\Configuration,
	Memcache, ArrayAccess;

/**
 * A lightweight wrapper around the PHP Memcached extension with three goals:
 *
 *  - You can specify a prefix to prepend to all keys.
 *  - You can use it exactly like a regular Memcache object.
 *  - You can access the cache like an array.
 *
 * Example:
 *
 * $cache = new MemcachedWrapper('foo');
 * $cache['bar'] = 'x';        // sets 'foobar' to 'x'
 * isset($cache['bar']);       // returns true
 * unset($cache['bar']);       // deletes 'foobar'
 * $cache->mc->set('bar', 'x')     // sets 'bar' to 'x'
 */
class MemcachedWrapper implements ArrayAccess
{
	/**
	 * @var mixed|string
	 */
	protected $prefix;

	/**
	 * The underlying Memcached object, which you can access in order to
	 * override the prefix prepending if you really want.
	 */
	public Memcache $mc;

	/**
	 * @var array
	 */
	protected array $serverAddr = [
		'host'  => 'localhost',
		'port'  => 11211
	];

	/**
	 * Возвращает адрес для подключения к сокету
	 * @param bool $socket = true
	 * @return array
	 */
	public static function getServerAddr($socket = true): array
	{
		$v = [
			'host'  => 'localhost',
			'port'  => 11211
		];
		if ($socket) {
			// socket
			$v["host"] = 'unix:///home/bitrix/memcached.sock';
			$v["port"] = 0;
		} else {
			$cacheConfig = Configuration::getValue("cache");
			$vS = (isset($cacheConfig["memcache"])) ? $cacheConfig["memcache"] : null;

			if ($vS != null && isset($vS["port"])) {
				$v["port"] = (int)$vS["port"];
			}
			if (trim($vS["host"]) != '') {
				$v["host"] = $vS["host"];
			}
		}
		return $v;
	}

	/**
	 * MemcachedWrapper constructor.
	 *
	 * @param string $prefix = '' Строковый префикс для группировки сходных данных в мемкеше
	 * @throws \Hipot\Utils\MemcachedWrapperError
	 */
	public function __construct($prefix = '')
	{
		$this->prefix = $prefix;
		$this->mc = new Memcache();

		$v = $this->serverAddr = self::getServerAddr();

		//$this->mc->addServer($v["host"], $v["port"]);
		if (! $this->mc->pconnect($v["host"], $v["port"])) {
			throw new MemcachedWrapperError("Cant connect to memmcached: " . $v["host"]);
		}
	}

	// region only to Memcached
	/**
	 * Memcached methods that take key(s) as arguments, and the argument
	 * position of those key(s).
	 */
	protected array $keyArgMethods = [
		'add'               => 0,
		'addByKey'          => 1,
		'append'            => 0,
		'appendByKey'       => 0,
		'cas'               => 1,
		'casByKey'          => 2,
		'decrement'         => 0,
		'delete'            => 0,
		'deleteByKey'       => 1,
		'get'               => 0,
		'getByKey'          => 1,
		'getDelayed'        => 0,
		'getDelayedByKey'   => 1,
		'getMulti'          => 0,
		'getMultiByKey'     => 1,
		'increment'         => 0,
		'prepend'           => 0,
		'prependByKey'      => 1,
		'replace'           => 0,
		'replaceByKey'      => 1,
		'set'               => 0,
		'setByKey'          => 1,
		'setMulti'          => 0,
		'setMultiByKey'     => 1,
	];

	/**
	 * Магический вызов метода из $this->keyArgMethods
	 * @param $name
	 * @param $args
	 *
	 * @return array|false|mixed
	 * @throws \Hipot\Utils\MemcachedWrapperError
	 */
	public function __call($name, $args)
	{
		if (!is_callable([$this->mc, $name])) {
			throw new MemcachedWrapperError("Unknown method: $name");
		}

		// find the position of the argument with key(s), if any
		if (isset($this->keyArgMethods[$name])) {
			$pos = $this->keyArgMethods[$name];
			// prepend prefix to key(s)
			if (strpos($name, 'setMulti') !== false) {
				$new = [];
				foreach ($args[$pos] as $k => $v) {
					$new[$this->prefix . $k] = $v;
				}
				$args[$pos] = $new;
			} else if (strpos($name, 'Multi') !== false || strpos($name, 'Delayed') !== false) {
				$new = [];
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
		$process = static function ($r) use ($prefixLen) {
			$r['key'] = substr($r['key'], $prefixLen);
			return $r;
		};
		if ($name == 'fetch' && is_array($result)) {
			return $process($result);
		} else if ($name == 'fetchAll' && is_array($result)) {
			return array_map($process, $result);
		} else if (strpos($name, 'getMulti') === 0 && is_array($result)) {
			$new = [];
			foreach ($result as $k => $v) {
				$new[substr($k, strlen($this->prefix))] = $v;
			}
			return $new;
		} else {
			return $result;
		}
	}
	// endregion

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		if ($this->mc->get($this->prefix . $offset)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param mixed $offset
	 *
	 * @return array|false|mixed|string
	 */
	public function offsetGet($offset)
	{
		return $this->mc->get($this->prefix . $offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 *
	 * @return bool|void
	 * @throws \Hipot\Utils\MemcachedWrapperError
	 */
	public function offsetSet($offset, $value)
	{
		if ($offset === null) {
			throw new MemcachedWrapperError("Tried to set null offset");
		}
		return $this->mc->set($this->prefix . $offset, $value);
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool|void
	 */
	public function offsetUnset($offset)
	{
		return $this->mc->delete($this->prefix . $offset);
	}

	/**
	 * Get all memcached keys. Special function because getAllKeys() is broken since memcached 1.4.23. Should only be needed on php 5.6
	 *
	 * cleaned up version of code found on Stackoverflow.com by Maduka Jayalath
	 *
	 * @return array|int - all retrieved keys (or negative number on error)
	 */
	public function getMemcachedKeys()
	{
		$v = $this->serverAddr;

		$mem = @fsockopen($v['host'], $v['port']);
		if ($mem === false) {
			return -1;
		}

		// retrieve distinct slab
		$r = @fwrite($mem, 'stats items' . chr(10));
		if ($r === false) {
			return -2;
		}

		$slab = [];
		while (($l = @fgets($mem, 1024)) !== false) {
			// finished?
			$l = trim($l);
			if ($l == 'END') {
				break;
			}

			$m = [];
			// <STAT items:22:evicted_nonzero 0>
			$r = preg_match('/^STAT\sitems\:(\d+)\:/', $l, $m);
			if ($r != 1) {
				return -3;
			}
			$a_slab = $m[1];

			if (!array_key_exists($a_slab, $slab)) {
				$slab[$a_slab] = [];
			}
		}

		reset($slab);
		foreach ($slab as $a_slab_key => &$a_slab) {
			$r = @fwrite($mem, 'stats cachedump ' . $a_slab_key . ' 100' . chr(10));
			if ($r === false) {
				return -4;
			}

			while (($l = @fgets($mem, 1024)) !== false) {
				// finished?
				$l = trim($l);
				if ($l == 'END') {
					break;
				}

				$m = [];
				// ITEM 42 [118 b; 1354717302 s]
				$r = preg_match('/^ITEM\s([^\s]+)\s/', $l, $m);
				if ($r != 1) {
					return -5;
				}
				$a_key = $m[1];

				$a_slab[] = $a_key;
			}
		}

		// close the connection
		@fclose($mem);
		unset($mem);

		$keys = [];
		reset($slab);
		foreach ($slab as &$a_slab) {
			reset($a_slab);
			foreach ($a_slab as &$a_key) {
				$keys[] = $a_key;
			}
		}
		unset($slab);

		foreach ($keys as &$k) {
			$k = str_replace($this->prefix, '', $k);
		}
		return $keys;
	}
}

