<?
namespace Hipot\IbAbstractLayer\Types;

/**
 * Оболочка над объектом информационного блока, возвращаемого цепочкой CIBlockElement::GetList()->GetNext()
 * Доступность полей определяется наличием переданного в конструктор массива $arItem
 *
 * @author hipot
 * @version 1.0
 *
 */
class IblockElementItem extends Base
{
	/**
	 * ID элемента
	 * @var int
	 */
	public $ID;

	/**
	 * Мнемонический идентификатор
	 * @var string
	 */
	public $CODE;

	/**
	 * EXTERNAL_ID или XML_ID Внешний идентификатор
	 * @var string
	 */
	public $EXTERNAL_ID;

	/**
	 * EXTERNAL_ID или XML_ID Внешний идентификатор
	 * @var string
	 */
	public $XML_ID;

	/**
	 * Название элемента
	 * @var string
	 */
	public $NAME;

	/**
	 * ID информационного блока.
	 * @var int;
	 */
	public $IBLOCK_ID;

	/**
	 * ID группы. Если не задан, то элемент не привязан к группе. Если элемент привязан
	 * к нескольким группам, то в этом поле ID одной из групп. По умолчанию содержит
	 * привязку к разделу с минимальным ID.
	 * @var int
	 */
	public $IBLOCK_SECTION_ID;

	/**
	 * Символический код инфоблока
	 * @var string
	 */
	public $IBLOCK_CODE;

	/**
	 * Флаг активности (Y|N)
	 * @var string
	 */
	public $ACTIVE;

	/**
	 * Дата начала действия элемента
	 * @var string
	 */
	public $DATE_ACTIVE_FROM;

	/**
	 * Дата окончания действия элемента
	 * @var string
	 */
	public $DATE_ACTIVE_TO;

	/**
	 * Порядок сортировки элементов между собой (в пределах одной группы-родителя)
	 * @var int
	 */
	public $SORT;

	/**
	 * Код картинки в таблице файлов для предварительного просмотра (анонса)
	 * @var int
	 */
	public $PREVIEW_PICTURE;

	/**
	 * Предварительное описание (анонс)
	 * @var string
	 */
	public $PREVIEW_TEXT;

	/**
	 * Тип предварительного описания (text/html)
	 * @var string
	 */
	public $PREVIEW_TEXT_TYPE;

	/**
	 * Код картинки в таблице файлов для детального просмотра
	 * @var int
	 */
	public $DETAIL_PICTURE;

	/**
	 * Детальное описание
	 * @var string
	 */
	public $DETAIL_TEXT;

	/**
	 * Тип детального описания (text/html)
	 * @var string
	 */
	public $DETAIL_TEXT_TYPE;

	/**
	 * Содержимое для поиска при фильтрации групп. Вычисляется автоматически.
	 * Складывается из полей NAME и DESCRIPTION (без html тэгов, если DESCRIPTION_TYPE
	 * установлен в html)
	 * @var string
	 */
	public $SEARCHABLE_CONTENT;

	/**
	 * Дата создания элемента
	 * @var string
	 */
	public $DATE_CREATE;

	/**
	 * Код пользователя, создавшего элемент
	 * @var int
	 */
	public $CREATED_BY;

	/**
	 * Имя пользователя, создавшего элемент (доступен только для чтения)
	 * @var string
	 */
	public $CREATED_USER_NAME;

	/**
	 * Время последнего изменения полей элемента
	 * @var string
	 */
	public $TIMESTAMP_X;

	/**
	 * Код пользователя, в последний раз изменившего элемент
	 * @var int
	 */
	public $MODIFIED_BY;

	/**
	 * Имя пользователя, в последний раз изменившего элемент. (доступен только для чтения)
	 * @var string
	 */
	public $USER_NAME;

	/**
	 * Путь к папке сайта. Определяется из параметров информационного блока.
	 * Изменяется автоматически. (доступен только для чтения)
	 * @var string
	 */
	public $LANG_DIR;

	/**
	 * Шаблон URL-а к странице для публичного просмотра списка элементов информационного
	 * блока. Определяется из параметров информационного блока. Изменяется
	 * автоматически. (доступен только для чтения)
	 * @var string
	 */
	public $LIST_PAGE_URL;

	/**
	 * Шаблон URL-а к странице для детального просмотра элемента. Определяется из
	 * параметров информационного блока. Изменяется автоматически. (доступен только
	 * для чтения)
	 * @var string
	 */
	public $DETAIL_PAGE_URL;

	/**
	 * Количество показов элемента (изменяется при вызове функции CIBlockElement::CounterInc).
	 * @var int
	 */
	public $SHOW_COUNTER;

	/**
	 * Дата первого показа элемента (изменяется при вызове функции CIBlockElement::CounterInc).
	 * @var string
	 */
	public $SHOW_COUNTER_START;

	/**
	 * Комментарий администратора документооборота.
	 * @var string
	 */
	public $WF_COMMENTS;

	/**
	 * Код статуса элемента в документообороте
	 * @var int
	 */
	public $WF_STATUS_ID;

	/**
	 * Текущее состояние блокированности на редактирование элемента. Может принимать
	 * значения: red - заблокирован, green - доступен для редактирования, yellow -
	 * заблокирован текущим пользователем.
	 * @var string
	 */
	public $LOCK_STATUS;

	/**
	 * Теги элемента. Используются для построения облака тегов модулем Поиска
	 * @var string
	 */
	public $TAGS;
	/*

	/**
	 * Динамичное создание итема из массива
	 * @param array $arItem массив c полями элемента CIBlockElement::GetList()
	 */
	public function __construct($arItem)
	{
		foreach ($arItem as $field => $value) {
			if (!isset($value) || $value === NULL) {
				continue;
			}
			if ($field == 'PROPERTIES') {
				// если множественное свойство, то это массив массивов
				foreach ($value as $propCode => $propArOrVal) {
					if ((int)$propArOrVal['ID'] <= 0) {
						$this->{$field}->{$propCode} = $this->generatePropObj($propArOrVal);
					} else {
						$this->{$field}->{$propCode} = new IblockElementItemPropertyValue($propArOrVal);
					}
				}

			} else {
				$this->{$field} = $value;
			}
		}
	}

	public function generatePropObj($value)
	{
		foreach ($value as $k => $sv) {
			$value[$k] = new IblockElementItemPropertyValue($sv);
		}
		return $value;
	}
}
?>