<?php

declare(strict_types=1);

namespace Hipot\Services;

use RuntimeException;
use Throwable;

/**
 * Stores top-level array buckets in APCu and supports nested array writes.
 *
 * Values returned by offsetGet() are kept locally and returned by reference, so
 * expressions such as $cache[$bucket][$key] = $value work as with a plain array.
 * New buckets are published atomically with apcu_add(). Existing buckets are
 * only rewritten when their value changed during the request.
 */
final class ApcuNestedArrayWrapper implements \ArrayAccess
{
	private string $prefix;

	/** @var array<int|string, array<mixed>> */
	private array $values = [];

	/** @var array<int|string, array<mixed>> */
	private array $originalValues = [];

	/** @var array<int|string, true> */
	private array $loaded = [];

	/** @var array<int|string, true> */
	private array $existing = [];

	/** @var array<int|string, true> */
	private array $dirty = [];

	/** @var array<int|string, true> */
	private array $forceStore = [];

	public function __construct(
		string $prefix,
		private readonly int $ttl = 0,
	) {
		if ($ttl < 0) {
			throw new RuntimeException('APCu cache TTL must not be negative');
		}

		$this->prefix = trim($prefix);
		register_shutdown_function($this->flush(...));
	}

	public static function isAvailable(): bool
	{
		return function_exists('apcu_enabled')
			&& function_exists('apcu_fetch')
			&& function_exists('apcu_add')
			&& function_exists('apcu_store')
			&& function_exists('apcu_delete')
			&& apcu_enabled();
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
		if (!isset($this->loaded[$key]) || isset($this->existing[$key])) {
			$this->forceStore[$key] = true;
		}

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
			$this->originalValues[$key],
			$this->existing[$key],
			$this->dirty[$key],
			$this->forceStore[$key],
		);
		$this->loaded[$key] = true;

		try {
			apcu_delete($this->prefix . $key);
		} catch (Throwable) {
			// The in-process cache remains invalidated when APCu is unavailable.
		}
	}

	public function flush(): bool
	{
		$result = true;
		foreach (array_keys($this->dirty) as $key) {
			if (!isset($this->existing[$key])) {
				unset($this->dirty[$key], $this->forceStore[$key]);
				continue;
			}

			if (
				!isset($this->forceStore[$key])
				&& array_key_exists($key, $this->originalValues)
				&& $this->values[$key] === $this->originalValues[$key]
			) {
				unset($this->dirty[$key]);
				continue;
			}

			$stored = $this->store($key);
			if ($stored) {
				$this->originalValues[$key] = $this->values[$key];
				unset($this->dirty[$key], $this->forceStore[$key]);
			} else {
				$result = false;
			}
		}

		return $result;
	}

	/** @param int|string $key */
	private function store(int|string $key): bool
	{
		try {
			if (isset($this->forceStore[$key]) || array_key_exists($key, $this->originalValues)) {
				return apcu_store($this->prefix . $key, $this->values[$key], $this->ttl);
			}

			if (apcu_add($this->prefix . $key, $this->values[$key], $this->ttl)) {
				return true;
			}

			// Another request may have populated the same bucket first.
			$winner = apcu_fetch($this->prefix . $key, $success);
			if ($success && is_array($winner)) {
				$this->values[$key] = $winner;
				return true;
			}

			return false;
		} catch (Throwable) {
			return false;
		}
	}

	/** @return int|string */
	private function load(mixed $offset): int|string
	{
		$key = $this->normalizeOffset($offset);
		if (isset($this->loaded[$key])) {
			return $key;
		}

		try {
			$value = apcu_fetch($this->prefix . $key, $success);
		} catch (Throwable) {
			$value = false;
			$success = false;
		}

		$this->loaded[$key] = true;
		if ($success && is_array($value)) {
			$this->values[$key] = $value;
			$this->originalValues[$key] = $value;
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
