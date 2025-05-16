<?
/**
 * Abstract Layer
 * Подсказки на выборки CIBlockElement::GetList
 *
 * @version 3.2 beta
 * @author hipot <hipot at wexpert dot ru>
 */
namespace Hipot\IbAbstractLayer\GenerateSxem;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Hipot\IbAbstractLayer\Types\IblockElementItem;

/**
 * Класс генерации схемы инфоблоков
 */
final class IblockGenerateSxem
{
	/**
	 * Шаблон генерации инфоблока со свойствами
	 * Шаблоны генерации, placeholders:
	 * #IBLOCK_ID# - ID инфоблока
	 * #IBLOCK_CODE# - Код API инфоблока
	 * #IBLOCK_ELEM_NAME# - Имя инфоблока
	 * #PROPERTYS# - Сгенерированный по шаблону список свойств
	 * #PROPERTYS_CHAINS# - Сгенерированный по шаблону список цепочек связанных элементов
	 * #ABSTRACT_LAYER_SAULT# - Соль в имени классов
	 * @var string
	 */
	private string $__iblockTemplate =
		'
/**
 * Генерируемый автоматически класс со ссылкой на свойства инфоблока #IBLOCK_ID# (IBLOCK_ID = #IBLOCK_ID#)
 * Имя сущности: <b>#IBLOCK_ELEM_NAME#</b>
 * @author info@hipot-studio.com
 * @version 0.x
 */
class __IblockElementItem_#ABSTRACT_LAYER_SAULT#_#IBLOCK_ID# extends IblockElementItem
{
	/**
	 * Свойства инфоблока
	 * @var __IblockElementItem_#ABSTRACT_LAYER_SAULT#_#IBLOCK_ID#_Properties
	 */
	public $PROPERTIES;	
	
#PROPERTIES_BY_GETLIST_SELECT#
	
	/**
	 * Динамичное создание итема из массива
	 * Имя сущности: <b>#IBLOCK_ELEM_NAME#</b>
	 * @param array $arItem массив c полями элемента GetNext от CIBlockElement::GetList()
	 */
	public function __construct($arItem)
	{		
		parent::__construct($arItem);
	}
}
class __IblockElementItem_#ABSTRACT_LAYER_SAULT#_#IBLOCK_CODE#_#IBLOCK_ID# extends __IblockElementItem_#ABSTRACT_LAYER_SAULT#_#IBLOCK_ID#
{
}

/**
 * Генерируемый автоматически класс со свойствами инфоблока #IBLOCK_ID# (IBLOCK_ID = #IBLOCK_ID#)
 * Свойства инфоблока: #IBLOCK_ELEM_NAME#
 * @author info@hipot-studio.com
 * @version 0.x
 */
class __IblockElementItem_#ABSTRACT_LAYER_SAULT#_#IBLOCK_ID#_Properties extends IbAbstractLayerBase
{
#PROPERTYS#
}
#PROPERTYS_CHAINS#

';

	/**
	 * Шаблоны генерации инфоблока без свойств, placeholders:
	 * #IBLOCK_ID# - ID инфоблока
	 * #IBLOCK_CODE# - Код API инфоблока
	 * #ABSTRACT_LAYER_SAULT# - Соль в имени классов
	 * @var string
	 */
	private string $__iblockTemplateNoProps =
		'
/**
 * Генерируемый автоматически класс со ссылкой на свойства инфоблока #IBLOCK_ID# (IBLOCK_ID = #IBLOCK_ID#)
 * Имя сущности: <b>#IBLOCK_ELEM_NAME#</b>
 * @author info@hipot-studio.com
 * @version 0.x
 */
class __IblockElementItem_#ABSTRACT_LAYER_SAULT#_#IBLOCK_ID# extends IblockElementItem
{
	/**
	 * Динамичное создание итема из массива
	 * Имя сущности: <b>#IBLOCK_ELEM_NAME#</b>
	 * @param array $arItem массив c полями элемента GetNext от CIBlockElement::GetList()
	 */
	public function __construct($arItem)
	{
		parent::__construct($arItem);
	}
}
class __IblockElementItem_#ABSTRACT_LAYER_SAULT#_#IBLOCK_CODE#_#IBLOCK_ID# extends __IblockElementItem_#ABSTRACT_LAYER_SAULT#_#IBLOCK_ID#
{
}


';

