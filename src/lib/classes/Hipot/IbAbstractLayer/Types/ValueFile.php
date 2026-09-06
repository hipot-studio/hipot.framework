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
 *
 * @property int $ID ID файла
 * @property int $TIMESTAMP_X Дата изменения записи
 * @property string $MODULE_ID Идентификатор модуля которому принадлежит файл.
 * @property int $WIDTH Ширина изображения (если файл - графический)
 * @property int $HEIGHT Высота изображения (если файл - графический)
 * @property int $FILE_SIZE Размер файла (байт)
 * @property string $CONTENT_TYPE MIME тип файла
 * @property string $SUBDIR Подкаталог в котором находится файл на диске. Основной каталог для хранения файлов "Папка по умолчанию для загрузки файлов" ("main", "upload_dir", "upload")
 * @property string $FILE_NAME Имя файла на диске сервера
 * @property string $ORIGINAL_NAME Оригинальное имя файла в момент загрузки его на сервер
 * @property string $DESCRIPTION Описание файла
 * @property string $SRC Путь к файлу начинающийся от каталога указанного в параметре DocumentRoot в настройках веб-сервера, заданный по правилам формирования URL-адресов: /ru/about/index.php
 * @property mixed $HANDLER_ID
 * @property string $EXTERNAL_ID
 * @property mixed $VERSION_ORIGINAL_ID
 * @property mixed $META
 */
final class ValueFile extends Base
{
	/**
	 * Создание объекта информации о файле
	 * @param array $arPropFlds результат, полученный через CFile::GetFileArray()
	 */
	public function __construct($arPropFlds)
	{
		foreach ($arPropFlds as $fld => $value) {
			if ($this->isEmptyValue($value)) {
				continue;
			}
			$this->{$fld} = $value;
		}
	}
}