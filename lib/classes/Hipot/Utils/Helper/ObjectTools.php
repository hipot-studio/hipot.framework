<?php

namespace Hipot\Utils\Helper;

trait ObjectTools
{
	/**
	 * Устанавливает значение для приватного/защищенного поля объекта
	 *
	 * @param object|string $target Объект или имя класса
	 * @param string        $propertyName Имя поля
	 * @param mixed         $value Значение для установки
	 */
	public static function setPrivateProperty(object|string $target, string $propertyName, mixed $value): void
	{
		$property = self::getReflectionProperty($target, $propertyName);
		$property->setValue($target, $value);
	}

	/**
	 * Получает значение приватного/защищенного поля объекта
	 *
	 * @param object|string $target Объект или имя класса
	 * @param string        $propertyName Имя поля
	 *
	 * @return mixed Значение поля
	 */
	public static function getPrivateProperty(object|string $target, string $propertyName): mixed
	{
		return self::getReflectionProperty($target, $propertyName)->getValue($target);
	}

	/**
	 * Создает и настраивает объект ReflectionProperty
	 */
	private static function getReflectionProperty(object|string $target, string $propertyName): \ReflectionProperty
	{
		$ref = new \ReflectionClass($target);
		// Walk up the class hierarchy to find the property.
		while (! $ref->hasProperty($propertyName)) {
			$ref = $ref->getParentClass();
			if ($ref === false) {
				throw new \RuntimeException("The property '{$propertyName}' was not found.");
			}
		}
		$property = $ref->getProperty($propertyName);
		$property->setAccessible(true);
		return $property;
	}
}