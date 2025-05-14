<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 08.06.2017 0:00
 * @version pre 1.0
 */

namespace Hipot\Types;

/**
 * Результат добавления или обновления сущностей (напр. инфоблока)
 */
final class UpdateResult extends ObjectArItem
{
	public const string STATUS_OK = 'OK';
	public const string STATUS_ERROR = 'ERROR';

	/**
	 * идентификатор записи или текст ошибки
	 * @var int|string
	 */
	public $RESULT;

	/**
	 * OK | ERROR успешно, либо ошибка
	 * @var mixed|string
	 */
	public $STATUS;

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
}
