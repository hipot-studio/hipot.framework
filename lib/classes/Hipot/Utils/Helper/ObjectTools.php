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
		return self::getReflectionProperty($target, $propertyName)->getValue();
	}

	/**
	 * Создает и настраивает объект ReflectionProperty
	 */
	private static function getReflectionProperty(object|string $target, string $propertyName): \ReflectionProperty
	{
		$property = new \ReflectionProperty($target, $propertyName);
		$property->setAccessible(true);
		return $property;
	}
}