<?php

declare(strict_types=1);

namespace Hipot\Services;

use ArrayAccess;
use Closure;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Replaces Bitrix array-like global registries with persistent cache wrappers.
 */
final class GlobalsCacher
{
	/** @var array<string, array{0: Closure(): void, 1: Closure(): ArrayAccess}> */
	private array $globals;

	/**
	 * @param array<string, array{0: callable(): void, 1: callable(): ArrayAccess}> $globals
	 *        Global name => [initializer called before replacement, wrapper factory]
	 */
	public function __construct(array $globals)
	{
		$this->globals = $this->normalizeGlobals($globals);
	}

	public function cache(): void
	{
		foreach ($this->globals as $globalName => [$beforeReplace, $wrapperFactory]) {
			$beforeReplace();
			$wrapper = $wrapperFactory();

			if (!$wrapper instanceof ArrayAccess) {
				throw new UnexpectedValueException(sprintf(
					'The cache wrapper factory for $GLOBALS[\'%s\'] must return an ArrayAccess instance.',
					$globalName,
				));
			}

			/** @noinspection GlobalVariableUsageInspection */
			$GLOBALS[$globalName] = $wrapper;
		}
	}

	/**
	 * @param array<string, array{0: callable(): void, 1: callable(): ArrayAccess}> $globals
	 * @return array<string, array{0: Closure(): void, 1: Closure(): ArrayAccess}>
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
					'Each cached global must contain an initializer and an ArrayAccess wrapper factory.'
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
