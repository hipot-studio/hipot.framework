<?
namespace Hipot\Types;

use ArrayAccess;

/**
 * Мини-объект, для работы с объектом как с массивом (предпочтительно для хранения одного элемента)
 */
#[\AllowDynamicProperties]
class ObjectArItem implements ArrayAccess
{
	use Container\ArrayAccess;

	/**
	 * Рекурсивное преобразование объекта в массив
	 *
	 * @param object|array|mixed $obj объект для преобразования
	 * @return array|mixed
	 */
	public static function toArr($obj): array
	{
		if (!is_object($obj) && !is_array($obj)) {
			return $obj;
		}
		if (is_object($obj)) {
			$obj = get_object_vars($obj);
		}
		if (is_array($obj)) {
			foreach ($obj as $key => $val) {
				$obj[$key] = self::toArr($val);
			}
		}
		return $obj;
	}

	/**
	 * Создает объект из массива данных
	 * @param array $item Массив данных для создания объекта
	 * @return static Возвращает созданный объект
	 */
	public static function fromArr(array $item = []): static
	{
		$object = new static();
		foreach ($item as $key => $value) {
			$object->offsetSet($key, $value);
		}
		return $object;
	}
}
