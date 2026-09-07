<?php

declare(strict_types=1);

namespace Hipot\Services;

use ArrayAccess;
use Closure;
use InvalidArgumentException;
use Memcache;
use UnexpectedValueException;

/**
 * Replaces Bitrix array-like global registries with persistent cache wrappers.
 */
final class GlobalsCacher
{
	/** @var array<string, array{0: Closure(): void, 1: Closure(): string}> */
	private array $globals;

	/** @var Closure(string, object): ArrayAccess */
	private Closure $wrapperFactory;

	/**
	 * @param object $connection Cache engine connection passed to the wrapper factory
	 * @param array<string, array{0: callable(): void, 1: callable(): string}> $globals
	 *        Global name => [initializer called before replacement, cache prefix provider]
	 * @param null|callable(string, object): ArrayAccess $wrapperFactory
	 */
	public function __construct(
		private readonly object $connection,
		array $globals,
		?callable $wrapperFactory = null,
	) {
		$this->globals = $this->normalizeGlobals($globals);
		$this->wrapperFactory = ($wrapperFactory ?? static function (string $prefix, object $connection): ArrayAccess {
			if (!$connection instanceof Memcache) {
				throw new InvalidArgumentException(
					'Memcache connection expected when the default wrapper factory is used.'
				);
			}
			
			return new MemcacheWrapper($prefix, $connection);
		})(...);
	}

	public function cache(): void
	{
		foreach ($this->globals as $globalName => [$beforeReplace, $prefixProvider]) {
			$beforeReplace();
			$prefix = $prefixProvider();
			$wrapper = ($this->wrapperFactory)($prefix, $this->connection);
			
			
			if (!$wrapper instanceof ArrayAccess) {
				throw new UnexpectedValueException('The cache wrapper factory must return an ArrayAccess instance.');
			}

			/** @noinspection GlobalVariableUsageInspection */
			$GLOBALS[$globalName] = $wrapper;
		}
	}

	/**
	 * @param array<string, array{0: callable(): void, 1: callable(): string}> $globals
	 * @return array<string, array{0: Closure(): void, 1: Closure(): string}>
	 */
	private function normalizeGlobals(array $globals): array
	{
		$normalized = [];

		foreach ($globals as $globalName => $configuration) {
			if (!is_string($globalName) || $globalName === '') {
				throw new InvalidArgumentException('A cached global name must be a non-empty string.');
			}
			if (
				!is_array($configuration)
				|| count($configuration) !== 2
				|| !isset($configuration[0], $configuration[1])
				|| !is_callable($configuration[0])
				|| !is_callable($configuration[1])
			) {
				throw new InvalidArgumentException(
					'Each cached global must contain an initializer and a cache prefix provider.'
				);
			}

			$normalized[$globalName] = [
				($configuration[0])(...),
				($configuration[1])(...),
			];
		}

		return $normalized;
	}
}
