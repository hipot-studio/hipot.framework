<?php
namespace Hipot\Utils;

use Memcache;

/**
 * A lightweight wrapper around the PHP Memcached extension with three goals:
 *
 *  - You can specify a prefix to prepend to all keys.
 *  - You can use it exactly like a regular Memcache object.
 *  - You can access the cache like an array.
 *
 * example:
 * <pre>$cache = new MemcachedWrapper('foo');
 * $cache['bar'] = 'x';        // sets 'foobar' to 'x'
 * isset($cache['bar']);       // returns true
 * unset($cache['bar']);       // deletes 'foobar'
 * $cache->getMc()->set('bar', 'x')     // sets 'bar' to 'x'</pre>
 *
 * @see https://www.php.net/manual/ru/book.memcache.php
 */
class MemcacheWrapper implements \ArrayAccess
{
	/**
	 * Для исключения коллизий
	 * @var mixed|string
	 */
	private string $prefix;

	/**
	 * The underlying Memcached object, which you can access in order to
	 * override the prefix prepending if you really want.
	 */
	private Memcache $mc;

	/**
	 * MemcachedWrapper constructor.
	 *
	 * @param string    $prefix = '' Строковый префикс для группировки сходных данных в мемкеше
	 * @param Memcache  $mc Объект подключения к memcache
	 */
	public function __construct(string $prefix, Memcache $mc)
	{
		$this->prefix = trim($prefix);
		$this->mc = $mc;
	}

	/**
	 * получить прямой доступ к используемому объекту Memcache
	 * @return \Memcache
	 */
	public function getMc(): Memcache
	{
		return $this->mc;
	}

	/**
	 * Get all memcached keys. Special function because getAllKeys() is broken since memcached 1.4.23. Should only be needed on php 5.6
	 * @param array{host:string, port:string} $config
	 *
	 * @return array|int - all retrieved keys (or negative number on error)
	 */
	public function getMemcachedKeys(array $config)
	{
		$mem = @fsockopen($config['host'], $config['port']);
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

	/////// ArrayAccess initialization

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		if ($this->mc->get($this->prefix . $offset)) {
			return true;
		}
		return false;
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
	 * @throws \Hipot\Utils\MemcacheWrapperError
	 */
	public function offsetSet($offset, $value)
	{
		if ($offset === null) {
			throw new MemcacheWrapperError("Tried to set null offset");
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
}

