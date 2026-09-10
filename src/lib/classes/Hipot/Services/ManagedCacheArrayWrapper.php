<?php

declare(strict_types=1);

namespace Hipot\Services;

use ArrayAccess;
use Bitrix\Main\Data\ManagedCache;
use RuntimeException;

/**
 * Exposes individual ManagedCache entries through array syntax.
 *
 * Existence is determined by ManagedCache::read(), so a stored false value is
 * distinguishable from a missing entry.
 *
 * @implements ArrayAccess<int|string, mixed>
 */
final class ManagedCacheArrayWrapper implements ArrayAccess
{
	public function __construct(
		private readonly string $prefix,
		private readonly ManagedCache $managedCache,
		private readonly int $ttl,
		private readonly string|false $tableId = false,
	) {
		if ($this->prefix === '') {
			throw new RuntimeException('A managed cache prefix must not be empty.');
		}
		if ($this->ttl <= 0) {
			throw new RuntimeException('Managed cache TTL must be greater than zero.');
		}
	}

	public function offsetExists(mixed $offset): bool
	{
		return $this->managedCache->read(
			$this->ttl,
			$this->getCacheId($offset),
			$this->tableId,
		);
	}

	public function offsetGet(mixed $offset): mixed
	{
		$cacheId = $this->getCacheId($offset);
		if (!$this->managedCache->read($this->ttl, $cacheId, $this->tableId)) {
			return null;
		}

		return $this->managedCache->get($cacheId);
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		if ($offset === null) {
			throw new RuntimeException('Tried to set a null cache offset.');
		}

		$cacheId = $this->getCacheId($offset);
		// ManagedCache::set() only accepts values for an initialized entry.
		$this->managedCache->read($this->ttl, $cacheId, $this->tableId);
		$this->managedCache->set($cacheId, $value);
	}

	public function offsetUnset(mixed $offset): void
	{
		$this->managedCache->clean($this->getCacheId($offset), $this->tableId);
	}

	private function getCacheId(mixed $offset): string
	{
		if (!is_int($offset) && !is_string($offset)) {
			throw new RuntimeException('A managed cache offset must be an integer or a string.');
		}

		return $this->prefix . $offset;
	}
}
