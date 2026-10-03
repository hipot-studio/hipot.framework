<?php

namespace Hipot\Utils\Helper;

trait ObjectTools
{
	/**
	 * Создает объект анонимного класса с указанным трейтом
	 *
	 * Трейт не должен требовать аргументы конструктора или реализацию абстрактных методов.
	 *
	 * @param class-string $traitName Имя трейта
	 */
	public static function createObjectFromTrait(string $traitName): object
	{
		if (!trait_exists($traitName)) {
			throw new \InvalidArgumentException("The trait '{$traitName}' was not found.");
		}

		$traitName = (new \ReflectionClass($traitName))->getName();

		/** @var array<class-string, \Closure(): object> $factories */
		static $factories = [];
		$factories[$traitName] ??= eval(
			'return static fn (): object => new class { use \\' . $traitName . '; };'
		);

		return $factories[$traitName]();
	}

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
		$property->setValue(is_object($target) ? $target : null, $value);
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
		return self::getReflectionProperty($target, $propertyName)->getValue(
			is_object($target) ? $target : null,
		);
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
		return $property;
	}
}
