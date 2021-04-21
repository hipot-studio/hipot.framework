<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 08.06.2017 0:00
 * @version pre 1.0
 */

namespace Hipot\Utils;

/**
 * Результат добавления или обновления сущностей (напр. инфоблока)
 */
class UpdateResult extends ObjectArItem
{
	public const STATUS_OK         = 'OK';
	public const STATUS_ERROR      = 'ERROR';

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
}
