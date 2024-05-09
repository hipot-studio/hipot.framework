<?
namespace Hipot\Types;

/**
 * Мини-объект, для работы с объектом как с массивом (предпочтительно для хранения одного элемента)
 * Похож на SPL ArrayObject, только со своими нюансами
 */
#[\AllowDynamicProperties]
class ObjectArItem implements \ArrayAccess, \Countable
{
	use Container\Container;
	use Container\ArrayAccess;
	use Container\MagicAccess;
	use Container\MagicChain;

	/**
	 * unsafe method, use only in test goals because may create trash-keys
	 */
	private bool $useMagicChain = false;

	public static function create(bool $useMagicChain = false): static
	{
		$object = new self();
		$object->useMagicChain = $useMagicChain;
		return $object;
	}

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
	public static function fromArr(array $item = [], bool $useMagicChain = false): static
	{
		$object = self::create($useMagicChain);
		foreach ($item as $key => $value) {
			if (is_array($value)) {
				$object->offsetSet($key, self::fromArr($value));
			} else {
				$object->offsetSet($key, $value);
			}
		}
		return $object;
	}
}
