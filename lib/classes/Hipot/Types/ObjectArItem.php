<?
namespace Hipot\Types;

use ArrayAccess;

/**
 * Мини-объект, для работы с объектом как с массивом
 *
 * @see http://php.net/manual/ru/class.arrayaccess.php
 */
class ObjectArItem implements ArrayAccess
{
	use ArrayLikeObject;

	/**
	 * Счетчик:
	 * пустые записи на добавление [] индексируются как AUTOINDEX_NN
	 * @var integer
	 */
	private int $cnt_append = 0;

	/**
	 * Создание объекта из массива
	 * @param array|null $result
	 */
	public function __construct($result = null)
	{
		if (is_array($result)) {
			foreach ($result as $k => $v) {
				$this->offsetSet($k, $v);
			}
		}
	}

	//// ArrayAccess interface init:

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->{$offset});
	}

	/**
	 * @param mixed $offset
	 *
	 * @return mixed|null
	 */
	public function offsetGet($offset)
	{
		if ($this->offsetExists($offset)) {
			return $this->{$offset};
		}
		return null;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value): void
	{
		if (trim($offset) == '') {
			$offset = 'AUTOINDEX_' . $this->cnt_append++;
		}
		$this->{$offset} = $value;
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset): void
	{
		if ($this->offsetExists($offset)) {
			unset($this->{$offset});
		}
	}

	// misc:

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
}
