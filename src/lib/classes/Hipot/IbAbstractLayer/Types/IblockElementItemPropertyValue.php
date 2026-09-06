<?php
namespace Hipot\IbAbstractLayer\Types;

use Hipot\IbAbstractLayer\IblockElemLinkedChains;
use Bitrix\Main\Type\DateTime;
use Bitrix\Iblock\PropertyTable;

/**
 * Значение свойств инфоблока, возвращаемые CIBlockElement::GetProperty()
 *
 * @property int $ID ID свойства
 * @property DateTime $TIMESTAMP_X Время последнего изменения свойства
 * @property int $IBLOCK_ID Код информационного блока
 * @property string $NAME Название свойства
 * @property string $ACTIVE Активность свойства (Y|N)
 * @property int SORT Индекс сортировки
 * @property string $CODE Мнемонический код свойства
 * @property string $DEFAULT_VALUE Значение свойства по умолчанию (кроме свойства типа список L)
 * @property string $PROPERTY_TYPE Тип свойства. Возможные значения: S - строка, N - число, F - файл, L - список, E - привязка к элементам, G - привязка к
 *     группам
 * @property int $ROW_COUNT Количество строк в ячейке ввода значения свойства
 * @property int $COL_COUNT
 * @property string $LIST_TYPE Тип для свойства список (L). Может быть "L" - выпадающий список или "C" - флажки
 * @property string $MULTIPLE Множественность (Y|N)
 * @property string $XML_ID Внешний код свойства
 * @property string $FILE_TYPE Список допустимых расширений для свойств файл "F" (через запятую)
 * @property int $MULTIPLE_CNT Количество строк в выпадающем списке для свойств типа "список"
 * @property int $LINK_IBLOCK_ID Для свойств типа привязки к элементам и группам задает код информационного блока с элементами/группами которого и будут
 *     связано значение.
 * @property string $WITH_DESCRIPTION Признак наличия у значения свойства дополнительного поля описания. Только для типов S - строка, N - число и F - файл
 *     (Y|N).
 * @property string $SEARCHABLE Индексировать значения данного свойства (Y|N)
 * @property string $FILTRABLE Выводить поля для фильтрации по данному свойству на странице списка элементов в административном разделе
 * @property string $IS_REQUIRED Обязательное (Y|N)
 * @property int $VERSION Флаг хранения значений свойств элементов инфоблока (1 - в общей таблице | 2 - в отдельной) (доступен только для чтения)
 * @property string $USER_TYPE Идентификатор пользовательского типа свойства
 * @property array $USER_TYPE_SETTINGS Свойства пользовательского типа
 * @property int $PROPERTY_VALUE_ID ID значения свойства
 * @property string|float|int|array $VALUE Значение свойства у элемента. Массив в случае свойств типа HTML/Text ([TEXT] => значение, [TYPE] => text|html)
 * @property string $DESCRIPTION Дополнительное поле описания значения
 * @property string $VALUE_ENUM Значение варианта свойства
 * @property string $VALUE_XML_ID Внешний код варианта свойства
 * @property string $TMP_ID
 * @property string $HINT Подсказка к свойству
 * @property string $VALUE_SORT
 */
class IblockElementItemPropertyValue extends Base
{
	/**
	 * Создание объекта значения свойства
	 * @param array $arPropFlds результат схемы CIBlockElement::GetProperty()->GetNext()
	 */
	public function __construct($arPropFlds)
	{
		$ignoredFields = ['IBLOCK_ID', 'VERSION', 'ROW_COUNT', 'COL_COUNT', 'SEARCHABLE', 'MULTIPLE_CNT', 'FILTRABLE'];

		foreach ($arPropFlds as $fld => $value) {
			if ($this->isEmptyValue($value) || in_array($fld, $ignoredFields)) {
				continue;
			}
			if ($fld == 'CHAIN') {
				$value = IblockElemLinkedChains::chainArrayToChainObject($value);
			}
			if ($fld == 'FILE_PARAMS') {
				$value = new ValueFile($value);
			}
			$this->{$fld} = $value;
		}
	}

	public function isFile(): bool
	{
		return isset($this->FILE_PARAMS) || $this->PROPERTY_TYPE == PropertyTable::TYPE_FILE;
	}

	public function isLinkElem(): bool
	{
		return isset($this->CHAIN) || $this->PROPERTY_TYPE == PropertyTable::TYPE_ELEMENT;
	}

	public function isList(): bool
	{
		return $this->PROPERTY_TYPE == PropertyTable::TYPE_LIST;
	}
}