	/**
	 * Шаблон не множественного свойства, placeholders:
	 * #PROPERTY_TITLE# - описание свойства
	 * #PROPERTY_CODE# - имя свойства
	 * #PROPERTY_TYPE# - тип свойства в системе схемы
	 * @var string
	 */
	private string $oneRowPropertytemplate =
		'	/**
	 * #PROPERTY_TITLE# (ID: <b>#PROPERTY_ID#</b>) 
	 * #PROPERTY_FULL_DESCRIPTION#
	 * @var #PROPERTY_TYPE#
	 */
	public $#PROPERTY_CODE#;
	
';

	/**
	 * Шаблон множественного свойства, placeholders:
	 * #PROPERTY_TITLE# - описание свойства
	 * #PROPERTY_CODE# - имя свойства
	 * #PROPERTY_TYPE# - тип свойства в системе схемы
	 * @var string
	 */
	private string $multipleRowPropertyTemplate =
		'	/**
	 * #PROPERTY_TITLE# (ID: <b>#PROPERTY_ID#</b>)
	 * @var array[#PROPERTY_TYPE#]
	 * #PROPERTY_FULL_DESCRIPTION#
	 * @var #PROPERTY_TYPE#[]
	 */
	public $#PROPERTY_CODE#;
	
';

	/**
	 * Шаблон свойств, привязанных к элементам свойства, placeholders:
	 * #PROPERTY_CODE# - имя свойства
	 * #IBLOCK_ID# - ID инфоблока
	 * #LINK_IBLOCK_ID# - ID связанного инфоблока
	 * #LINK_IBLOCK_ELEM_NAME# - Имя сущности связанного инфоблока
	 * #ABSTRACT_LAYER_SAULT# - Соль в имени классов
	 * @var string
	 */
	private string $chainPropChainClassTemplate =
		'
/**
 * Класс цепочек связанных элементов со свойством "#PROPERTY_CODE#" инфоблока #IBLOCK_ID#
 */
class __IblockElementItemPropertyValueLinkElem_#ABSTRACT_LAYER_SAULT#_#IBLOCK_ID#_#PROPERTY_CODE# extends IblockElementItemPropertyValueLinkElem
{
	/**
	 * Цепочка из связанных элементов, выводятся все поля связанного элемента, а также его свойства
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var #TYPE#
	 */
	public $CHAIN;
}
';

	/**
	 * Шаблон свойства, выбранного через CIBLockElement::GetList()
	 * #PROPERTY_CODE# - код свойства
	 * #PROPERTY_TITLE# - описание свойства
	 * #BY_ELEM_PROPS_SELECT# - поля и свойства элементов, выбранные через свойства
	 * @var string
	 */
	private string $propByGetListSelectTemplate =
		'	/**
	 * #PROPERTY_TITLE# - значение
	 * @var string|int
	 */
	public $PROPERTY_#PROPERTY_CODE#_VALUE;
	
	/**
	 * #PROPERTY_TITLE# - код значения у элемента
	 * @var int
	 */
	public $PROPERTY_#PROPERTY_CODE#_VALUE_ID;
	
	#BY_ELEM_PROPS_SELECT#
	
';

	/**
	 * Шаблон свойства типа список, выбранного через CIBLockElement::GetList()
	 *
	 * #PROPERTY_CODE# - код свойства
	 * #PROPERTY_TITLE# - описание свойства
	 * @var string
	 */
	private string $propByGetListSelectTypeListTemplate =
		'	/**
	 * #PROPERTY_TITLE# - значение
	 * @var string
	 */
	public $PROPERTY_#PROPERTY_CODE#_VALUE;
	
	/**
	 * #PROPERTY_TITLE# - код значения у элемента
	 * @var int
	 */
	public $PROPERTY_#PROPERTY_CODE#_VALUE_ID;
	
	/**
	 * #PROPERTY_TITLE# - ID значения типа список
	 * @var int
	 */
	public $PROPERTY_#PROPERTY_CODE#_ENUM_ID;
	
';

	/**
	 * Шаблон полей элементов, выбранный через свойства
	 * #PROPERTY_TITLE# - имя свойства
	 * #PROPERTY_CODE# - код свойства
	 * #LINK_IBLOCK_ELEM_NAME# - имя связанного инфоблока
	 * #BY_ELEM_PROPS_BY_PROPS# - свойства элементов, связанных с элементом
	 * @var string
	 */
	private string $propByElemFieldsProps =
		'
	/**
	 * #PROPERTY_TITLE# - ID связанного элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var int
	 */
	public $PROPERTY_#PROPERTY_CODE#_ID;
	
	/**
	 * #PROPERTY_TITLE# - Время последнего изменения полей элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var datetime
	 */
	public $PROPERTY_#PROPERTY_CODE#_TIMESTAMP_X;
	
	/**
	 * #PROPERTY_TITLE# - Код пользователя, в последний раз изменившего связанный элемент
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var int
	 */
	public $PROPERTY_#PROPERTY_CODE#_MODIFIED_BY;
	
	/**
	 * #PROPERTY_TITLE# - Дата создания связанного элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var datetime
	 */
	public $PROPERTY_#PROPERTY_CODE#_CREATED_DATE;
    
	/**
	 * #PROPERTY_TITLE# - Код пользователя, создавшего связанный элемент
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var int
	 */
	public $PROPERTY_#PROPERTY_CODE#_CREATED_BY;
    
	/**
	 * #PROPERTY_TITLE# - ID информационного блока у связанного элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var int
	 */
	public $PROPERTY_#PROPERTY_CODE#_IBLOCK_ID;
    
	/**
	 * #PROPERTY_TITLE# - Флаг активности (Y|N) у связанного элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var string
	 */
	public $PROPERTY_#PROPERTY_CODE#_ACTIVE;
    
	/**
	 * #PROPERTY_TITLE# - Дата начала действия у связанного элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var datetime
	 */
	public $PROPERTY_#PROPERTY_CODE#_ACTIVE_FROM;
    
	/**
	 * #PROPERTY_TITLE# - Дата окончания действия у связанного элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var datetime
	 */
	public $PROPERTY_#PROPERTY_CODE#_ACTIVE_TO;
    
	/**
	 * #PROPERTY_TITLE# - индекс сортировки у связанного элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var int
	 */
	public $PROPERTY_#PROPERTY_CODE#_SORT;
    
	/**
	 * #PROPERTY_TITLE# - Название связанного элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var string
	 */
	public $PROPERTY_#PROPERTY_CODE#_NAME;
    
	/**
	 * #PROPERTY_TITLE# - Количество показов связанного элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var int
	 */
	public $PROPERTY_#PROPERTY_CODE#_SHOW_COUNTER;
    
	/**
	 * #PROPERTY_TITLE# - Дата первого показа связанного элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var Datetime
	 */
	public $PROPERTY_#PROPERTY_CODE#_SHOW_COUNTER_START;
    
	/**
	 * #PROPERTY_TITLE# - Мнемонический идентификатор связанного элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var string
	 */
	public $PROPERTY_#PROPERTY_CODE#_CODE;
    
	/**
	 * #PROPERTY_TITLE# - Теги связанного элемента.
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var string
	 */
	public $PROPERTY_#PROPERTY_CODE#_TAGS;
    
	/**
	 * #PROPERTY_TITLE# - EXTERNAL_ID или XML_ID Внешний идентификатор связанного элемента
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var string
	 */
	public $PROPERTY_#PROPERTY_CODE#_XML_ID;
    
	/**
	 * #PROPERTY_TITLE# - Текущее состояние блокированности на редактирование связанного элемента.
	 * Имя сущности: <b>#LINK_IBLOCK_ELEM_NAME#</b>
	 * @var string
	 */
	public $PROPERTY_#PROPERTY_CODE#_STATUS;
	
#BY_ELEM_PROPS_BY_PROPS#
    
';

	/**
	 * Шаблон свойств элементов, выбранных через свойства
	 * #PROPERTY_TITLE# - имя свойства
	 * #PROPERTY_CODE# - тип свойства
	 * #PROPERTY_LINK_CODE# - код завязанного свойства
	 * @var string
	 */
	private string $propByElemFieldsPropsTemplate =
		'
	/**
	 * #PROPERTY_TITLE# - значение
	 * @var string|int
	 */
	public $PROPERTY_#PROPERTY_LINK_CODE#_PROPERTY_#PROPERTY_CODE#_VALUE;
	
	/**
	 * #PROPERTY_TITLE# - код значения у элемента
	 * @var int
	 */
	public $PROPERTY_#PROPERTY_LINK_CODE#_PROPERTY_#PROPERTY_CODE#_VALUE_ID;
';


	/**
	 * Шаблон свойств элементов, выбранных через свойства, тип выбранного свойства типа список
	 * #PROPERTY_TITLE# - имя свойства
	 * #PROPERTY_CODE# - тип свойства
	 * #PROPERTY_LINK_CODE# - код завязанного свойства
	 * @var string
	 */
	private string $propByElemFieldsPropsListTemplate =
		'
	/**
	 * #PROPERTY_TITLE# - значение
	 * @var string
	 */
	public $PROPERTY_#PROPERTY_LINK_CODE#_PROPERTY_#PROPERTY_CODE#_VALUE;
	
	/**
	 * #PROPERTY_TITLE# - код значения у элемента
	 * @var int
	 */
	public $PROPERTY_#PROPERTY_LINK_CODE#_PROPERTY_#PROPERTY_CODE#_VALUE_ID;
	
	/**
	 * #PROPERTY_TITLE# - ID значения типа список
	 * @var int
	 */
	public $PROPERTY_#PROPERTY_LINK_CODE#_PROPERTY_#PROPERTY_CODE#_ENUM_ID;
	
';

	private string $ufFieldsList =
		'
/**
 * Пользовательские поля
 */
class __UfFieldsList_#ABSTRACT_LAYER_SAULT#
{
	#UF_LIST_ITEMS#
}
';
	private string $ufFieldsListItem =
		'
	/**
	 * #ENTITY_ID#_#FIELD_NAME# - #NAME#
	 * @var string
	 */
	public const string #ENTITY_ID#___#FIELD_NAME# = "#NAME#";
';

	public function __construct(
		/**
		 * Путь к файлу, в котором будут сгенерированы классы по инфоблокам
		 * @var string
		 */
		private readonly string $fileGenerate
	)
	{
	}

	/**
	 * Получить список инфоблоков со свойствами
	 */
	private function getIblockList(): array
	{
		$arPROPERTIES = $this->getPropertiesByIblock();

		$arReturn = [];
		$rs = \CIBlock::GetList(['ID' => 'ASC'], [], false);
		while ($ar = $rs->Fetch()) {
			$ar['PROPERTIES'] = [];
			foreach ($arPROPERTIES[ $ar['ID'] ] as $prop) {
				$ar['PROPERTIES'][] = $prop;
			}
			$arReturn[] = $ar;
		}
		return $arReturn;
	}

	/**
	 * Получить список свойств инфоблока
	 * @return array
	 */
	private function getPropertiesByIblock(): array
	{
		// fix
		global $USER;
		if (!is_object($USER)) {
			$USER = new \CUser();
		}

		$props = new \CIBlockProperty();
		$rs = $props::GetList(['IBLOCK_ID' => 'ASC', 'SORT' => 'ASC'], ['CHECK_PERMISSIONS' => 'N']);

		$arReturn = [];
		while ($prop = $rs->Fetch()) {
			if ($prop['PROPERTY_TYPE'] == PropertyTable::TYPE_LIST) {
				$prop['VALUE_LIST'] = [];
				$iterator = \Bitrix\Iblock\PropertyEnumerationTable::getList([
					'filter' => ['=PROPERTY_ID' => $prop['ID']],
					'select' => ['ID', 'XML_ID', 'VALUE'],
					'order'  => ['SORT' => 'ASC', 'ID' => 'ASC'],
				]);
				while ($row = $iterator->fetch()) {
					$prop['VALUE_LIST'][] = $row;
				}
			}
			$arReturn[ $prop['IBLOCK_ID'] ][] = $prop;
		}
		return $arReturn;
	}

	/**
	 * Генерировать файл
	 * @return bool
	 */
	public function generate(): bool
	{
		if (! Loader::includeModule('iblock')) {
			return false;
		}
		$arIblocks = $this->getIblockList();

		$arIblocksIdsIndex = [];
		foreach ($arIblocks as $k => $arIblock) {
			$arIblocksIdsIndex[ $arIblock['ID'] ] = $k;
		}

		// общий вывод
		$out = 'use Hipot\IbAbstractLayer\Types\Base as IbAbstractLayerBase,
	Hipot\IbAbstractLayer\Types\IblockElementItem,
	Hipot\IbAbstractLayer\Types\IblockElementItemPropertyValue,
	Hipot\IbAbstractLayer\Types\IblockElementItemPropertyValueFile,
	Hipot\IbAbstractLayer\Types\IblockElementItemPropertyValueLinkElem;
';

		foreach ($arIblocks as $arIblock) {
			// накопление всех свойств
			$outPropsIter = '';
			// накопление всех связанных свойств
			$outPropsChains = '';
			// накопление свойств, получаемых прямо в GetList по элементам
			$propByGetListSelect = '';

			foreach ($arIblock['PROPERTIES'] as $prop) {
				$propType = 'IblockElementItemPropertyValue';

				// поля элементов, выбранных через свойства
				$bySelectLinkedProps = '';

				if ($prop['PROPERTY_TYPE'] == PropertyTable::TYPE_ELEMENT) {
					$propType = '__IblockElementItemPropertyValueLinkElem_' . ABSTRACT_LAYER_SAULT . '_' . $arIblock['ID'] . '_' . $prop['CODE'];

					$k = $arIblocksIdsIndex[ $prop['LINK_IBLOCK_ID'] ];
					if ($prop['LINK_IBLOCK_ID']) {
						$linkIblockName = $arIblocks[$k]['NAME'] . ' / ' . $arIblocks[$k]['ELEMENT_NAME'];
						$chainType = str_replace(
							["#LINK_IBLOCK_ID#", '#ABSTRACT_LAYER_SAULT#'],
							[$prop['LINK_IBLOCK_ID'], ABSTRACT_LAYER_SAULT], '__IblockElementItem_#ABSTRACT_LAYER_SAULT#_#LINK_IBLOCK_ID#');

						// список свойств у привязанных свойств вида PROPERTY_code_PROPERTY_code2_VALUE
						$byElemsPropByProp = '';
						foreach ($arIblocks[$k]['PROPERTIES'] as $propIter) {
							$byElemsPropByProp .= str_replace(
								['#PROPERTY_LINK_CODE#', '#PROPERTY_TITLE#', '#PROPERTY_CODE#'],
								[strtoupper($prop['CODE']), rtrim($propIter['NAME'] . ' ' . $propIter['HINT']), strtoupper($propIter['CODE'])],
								($propIter['PROPERTY_TYPE'] == PropertyTable::TYPE_LIST) ? $this->propByElemFieldsPropsListTemplate : $this->propByElemFieldsPropsTemplate
							);
						}
						$bySelectLinkedProps .= str_replace(
							['#PROPERTY_TITLE#', '#PROPERTY_CODE#', '#LINK_IBLOCK_ELEM_NAME#', '#BY_ELEM_PROPS_BY_PROPS#'],
							[rtrim($prop['NAME'] . ' ' . $prop['HINT']), strtoupper($prop['CODE']), $linkIblockName, $byElemsPropByProp],
							$this->propByElemFieldsProps
						);
					} else {
						$linkIblockName = 'Связь не указана';
						$chainType = 'IblockElementItem';
					}

					$outPropsChains .= str_replace(
						["#PROPERTY_CODE#", "#IBLOCK_ID#", "#LINK_IBLOCK_ID#", '#LINK_IBLOCK_ELEM_NAME#', '#ABSTRACT_LAYER_SAULT#', '#TYPE#'],
						[$prop['CODE'], $arIblock['ID'], $prop['LINK_IBLOCK_ID'], $linkIblockName, ABSTRACT_LAYER_SAULT, $chainType],
						$this->chainPropChainClassTemplate
					);
				} else if ($prop['PROPERTY_TYPE'] == PropertyTable::TYPE_FILE) {
					$propType = 'IblockElementItemPropertyValueFile';
				}

				$propByGetListSelect .= str_replace(
					['#PROPERTY_TITLE#', '#PROPERTY_CODE#', '#BY_ELEM_PROPS_SELECT#'],
					[rtrim($prop['NAME'] . ' ' . $prop['HINT']), strtoupper($prop['CODE']), $bySelectLinkedProps],
					($prop['PROPERTY_TYPE'] == PropertyTable::TYPE_LIST) ? $this->propByGetListSelectTypeListTemplate : $this->propByGetListSelectTemplate
				);

				$propFullDescription = '';
				if ($prop['PROPERTY_TYPE'] == PropertyTable::TYPE_LIST) {
					$propFullDescription = '<table>';
					$propFullDescription .= '<tr><th>ID</th><th>XML_ID</th><th>VALUE</th></tr>';
					foreach ($prop['VALUE_LIST'] as $value) {
						$propFullDescription .= '<tr>';
						foreach (['ID', 'XML_ID', 'VALUE'] as $key) {
							$propFullDescription .= '<td>' . $value[$key] . '</td>';
						}
						$propFullDescription .= '</tr>';
					}
					$propFullDescription .= '</table>';
				}
				// TODO fill hl-list too
				if ($prop['USER_TYPE'] == PropertyTable::USER_TYPE_DIRECTORY) {
				}

				$temp = ($prop['MULTIPLE'] != 'Y') ? $this->oneRowPropertytemplate : $this->multipleRowPropertyTemplate;
				$outPropsIter .= str_replace(
					[
						'#PROPERTY_TITLE#',
						'#PROPERTY_CODE#',
						'#PROPERTY_ID#',
						'#PROPERTY_TYPE#',
						'#PROPERTY_FULL_DESCRIPTION#'
					],
					[
						rtrim($prop['NAME'] . ' ' . $prop['HINT']),
						$prop['CODE'],
						$prop['ID'],
						$propType,
						$propFullDescription
					],
					$temp
				);
				// to find property by its ID_
				$outPropsIter .= str_replace(
					[
						'#PROPERTY_TITLE#',
						'#PROPERTY_CODE#',
						'#PROPERTY_ID#',
						'#PROPERTY_TYPE#',
						'#PROPERTY_FULL_DESCRIPTION#'
					],
					[
						rtrim($prop['NAME'] . ' ' . $prop['HINT']),
						'ID_' . $prop['ID'],
						$prop['CODE'],
						$propType,
						$propFullDescription
					],
					$temp
				);
			}

			$iblockCode = $arIblock['ID'] . '_no_code_';
			foreach (['API_CODE', 'CODE'] as $codeKey) {
				if (!empty($arIblock[$codeKey])) {
					$iblockCode = $arIblock[$codeKey];
					break;
				}
			}

			$out .= str_replace([
				'#IBLOCK_ID#',
				'#IBLOCK_CODE#',
				'#PROPERTYS#',
				'#IBLOCK_ELEM_NAME#',
				'#PROPERTYS_CHAINS#',
				'#PROPERTIES_BY_GETLIST_SELECT#',
				'#ABSTRACT_LAYER_SAULT#'
			], [
				$arIblock['ID'],
				$iblockCode,
				$outPropsIter,
				$arIblock['NAME'] . ' / ' . $arIblock['ELEMENT_NAME'],
				$outPropsChains,
				$propByGetListSelect,
				ABSTRACT_LAYER_SAULT
			],
				(count($arIblock['PROPERTIES']) > 0) ? $this->__iblockTemplate : $this->__iblockTemplateNoProps
			);
		}

		$ufRs = \CUserTypeEntity::GetList(['ENTITY_ID' => 'ASC', 'SORT' => 'ASC', 'FIELD_NAME' => 'ASC'], ['LANG' => LANGUAGE_ID]);
		$outUfs = '';
		while ($ufField = $ufRs->Fetch()) {
			$outUfs .= str_replace([
				'#ENTITY_ID#',
				'#FIELD_NAME#',
				'#NAME#',
				'#ABSTRACT_LAYER_SAULT#'
			], [
				$ufField['ENTITY_ID'],
				$ufField['FIELD_NAME'],
				$ufField['EDIT_FORM_LABEL'] ?? $ufField['FIELD_NAME'],
				ABSTRACT_LAYER_SAULT
			], $this->ufFieldsListItem);
		}
		$out .= str_replace([
			'#UF_LIST_ITEMS#',
			'#ABSTRACT_LAYER_SAULT#'
		], [
			$outUfs,
			ABSTRACT_LAYER_SAULT
		], $this->ufFieldsList);
		unset($outUfs);

		return file_put_contents($this->fileGenerate,
			'<?php /** @noinspection PhpMissingParamTypeInspection */ 
/** @noinspection PhpUnused */ 
/** @noinspection PhpMissingFieldTypeInspection */' . PHP_EOL . $out, LOCK_EX
		);
	}
}

