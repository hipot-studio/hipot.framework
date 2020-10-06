<?
namespace Hipot\Utils;

use ArrayAccess;

/**
 * Мини-объект, для работы с объектом как с массивом
 *
 * @see http://php.net/manual/ru/class.arrayaccess.php
 */
class ObjectArItem implements ArrayAccess
{
	/**
	 * Счетчик:
	 * пустые записи на добавление [] индексируются как AUTOINDEX_NN
	 * @var integer
	 */
	private $cnt_append = 0;

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

	public function offsetExists($offset): bool
	{
		return isset($this->{$offset});
	}

	public function offsetGet($offset)
	{
		if ($this->offsetExists($offset)) {
			return $this->{$offset};
		} else {
			return null;
		}
	}

	public function offsetSet($offset, $value): void
	{
		if (trim($offset) == '') {
			$offset = 'AUTOINDEX_' . $this->cnt_append++;
		}
		$this->{$offset} = $value;
	}

	public function offsetUnset($offset): void
	{
		if ($this->offsetExists($offset)) {
			unset($this->{$offset});
		}
	}
}
?>