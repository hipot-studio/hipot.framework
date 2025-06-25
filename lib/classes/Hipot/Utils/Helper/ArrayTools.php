<?php
namespace Hipot\Utils\Helper;

trait ArrayTools
{
	/**
	 * Удаляет из ассоциативного массива ключи, начинающиеся с тильды (~)
	 * @param array $data Исходный ассоциативный массив с данными
	 * @return array Массив с удаленными ключами
	 */
	public static function removeTildaKeys(array $data): array
	{
		$deleteKeys = array_filter(array_keys($data), static function ($key) {
			return str_starts_with($key, '~');
		});
		foreach ($deleteKeys as $key) {
			unset($data[$key]);
		}
		return $data;
	}
}