<?
namespace Hipot\IbAbstractLayer\Types;

use \Hipot\IbAbstractLayer\IblockElemLinkedChains;

/**
 * Значение свойств инфоблока, возвращаемые CIBlockElement::GetProperty()
 * @author hipot
 * @version 1.0
 */
class IblockElementItemPropertyValue extends Base
{
	/**
	 * ID свойства
	 * @var int
	 */
	public $ID;

	/**
	 * Время последнего изменения свойства
	 * @var Datetime
	 */
	public $TIMESTAMP_X;

	/**
	 * Код информационного блока
	 * @var int
	 */
	public $IBLOCK_ID;

	/**
	 * Название свойства
	 * @var string
	 */
	public $NAME;

	/**
	 * Активность свойства (Y|N).
	 * @var string
	 */
	public $ACTIVE;

	/**
	 * Индекс сортировки
	 * @var int
	 */
	public $SORT;

	/**
	 * Мнемонический код свойства
	 * @var string
	 */
	public $CODE;

	/**
	 * Значение свойства по умолчанию (кроме свойства типа список L)
	 * @var string
	 */
	public $DEFAULT_VALUE;

	/**
	 * Тип свойства. Возможные значения: S - строка, N - число, F - файл, L - список,
	 * E - привязка к элементам, G - привязка к группам
	 * @var string
	 */
	public $PROPERTY_TYPE;

	/**
	 * Количество строк в ячейке ввода значения свойства
	 * @var int
	 */
	public $ROW_COUNT;

	/**
	 * Количество столбцов в ячейке ввода значения свойства
	 * @var int
	 */
	public $COL_COUNT;

	/**
	 * Тип для свойства список (L). Может быть "L" - выпадающий список или "C" - флажки
	 * @var string
	 */
	public $LIST_TYPE;

	/**
	 * Множественность (Y|N)
	 * @var string
	 */
	public $MULTIPLE;

	/**
	 * Внешний код свойства
	 * @var string
	 */
	public $XML_ID;

	/**
	 * Список допустимых расширений для свойств файл "F"(через запятую).
	 * @var string
	 */
	public $FILE_TYPE;

	/**
	 * Количество строк в выпадающем списке для свойств типа "список"
	 * @var int
	 */
	public $MULTIPLE_CNT;


	/**
	 * Для свойств типа привязки к элементам и группам задает код информационного блока
	 * с элементами/группами которого и будут связано значение.
	 * @var int
	 */
	public $LINK_IBLOCK_ID;

	/**
	 * Признак наличия у значения свойства дополнительного поля описания.
	 * Только для типов S - строка, N - число и F - файл (Y|N).
	 * @var string
	 */
	public $WITH_DESCRIPTION;

	/**
	 * Индексировать значения данного свойства (Y|N)
	 * @var string
	 */
	public $SEARCHABLE;

	/**
	 * Выводить поля для фильтрации по данному свойству на странице списка элементов
	 * в административном разделе
	 * @var string
	 */
	public $FILTRABLE;

	/**
	 * Обязательное (Y|N)
	 * @var string
	 */
	public $IS_REQUIRED;

	/**
	 * Флаг хранения значений свойств элементов инфоблока (1 - в общей таблице | 2 - в отдельной).
	 * (доступен только для чтения)
	 * @var int
	 */
	public $VERSION;

	/**
	 * Идентификатор пользовательского типа свойства
	 * @var string
	 */
	public $USER_TYPE;

	/**
	 * Свойства пользовательского типа
	 * @var array
	 */
	public $USER_TYPE_SETTINGS;

	/**
	 * ID значения свойства
	 * @var int
	 */
	public $PROPERTY_VALUE_ID;

	/**
	 * Значение свойства у элемента. Массив в случае свойств типа HTML/Text ([TEXT] => значение, [TYPE] => text|html)
	 * @var string|float|int|array
	 */
	public $VALUE;

	/**
	 * Дополнительное поле описания значения
	 * @var string
	 */
	public $DESCRIPTION;

	/**
	 * Значение варианта свойства
	 * @var string
	 */
	public $VALUE_ENUM;

	/**
	 * Внешний код варианта свойства
	 * @var string
	 */
	public $VALUE_XML_ID;

	public $TMP_ID;

	/**
	 * Создание объекта значения свойства
	 * @param array $arPropFlds результат схемы CIBlockElement::GetProperty()->GetNext()
	 */
	public function __construct($arPropFlds)
	{
		foreach ($arPropFlds as $fld => $value) {
			if ($fld == 'CHAIN') {
				$value = IblockElemLinkedChains::chainArrayToChainObject($value);
			}
			if ($fld == 'FILE_PARAMS') {
				$value = new ValueFile($value);
			}
			$this->{$fld} = $value;
		}
	}
}
?>