<?php

declare(strict_types=1);

namespace Hipot\Services;

use ArrayAccess;
use InvalidArgumentException;
use ReflectionClass;
use UnexpectedValueException;

/**
 * Replaces static array-like class properties with ArrayAccess wrappers.
 */
final class StaticPropertiesCacher
{
	/** @var array<class-string, array<string, callable(): ArrayAccess>> */
	private array $classes;

	/**
	 * @param array<class-string, array<string, callable(): ArrayAccess>> $classes
	 *        Class name => [static property name => cache wrapper provider]
	 */
	public function __construct(array $classes)
	{
		$this->classes = $classes;
		$this->validateConfiguration();
	}

	public function cache(): void
	{
		foreach ($this->classes as $className => $properties) {
			$reflection = new ReflectionClass($className);

			foreach ($properties as $propertyName => $wrapperProvider) {
				$property = $reflection->getProperty($propertyName);
				if (!$property->isStatic()) {
					throw new UnexpectedValueException(sprintf(
						'Property %s::$%s must be static.',
						$className,
						$propertyName,
					));
				}

				$wrapper = $wrapperProvider();
				if (!$wrapper instanceof ArrayAccess) {
					throw new UnexpectedValueException(sprintf(
						'The cache wrapper for %s::$%s must implement ArrayAccess.',
						$className,
						$propertyName,
					));
				}

				$property->setValue(null, $wrapper);
			}
		}
	}

	private function validateConfiguration(): void
	{
		foreach ($this->classes as $className => $properties) {
			if (!is_string($className) || $className === '' || !class_exists($className)) {
				throw new InvalidArgumentException('A cached class must be an existing class name.');
			}
			if (!is_array($properties) || $properties === []) {
				throw new InvalidArgumentException('Each cached class must contain at least one static property.');
			}

			$reflection = new ReflectionClass($className);
			foreach ($properties as $propertyName => $wrapperProvider) {
				if (
					!is_string($propertyName)
					|| $propertyName === ''
					|| !$reflection->hasProperty($propertyName)
					|| !is_callable($wrapperProvider)
				) {
					throw new InvalidArgumentException(
						'Each cached static property must exist and have a callable wrapper provider.'
					);
				}
			}
		}
	}
}
