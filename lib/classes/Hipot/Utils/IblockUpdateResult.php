<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 08.06.2017 0:00
 * @version pre 1.0
 */

namespace Hipot\Utils;

/**
 * Результат добавления или обновления сущностей инфоблока
 */
class IblockUpdateResult extends ObjectArItem
{
	/**
	 * идентификатор записи или текст ошибки
	 * @var int|string
	 */
	public $RESULT;

	/**
	 * OK | ERROR успешно, либо ошибка
	 * @var string
	 */
	public $STATUS;
}
