<?php
namespace Hipot\IbAbstractLayer\Types;

use Hipot\Types\ObjectArItem;

/**
 * Оболочка над объектом информационного блока, возвращаемого цепочкой CIBlockElement::GetList()->GetNext()
 * Доступность полей определяется наличием переданного в конструктор массива $arItem
 *
 * @author hipot
 * @version 1.0
 *
 * @property int $ID ID элемента
 * @property string $CODE Мнемонический идентификатор
 * @property string $EXTERNAL_ID Внешний идентификатор
 * @property string $XML_ID Внешний идентификатор
 * @property string $NAME Название элемента
 * @property int $IBLOCK_ID ID информационного блока
 * @property string $IBLOCK_TYPE_ID
 * @property string $IBLOCK_EXTERNAL_ID
 * @property string $LID
 * @property int $IBLOCK_SECTION_ID ID группы. Если не задан, то элемент не привязан к группе. Если элемент привязан к нескольким группам, то в этом поле значение с минимальным ID
 * @property string $IBLOCK_CODE Символический код инфоблока
 * @property string $ACTIVE Флаг активности (Y|N)
 * @property string $DATE_ACTIVE_FROM Дата начала действия элемента
 * @property string $DATE_ACTIVE_TO Дата окончания действия элемента
 * @property int $SORT Порядок сортировки элементов между собой (в пределах одной группы-родителя)
 * @property int $PREVIEW_PICTURE Код картинки в таблице файлов для предварительного просмотра (анонса)
 * @property string $PREVIEW_TEXT Краткое описание
 * @property string $PREVIEW_TEXT_TYPE Тип предварительного описания (text/html)
 * @property int $DETAIL_PICTURE Код картинки в таблице файлов для детального просмотра
 * @property string $DETAIL_TEXT Детальное описание
 * @property string $DETAIL_TEXT_TYPE Тип детального описания (text/html)
 * @property string $SEARCHABLE_CONTENT Содержимое для поиска при фильтрации групп. Вычисляется автоматически. Складывается из полей NAME и DESCRIPTION (без html тэгов, если DESCRIPTION_TYPE установлен в html)
 * @property string $DATE_CREATE Дата создания элемента
 * @property int $CREATED_BY Код пользователя, создавшего элемент
 * @property int $MODIFIED_BY Код пользователя, в последний раз изменившего элемент
 * @property string $CREATED_USER_NAME Имя пользователя, создавшего элемент (доступен только для чтения)
 * @property string $TIMESTAMP_X Время последнего изменения полей элемента
 * @property string $USER_NAME Имя пользователя, в последний раз изменившего элемент. (доступен только для чтения)
 * @property string $LANG_DIR Путь к папке сайта. Определяется из параметров информационного блока. Изменяется автоматически. (доступен только для чтения)
 * @property string $LIST_PAGE_URL Шаблон URL-а к странице для публичного просмотра списка элементов информационного блока. Изменяется автоматически (доступен только для чтения)
 * @property string $DETAIL_PAGE_URL Шаблон URL-а к странице для детального просмотра элемента. Определяется из параметров информационного блока.
 * @property int $SHOW_COUNTER Количество показов элемента (изменяется при вызове \CIBlockElement::CounterInc)
 * @property string $SHOW_COUNTER_START Дата первого показа элемента (изменяется при вызове \CIBlockElement::CounterInc)
 * @property string $TAGS Теги элемента через запятую. Используются для построения облака тегов модулем Поиска
 * @property string $WF_COMMENTS Комментарий администратора документооборота
 * @property int $WF_STATUS_ID Код статуса элемента в документообороте
 * @property string $LOCK_STATUS Текущее состояние блокированности на редактирование элемента (red - заблокирован, green - доступен для редактирования, yellow - заблокирован текущим пользователем)
 */
class IblockElementItem extends Base
{
	/**
	 * Динамичное создание элемента инфоблока из массива
	 *
	 * @param array $arItem массив c полями элемента CIBlockElement::GetList()
	 */
	public function __construct(array $arItem)
	{
		foreach ($arItem as $field => $value) {
			if ($this->isEmptyValue($value)) {
				continue;
			}
			if ($field == 'PROPERTIES') {
				if (!isset($this->{$field})) {
					$this->{$field} = new ObjectArItem();
				}
				// если множественное свойство, то это массив массивов
				foreach ($value as $propCode => $propArOrVal) {
					$this->{$field}->{$propCode} = $this->generatePropObj($propArOrVal);
				}
			} else {
				$this->{$field} = $value;
			}
		}
	}

	/**
	 * @param $value
	 * @return IblockElementItemPropertyValue[] | IblockElementItemPropertyValue
	 */
	private function generatePropObj($value)
	{
		if ((int)$value['ID'] <= 0) {
			$result = [];
			foreach ($value as $k => $sv) {
				$result[$k] = new IblockElementItemPropertyValue($sv);
			}
		} else {
			$result = new IblockElementItemPropertyValue($value);
		}
		return $result;
	}
}
