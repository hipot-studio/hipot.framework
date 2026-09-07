<?php

declare(strict_types=1);

namespace Hipot\Services;

use Memcache;
use RuntimeException;
use Throwable;

/**
 * Stores top-level array buckets in Memcache and supports nested array writes.
 *
 * Values returned by offsetGet() are kept locally and returned by reference, so
 * expressions such as $cache[$bucket][$key] = $value work as with a plain array.
 * Modified buckets are written at the end of the request or by an explicit
 * flush() call.
 */
final class MemcacheNestedArrayWrapper implements \ArrayAccess
{
	private string $prefix;

	/** @var array<int|string, array<mixed>> */
	private array $values = [];

	/** @var array<int|string, true> */
	private array $loaded = [];

	/** @var array<int|string, true> */
	private array $existing = [];

	/** @var array<int|string, true> */
	private array $dirty = [];

	public function __construct(
		string $prefix,
		private readonly Memcache $memcache,
	) {
		$this->prefix = trim($prefix);
		register_shutdown_function($this->flush(...));
	}

	public function getMemcache(): Memcache
	{
		return $this->memcache;
	}

	public function offsetExists(mixed $offset): bool
	{
		$key = $this->load($offset);

		return isset($this->existing[$key]);
	}

	public function &offsetGet(mixed $offset): mixed
	{
		$key = $this->load($offset);
		if (!isset($this->existing[$key])) {
			$this->values[$key] = [];
			$this->existing[$key] = true;
		}

		// offsetGet() cannot detect whether the returned reference is changed.
		// Marking the bucket dirty also makes read-modify-write expressions safe.
		$this->dirty[$key] = true;

		return $this->values[$key];
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		if ($offset === null) {
			throw new RuntimeException('Tried to set null offset');
		}
		if (!is_array($value)) {
			throw new RuntimeException('A nested cache bucket must be an array');
		}

		$key = $this->normalizeOffset($offset);
		$this->values[$key] = $value;
		$this->loaded[$key] = true;
		$this->existing[$key] = true;
		$this->dirty[$key] = true;
	}

	public function offsetUnset(mixed $offset): void
	{
		$key = $this->normalizeOffset($offset);
		unset(
			$this->values[$key],
			$this->existing[$key],
			$this->dirty[$key],
		);
		$this->loaded[$key] = true;
		try {
			$this->memcache->delete($this->prefix . $key);
		} catch (Throwable) {
			// The in-process cache remains invalidated when Memcache is unavailable.
		}
	}

	public function flush(): bool
	{
		$result = true;
		foreach (array_keys($this->dirty) as $key) {
			if (!isset($this->existing[$key])) {
				unset($this->dirty[$key]);
				continue;
			}

			try {
				$stored = $this->memcache->set($this->prefix . $key, $this->values[$key]);
			} catch (Throwable) {
				$stored = false;
			}

			if ($stored) {
				unset($this->dirty[$key]);
			} else {
				$result = false;
			}
		}

		return $result;
	}

	/** @return int|string */
	private function load(mixed $offset): int|string
	{
		$key = $this->normalizeOffset($offset);
		if (isset($this->loaded[$key])) {
			return $key;
		}

		try {
			$value = $this->memcache->get($this->prefix . $key);
		} catch (Throwable) {
			$value = false;
		}
		$this->loaded[$key] = true;
		if (is_array($value)) {
			$this->values[$key] = $value;
			$this->existing[$key] = true;
		}

		return $key;
	}

	/** @return int|string */
	private function normalizeOffset(mixed $offset): int|string
	{
		if (is_int($offset) || is_string($offset)) {
			return $offset;
		}

		throw new RuntimeException('A nested cache bucket key must be an integer or a string');
	}
}
