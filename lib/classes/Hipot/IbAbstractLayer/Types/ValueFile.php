<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 08.06.2017 0:06
 * @version pre 1.0
 */

namespace Hipot\IbAbstractLayer\Types;

/**
 * Объект информации о файле, полученный через CFile::GetFileArray()
 * @author hipot
 * @version 1.0
 */
class ValueFile extends Base
{
	/**
	 * ID файла
	 * @var int
	 */
	public $ID;

	/**
	 * Дата изменения записи
	 * @var timestamp
	 */
	public $TIMESTAMP_X;

	/**
	 * Идентификатор модуля которому принадлежит файл.
	 * @var string
	 */
	public $MODULE_ID;

	/**
	 * Высота изображения (если файл - графический).
	 * @var int
	 */
	public $HEIGHT;

	/**
	 * Ширина изображения (если файл - графический).
	 * @var int
	 */
	public $WIDTH;

	/**
	 * Размер файла (байт)
	 * @var int
	 */
	public $FILE_SIZE;

	/**
	 * MIME тип файла
	 * @var string
	 */
	public $CONTENT_TYPE;

	/**
	 * Подкаталог в котором находится файл на диске. Основной каталог для хранения файлов
	 * задается в параметре "Папка по умолчанию для загрузки файлов" в настройках главного
	 * модуля, значение данного параметра программно можно получить с помощью вызова
	 * функции: COption::GetOptionString("main", "upload_dir", "upload");
	 * @var string
	 */
	public $SUBDIR;

	/**
	 * Имя файла на диске сервера
	 * @var string
	 */
	public $FILE_NAME;

	/**
	 * Оригинальное имя файла в момент загрузки его на сервер
	 * @var string
	 */
	public $ORIGINAL_NAME;

	/**
	 * Описание файла
	 * @var string
	 */
	public $DESCRIPTION;

	/**
	 * Функция возвращает путь от корня к зарегистрированному файлу.
	 * Путь к файлу начинающийся от каталога указанного в параметре DocumentRoot в
	 * настройках веб-сервера, заданный по правилам формирования URL-адресов.
	 * Пример: /ru/about/index.php
	 * @var string
	 */
	public $SRC;

	/**
	 * Создание объекта информации о файле
	 * @param array $arPropFlds результат, полученный через CFile::GetFileArray()
	 */
	public function __construct($arPropFlds)
	{
		foreach ($arPropFlds as $fld => $value) {
			$this->{$fld} = $value;
		}
	}
}