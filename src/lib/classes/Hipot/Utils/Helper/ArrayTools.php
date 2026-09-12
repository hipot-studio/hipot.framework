<?php
namespace Hipot\Utils\Helper;

trait ArrayTools
{
	/**
	 * Преобразует вложенный массив в плоский массив с составными ключами.
	 *
	 * Пустые массивы сохраняются как значения. Ключи, содержащие разделитель,
	 * при обратном преобразовании будут интерпретированы как составные.
	 *
	 * @param array $array Исходный вложенный массив
	 * @param string $separator Разделитель частей ключа
	 * @param string $prefix Префикс составного ключа
	 * @return array
	 */
	public static function flatten(array $array, string $separator = '.', string $prefix = ''): array
	{
		if ($separator === '') {
			throw new \InvalidArgumentException('Array key separator must not be empty');
		}

		$result = [];
		foreach ($array as $key => $value) {
			$compoundKey = $prefix === ''
				? (string)$key
				: $prefix . $separator . $key;

			if (is_array($value) && $value !== []) {
				$result = array_replace(
					$result,
					self::flatten($value, $separator, $compoundKey),
				);
				continue;
			}

			$result[$compoundKey] = $value;
		}

		return $result;
	}

	/**
	 * Преобразует плоский массив с составными ключами во вложенный.
	 *
	 * Если пути конфликтуют, более позднее значение заменяет ранее собранную
	 * ветку либо становится ее новой веткой.
	 *
	 * @param array $array Исходный плоский массив
	 * @param string $separator Разделитель частей ключа
	 * @return array
	 */
	public static function unflatten(array $array, string $separator = '.'): array
	{
		if ($separator === '') {
			throw new \InvalidArgumentException('Array key separator must not be empty');
		}

		$result = [];
		foreach ($array as $compoundKey => $value) {
			$path = explode($separator, (string)$compoundKey);
			$lastKey = array_pop($path);
			$target =& $result;

			foreach ($path as $key) {
				if (!array_key_exists($key, $target) || !is_array($target[$key])) {
					$target[$key] = [];
				}
				$target =& $target[$key];
			}

			$target[$lastKey] = $value;
			unset($target);
		}

		return $result;
	}

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
