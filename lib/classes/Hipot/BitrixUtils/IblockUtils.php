<?
namespace Hipot\BitrixUtils;

use Bitrix\Main\Loader,
	Hipot\Utils\UpdateResult,
	Hipot\Utils\UnsortedUtils;

Loader::includeModule('iblock');

use Bitrix\Iblock\InheritedProperty\ElementTemplates,
	Bitrix\Iblock\InheritedProperty\ElementValues,
	Bitrix\Iblock\InheritedProperty\SectionTemplates,
	Bitrix\Iblock\InheritedProperty\SectionValues,
	Bitrix\Iblock\PropertyIndex\Manager;

/**
 * Дополнительные утилиты для работы с инфоблоками
 *
 * IMPORTANT:
 * Некоторые методы выборки избыточны (лучше использовать bitrix api).
 * В основном необходимы для построения быстрых решений: к примеру, отчетов.
 *
 * @version 2.X
 */
class IblockUtils
{
	/**
	 * Добавление секции в инфоблок, возвращает ошибку либо ID результата, см. return
	 *
	 * @param array $arAddFields массив к добавлению
	 * @param bool $bResort = true перестроить ли дерево, можно отдельно вызывать CIBlockSection::Resort(int IBLOCK_ID);
	 * @param bool $bUpdateSearch = false обновить ли поиск
	 *
	 * @return \Hipot\Utils\UpdateResult
	 * @see \CIBlockSection::Add()
	 */
	public static function addSectionToDb($arAddFields = [], $bResort = true, $bUpdateSearch = false): UpdateResult
	{
		if (! is_array($arAddFields)) {
			$arAddFields = array();
		}

		$el = new \CIBlockSection();
		$ID = $el->Add($arAddFields, $bResort, $bUpdateSearch);

		if ($ID) {
			return new UpdateResult(['RESULT' => $ID,				'STATUS' => UpdateResult::STATUS_OK]);
		} else {
			return new UpdateResult(['RESULT' => $el->LAST_ERROR,	'STATUS' => UpdateResult::STATUS_ERROR]);
		}
	}

	/**
	 * Обновление секции в инфоблоке, возвращает ошибку либо ID результата, см. return
	 *
	 * @param int $ID код секции
	 * @param array $arAddFields массив к добавлению
	 * @param bool $bResort = true перестроить ли дерево, можно отдельно вызывать CIBlockSection::Resort(int IBLOCK_ID);
	 * @param bool $bUpdateSearch = false обновить ли поиск
	 *
	 * @return bool | \Hipot\Utils\UpdateResult
	 * @see \CIBlockSection::Add()
	 */
	public static function updateSectionToDb($ID, $arAddFields = [], $bResort = true, $bUpdateSearch = false)
	{
		if (! is_array($arAddFields)) {
			$arAddFields = [];
		}

		$ID = (int)$ID;
		if ($ID <= 0) {
			return false;
		}

		$el		= new \CIBlockSection();
		$res	= $el->Update($ID, $arAddFields, $bResort, $bUpdateSearch);
		if ($res) {
			return new UpdateResult(['RESULT' => $ID,				'STATUS' => UpdateResult::STATUS_OK]);
		} else {
			return new UpdateResult(['RESULT' => $el->LAST_ERROR,	'STATUS' => UpdateResult::STATUS_ERROR]);
		}
	}

	/**
	 * Добавление элемента в инфоблок, возвращает ошибку либо ID результата, см. return
	 *
	 * @param array $arAddFields массив к добавлению
	 * @param bool $bUpdateSearch = false обновить ли поиск
	 *
	 * @return \Hipot\Utils\UpdateResult
	 * @see \CIBlockElement::Add()
	 */
	public static function addElementToDb($arAddFields = [], $bUpdateSearch = false): UpdateResult
	{
		if (! is_array($arAddFields)) {
			$arAddFields = [];
		}

		$el = new \CIBlockElement();
		$ID = $el->Add($arAddFields, false, $bUpdateSearch);

		if ($ID) {
			return new UpdateResult(['RESULT' => $ID,				'STATUS' => UpdateResult::STATUS_OK]);
		} else {
			return new UpdateResult(['RESULT' => $el->LAST_ERROR,	'STATUS' => UpdateResult::STATUS_ERROR]);
		}
	}

	/**
	 * Обновление элемента в инфоблоке, возвращает ошибку либо ID результата, см. return
	 *
	 * @param int $ID массив к добавлению
	 * @param array $arAddFields массив к добавлению
	 * @param bool $bUpdateSearch = false обновить ли поиск
	 *
	 * @return bool | \Hipot\Utils\UpdateResult
	 * @see \CIBlockElement::Update()
	 */
	public static function updateElementToDb($ID, $arAddFields, $bUpdateSearch = false)
	{
		if (! is_array($arAddFields)) {
			$arAddFields = [];
		}
		$ID = (int)$ID;
		if ($ID <= 0) {
			return false;
		}

		if (isset($arAddFields["PROPERTY_VALUES"])) {
			$PROPS = $arAddFields["PROPERTY_VALUES"];
			unset($arAddFields["PROPERTY_VALUES"]);
		}

		$el 	= new \CIBlockElement();
		$bUpd 	= $el->Update($ID, $arAddFields, false, $bUpdateSearch);

		if (isset($PROPS) && $bUpd) {
			$iblockId = self::getElementIblockId($ID);
			\CIBlockElement::SetPropertyValuesEx($ID, $iblockId, $PROPS);
			Manager::updateElementIndex($iblockId, $ID);
		}

		if ($bUpd) {
			return new UpdateResult(['RESULT' => $ID,				'STATUS' => UpdateResult::STATUS_OK]);
		} else {
			return new UpdateResult(['RESULT' => $el->LAST_ERROR,	'STATUS' => UpdateResult::STATUS_ERROR]);
		}
	}

	/**
	 * Получить результат выборки из инфоблока по параметрам
	 *
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param bool $arGroupBy
	 * @param bool $arNavParams
	 * @param array $arSelect
	 * @return \CIBlockResult | int
	 */
	public static function selectElementsByFilter($arOrder, $arFilter, $arGroupBy = false, $arNavParams = false, $arSelect = [])
	{
		$rsItems = \CIBlockElement::GetList($arOrder, $arFilter, $arGroupBy, $arNavParams, $arSelect);
		return $rsItems;
	}

	/**
	 * Получить массив элементов инфоблока по параметрам (со свойствами)
	 *
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param bool $arGroupBy
	 * @param bool|array $arNavParams
	 * @param array $arSelect
	 * @param bool $SelectAllProps = false
	 * @param bool $OnlyPropsValue = true
	 * @param bool $bSelectChains = false
	 * @param int $selectChainsDepth = 3
	 * @return array | int
	 */
	public static function selectElementsByFilterArray($arOrder, $arFilter, $arGroupBy = false, $arNavParams = false,
	                                                   $arSelect = [], $SelectAllProps = false, $OnlyPropsValue = true,
	                                                   $bSelectChains = false, $selectChainsDepth = 3)
	{
		/** @noinspection TypeUnsafeArraySearchInspection */
		if (! in_array('IBLOCK_ID', $arSelect)) {
			$arSelect[] = 'IBLOCK_ID';
		}
		/** @noinspection TypeUnsafeArraySearchInspection */
		if (! in_array('ID', $arSelect)) {
			$arSelect[] = 'ID';
		}

		$obChainBuilder = null;
		if ($bSelectChains) {
			$obChainBuilder = new \Hipot\IbAbstractLayer\IblockElemLinkedChains();
			$OnlyPropsValue = false;
		}

		$arResult = [];
		$rsItems = self::selectElementsByFilter($arOrder, $arFilter, $arGroupBy, $arNavParams, $arSelect);
		if (! is_object($rsItems)) {
			return $rsItems;
		}
		while ($arItem = $rsItems->GetNext()) {
			if ($SelectAllProps === true) {
				$arItem['PROPERTIES'] = self::selectElementProperties(
					$arItem['ID'],
					$arItem['IBLOCK_ID'],
					$OnlyPropsValue,
					$obChainBuilder,
					$selectChainsDepth
				);
			}
			$arResult[] = $arItem;
		}
		if (count($arResult) == 1) {
			$arResult = current($arResult);
		}

		unset($obChainBuilder);

		return $arResult;
	}

	/**
	 * Получить свойства элемента инфоблока
	 *
	 * @param int  $ID элемент инфоблока
	 * @param int  $IBLOCK_ID = 0 код инфоблока, если не указан, то будет выбран (желательно указывать для быстродействия!)
	 * @param bool $onlyValue = false возвращать только значение свойства
	 * @param array $exFilter = [] дополнительный фильтр при выборке свойств через CIBlockElement::GetProperty()
	 * @param null | \Hipot\IbAbstractLayer\IblockElemLinkedChains $obChainBuilder = null
	 * @param int $selectChainsDepth = 3 глубина вложенности выборки вложенных свойств
	 *
	 * @return array | bool
	 */
	public static function selectElementProperties($ID, $IBLOCK_ID = 0, $onlyValue = false, $exFilter = [],
	                                               $obChainBuilder = null, $selectChainsDepth = 3)
	{
		$IBLOCK_ID	= (int)$IBLOCK_ID;
		$ID			= (int)$ID;
		if ($ID <= 0) {
			return false;
		}

		if ($IBLOCK_ID <= 0) {
			$IBLOCK_ID = self::getElementIblockId($ID);
			if (! $IBLOCK_ID) {
				return false;
			}
		}

		$PROPERTIES = [];
		// QUERY 2
		$arFilter = ["EMPTY" => "N"];
		foreach ($exFilter as $f => $v) {
			$arFilter[$f] = $v;
		}
		$db_props = \CIBlockElement::GetProperty($IBLOCK_ID, $ID, ["sort" => "asc"], $arFilter);
		while ($ar_props = $db_props->Fetch()) {
			// довыборка цепочек глубиной 3
			if (is_object($obChainBuilder) && $ar_props['PROPERTY_TYPE'] == 'E') {
				// инициализация должна происходить перед каждым вызовом getChains_r
				// с указанием выбираемой вложенности
				$obChainBuilder->init( (int)$selectChainsDepth );
				$ar_props['CHAIN'] = $obChainBuilder->getChains_r($ar_props['VALUE']);
			}

			if (trim($ar_props['CODE']) == '') {
				$ar_props['CODE'] = $ar_props['ID'];
			}
			if ($ar_props['PROPERTY_TYPE'] == "S" && isset($ar_props['VALUE']['TEXT'], $ar_props['VALUE']['TYPE'])) {
				$ar_props['VALUE']['TEXT'] = \FormatText($ar_props['VALUE']['TEXT'], $ar_props['VALUE']['TYPE']);
			}
			if ($ar_props['PROPERTY_TYPE'] == 'F') {
				$ar_props['FILE_PARAMS'] = \CFile::GetFileArray($ar_props['VALUE']);
			}

			if ($ar_props['MULTIPLE'] == "Y") {
				$PROPERTIES[ $ar_props['CODE'] ][]	= ($onlyValue) ? $ar_props['VALUE'] : $ar_props;
			} else {
				$PROPERTIES[ $ar_props['CODE'] ]	= ($onlyValue) ? $ar_props['VALUE'] : $ar_props;
			}
		}
		return $PROPERTIES;
	}

	/**
	 * Получить инфоблок элемента
	 * @param int $ID код элемента
	 * @return bool|integer
	 */
	public static function getElementIblockId($ID)
	{
		global $DB;

		if (($ID = (int)$ID) <= 0) {
			return false;
		}

		/** @noinspection SqlNoDataSourceInspection */
		/** @noinspection SqlResolve */
		$rs = $DB->Query("select IBLOCK_ID from b_iblock_element where ID=" . $ID);
		if ($ar = $rs->Fetch()) {
			return $ar["IBLOCK_ID"];
		} else {
			return false;
		}
	}

	/**
	 * Получить список секций по фильтру.
	 * FUTURE
	 *
	 * @see CIBlockSection::GetList()
	 *
	 * @param array                              $arOrder
	 * @param array                              $arFilter
	 * @param bool|string                        $bIncCnt = false
	 * @param array                              $arSelect = []
	 * @param bool|string                        $arNavStartParams = false
	 *
	 * @return \CIBlockResult | int
	 */
	public static function selectSectionsByFilter($arOrder, $arFilter, $bIncCnt = false, $arSelect = [], $arNavStartParams = false)
	{
		/** @noinspection TypeUnsafeArraySearchInspection */
		if (! in_array('ID', $arSelect)) {
			$arSelect[] = 'ID';
		}
		/** @noinspection TypeUnsafeArraySearchInspection */
		if (! in_array('IBLOCK_ID', $arSelect)) {
			$arSelect[] = 'IBLOCK_ID';
		}
		return \CIBlockSection::GetList($arOrder, $arFilter, $bIncCnt, $arSelect, $arNavStartParams);
	}

	/**
	 * Получить список секций по фильтру в виде массива
	 *
	 * @param array       $arOrder
	 * @param array       $arFilter
	 * @param bool|string $bIncCnt
	 * @param array       $arSelect
	 * @param bool|string $arNavStartParams
	 *
	 * @return array|boolean
	 */
	public static function selectSectionsByFilterArray($arOrder, $arFilter, $bIncCnt = false,
	                                                   $arSelect = [], $arNavStartParams = false): array
	{
		$arResult = [];
		$rsSect = self::selectSectionsByFilter($arOrder, $arFilter, $bIncCnt, $arSelect, $arNavStartParams);
		while ($arSect = $rsSect->GetNext()) {
			$arResult[] = $arSect;
		}
		return $arResult;
	}


	/**
	 * Получить варианты значения выпадающего списка
	 *
	 * @param string|int $propCode
	 * @param array $arFilterEx = array() фильтр по выборке вариантов
	 * @param array $aSort = array("DEF"=>"DESC", "SORT"=>"ASC")
	 *
	 * @return array | bool
	 */
	public static function selectPropertyEnumArray($propCode, $arFilterEx = [], $aSort = ["DEF" => "DESC", "SORT" => "ASC"])
	{
		if (trim($propCode) == '') {
			return false;
		}

		$arFilter = [];
		if (is_numeric($propCode)) {
			$arFilter['ID']		= (int)$propCode;
		} else {
			$arFilter['CODE']	= $propCode;
		}
		foreach ($arFilterEx as $f => $filter) {
			$arFilter[ $f ] = $filter;
		}

		$arRes = [];
		$property_enums = \CIBlockPropertyEnum::GetList($aSort, $arFilter);
		while ($enum_fields = $property_enums->GetNext()) {
			$arRes[] = $enum_fields;
		}
		return $arRes;
	}

	/**
	 * Проверить наличие элемента/секции с именем или симв. кодом $field в инфоблоке $iblockId.
	 *
	 * @param string $field
	 * @param int $iblockId
	 * @param string $fieldType
	 * @param string $table = b_iblock_element | b_iblock_section
	 * @return boolean | int возвращает ID найденного элемента
	 */
	public static function checkExistsByNameOrCode($field, $iblockId, $fieldType = 'name', $table = 'b_iblock_element')
	{
		global $DB;

		$fieldType = ToLower($fieldType);

		$iblockFieldsBase = ['code', 'xml_id', 'name', 'preview_text'];
		$fields = UnsortedUtils::getTableFieldsFromDB($table);
		foreach ($fields as &$f) {
			$f = ToLower($f);
			$iblockFields[] = $f;
		}
		unset($f);

		if ((int)$iblockId == 0 || trim($field) == ''
			|| !in_array(trim($fieldType, '?=%!<> '), $iblockFields)
			|| !in_array(ToLower($table), ['b_iblock_element', 'b_iblock_section'])
		) {
			return false;
		}

		if ($fieldType == 'code') {
			$fw = 'CODE = "' . $DB->ForSql($field) . '"';
		} else if ($fieldType == 'xml_id') {
			$fw = 'XML_ID = "' . $DB->ForSql($field) . '"';

		} else if (! in_array($fieldType, $iblockFieldsBase)) {
			$fw = $fieldType . ' = "' . $DB->ForSql($field) . '"';
		} else {
			$fw = 'NAME = "' . $DB->ForSql($field) . '"';
		}

		/** @noinspection SqlNoDataSourceInspection */
		/** @noinspection SqlResolve */
		$sqlCheck = 'SELECT ID FROM ' . $table . ' WHERE ' . $fw . ' AND IBLOCK_ID = ' . (int)$iblockId;
		$el = $DB->Query($sqlCheck)->Fetch();

		if ((int)$el['ID'] > 0) {
			return (int)$el['ID'];
		} else {
			return false;
		}
	}

	/**
	 * Проверить наличие элемента с именем или симв. кодом $field в инфоблоке $iblockId.
	 * Удобно для импортеров.
	 *
	 * !!! Крайне желательно иметь двойной индекс $field / $iblockId в таблице b_iblock_element
	 *
	 * @param string $fieldVal по какому значению ищем
	 * @param int $iblockId
	 * @param string $fieldType  = 'name' тип поля: name|code|xml_id
	 * @return boolean|integer
	 */
	public static function checkElementExistsByNameOrCode($fieldVal, $iblockId, $fieldType = 'name')
	{
		return self::checkExistsByNameOrCode($fieldVal, $iblockId, $fieldType, 'b_iblock_element');
	}

	/**
	 * Проверить наличие секции с именем или симв. кодом $field в инфоблоке $iblockId.
	 * Удобно для импортеров.
	 *
	 * !!! Крайне желательно иметь двойной индекс $field / $iblockId в таблице b_iblock_section
	 *
	 * @param string $fieldVal по какому значению ищем
	 * @param int $iblockId
	 * @param string $fieldType  = 'name' тип поля: name|code|xml_id
	 * @return boolean|integer
	 */
	public static function checkSectionExistsByNameOrCode($fieldVal, $iblockId, $fieldType = 'name')
	{
		return self::checkExistsByNameOrCode($fieldVal, $iblockId, $fieldType, 'b_iblock_section');
	}

	/**
	 * Вернуть атрибуты товара из множественного свойства 1с
	 * @param array $propCml2Value
	 * @return array
	 */
	public static function returnCml2AttributesFromPropVal($propCml2Value): array
	{
		$attr = [];
		foreach ($propCml2Value as $v) {
			if (trim($v['VALUE']) == '') {
				continue;
			}
			$attr[ trim($v['DESCRIPTION']) ] = trim($v['VALUE']);
		}
		return $attr;
	}

	/**
	 * Выборка ID-значения списочного свойства из инфоблока и значения
	 * @param string $propCode код списочного свойства
	 * @param int $iblockId
	 * @param string $val искомое значение в списочном свойстве
	 * @return false|int
	 */
	public static function checkExistsEnumByVal($propCode, $iblockId, $val)
	{
		$val = trim($val);
		if ($val == '') {
			return false;
		}

		static $existsValues, $PROPERTY_ID;
		if (is_null($existsValues)) {
			$vs = self::selectPropertyEnumArray($propCode, ['IBLOCK_ID' => (int)$iblockId]);
			foreach ($vs as $v) {
				$existsValues[ trim($v['VALUE']) ] = $v['ID'];
			}
		}
		if (is_null($PROPERTY_ID)) {
			$arProp = \CIBlockProperty::GetList([], ['IBLOCK_ID' => (int)$iblockId, 'CODE' => $propCode])->Fetch();
			$PROPERTY_ID = (int)$arProp['ID'];
		}

		if (! isset($existsValues[ $val ])) {
			$ibpenum = new \CIBlockPropertyEnum();
			$propID = $ibpenum->Add(['PROPERTY_ID' => $PROPERTY_ID, 'VALUE' => $val, 'SORT' => 10 + count($existsValues) * 10]);
			$existsValues[ $val ] = $propID;
		}
		return $existsValues[ $val ];
	}

	/**
	 * Выбрать следующие/предыдущие $cntSelect штук относительно $elId
	 *
	 * @param int        $ELEMENT_ID относительно какого элемента выбрать след/предыдущие $cntSelect штук
	 * @param array      $rowSort = array('sort' => 'asc') сортировка ряда след./пред.
	 * @param array      $prevNextSelect = array('ID', 'NAME', 'DETAIL_PAGE_URL', 'DATE_ACTIVE_FROM') -
	 *                               какие поля выбрать у след./пред. элементов. Дополнительно еще выбирается
	 *                               TYPE - PREV|NEXT указывающий тип элемента (до или после элемента $elId)
	 * @param array      $dopFilter = array('ACTIVE' => 'Y') дополнительный фильтр при выборке.
	 *                               Помимо указанных значений всегда передается IBLOCK_ID указанного элемента $elId
	 *
	 * @param int|number $cntSelect = 1 по скольку элементов справа и слева выбрать
	 *
	 * @return array|bool
	 */
	public static function getNextPrevElementsByElementId($ELEMENT_ID, $rowSort = [], $prevNextSelect = [],
	                                                      $dopFilter = ['ACTIVE' => 'Y'], $cntSelect = 1)
	{
		$ELEMENT_ID = (int)$ELEMENT_ID;
		if ($ELEMENT_ID <= 0) {
			return false;
		}

		$IBLOCK_ID = self::getElementIblockId($ELEMENT_ID);
		if (! $IBLOCK_ID) {
			return false;
		}

		$arSort   = ['SORT' => 'ASC'];
		if (!empty( $rowSort )) {
			$arSort = $rowSort;
		}
		$arFields = ['ID', 'NAME', 'DETAIL_PAGE_URL', 'DATE_ACTIVE_FROM'];
		if ($prevNextSelect) {
			$arFields = array_merge($arFields, $prevNextSelect);
		}
		$arFilter = [
			'IBLOCK_ID' => (int)$IBLOCK_ID,
		];
		if ($dopFilter) {
			$arFilter = array_merge($arFilter, $dopFilter);
		}
		$arNavStartParams = [
			'nElementID' => $ELEMENT_ID
		];
		if ($cntSelect) {
			$arNavStartParams['nPageSize'] = (int)$cntSelect;
		}

		$rsList = \CIBlockElement::GetList($arSort, $arFilter, false, $arNavStartParams, $arFields);

		$arResult = [];
		while ($arItem = $rsList->GetNext()) {
			if ($arItem['ID'] != $ELEMENT_ID) {
				$arResult['PREV_NEXT'][$arItem['RANK']] = $arItem;
			} else {
				$arResult['CURRENT'] = $arItem;
			}
		}
		foreach ($arResult['PREV_NEXT'] as $key => $val) {
			if ($key < $arResult['CURRENT']['RANK']) {
				$arResult['PREV'][] = $val;
			}
			if ($key > $arResult['CURRENT']['RANK']) {
				$arResult['NEXT'][] = $val;
			}
		}
		return $arResult;
	}


	/**
	 * Выбор секции с детьми. В итоговый массив попадает и $sectionId
	 *
	 * @param int $sectionId
	 * @param array $arSelect = ["ID"]
	 * @return array | bool
	 */
	public static function selectSubsectionByParentSection($sectionId, $arSelect = ["ID"])
	{
		if ((int)$sectionId <= 0) {
			return false;
		}
		$arParentSection = \CIBlockSection::GetList(["SORT" => "ASC"], [
			"ACTIVE"    => "Y",
			"ID"      	=> $sectionId,
		], false, ['ID', 'IBLOCK_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'IBLOCK_ID'])->GetNext();

		$rsSections = \CIBlockSection::GetList(['LEFT_MARGIN' => 'ASC'], [
			"ACTIVE"        => "Y",
			"IBLOCK_ID"     => $arParentSection['IBLOCK_ID'],
			">LEFT_MARGIN"  => $arParentSection["LEFT_MARGIN"],
			"<RIGHT_MARGIN" => $arParentSection["RIGHT_MARGIN"],
			'>DEPTH_LEVEL'  => $arParentSection['DEPTH_LEVEL']
		], false, $arSelect);
		$sections = [];
		while ($Section = $rsSections->GetNext()) {
			$sections[] = $Section;
		}
		return $sections;
	}

	/**
	 * Добавляет фотки из ссылок, во множественное свойство типа файл
	 *
	 * @param int $ID
	 * @param int $IBLOCK_ID ID
	 * @param array $arFiles Массив файлов в виде ['link.png'=>'description']
	 * @param string $prop_code Код свойства
	 * @return bool|string
	 */
	public function AddMultipleFileValue($ID, $IBLOCK_ID, $arFiles, $prop_code)
	{
		$result = true;
		$ar = [];

		$rsPr = \CIBlockElement::GetProperty($IBLOCK_ID, $ID, ['sort', 'asc'], [
			'CODE' => $prop_code,
			'EMPTY' => 'N'
		]);
		while ($arPr = $rsPr->GetNext()) {
			$far = \CFile::GetFileArray($arPr['VALUE']);
			$far = \CFile::MakeFileArray($far['SRC']);
			$ar[] = [
				'VALUE' => $far,
				'DESCRIPTION' => $arPr['DESCRIPTION']
			];
		}
		foreach ($arFiles as $l => $d) {
			$art = false;
			$gn = 0;
			while (!$art || ($art['type'] == 'unknown' && $gn < 10)) {
				$gn++;
				$art = \CFile::MakeFileArray($l);
			}
			if ($art['tmp_name']) {
				$ar[] = [
					'VALUE' => $art,
					'DESCRIPTION' => $d
				];
			} else {
				$result = 'partial';
			}
		}
		\CIBlockElement::SetPropertyValuesEx($ID, $IBLOCK_ID, array(
			$prop_code => $ar
		));
		return $result;
	}

	/**
	 * По имени либо получает значение, либо если его нет - добавляет его в справочник.
	 *
	 * @param string $fieldVal = Значение, не может быть пустым (Dr.Pepper)
	 * @param string $iblockId = инентификатор справочника
	 *
	 * @retrun возвращается значение ID значения в справочнике
	 * @return bool|int
	 */
	public static function addToHelperAndReturnElementId($fieldVal, $iblockId)
	{
		$iblockId = (int)$iblockId;
		if ($iblockId <= 0 || ($fieldVal = trim($fieldVal)) == '') {
			return false;
		}

		static $cacheProcess;
		$cacheKey = $fieldVal . '|' . $iblockId;

		if (isset($cacheProcess[$cacheKey])) {
			return $cacheProcess[$cacheKey];
		}

		$checkBase = self::checkElementExistsByNameOrCode($fieldVal, $iblockId, 'name');
		if ($checkBase) {
			$cacheProcess[$cacheKey] = $checkBase;
		} else {
			$r = self::addElementToDb([
				'ACTIVE'        => 'Y',
				'NAME'          => $fieldVal,
				'IBLOCK_ID'     => $iblockId,
				'CODE'          => UnsortedUtils::TranslitText($fieldVal) . '-' . randString(3)
			]);
			if ($r->STATUS != UpdateResult::STATUS_OK) {
				return false;
			}
			$cacheProcess[$cacheKey] = (int)$r->RESULT;
		}
		return $cacheProcess[$cacheKey];
	}

	/**
	 * Получить новое сео у элемента
	 *
	 * @param int $IBLOCK_ID
	 * @param int $ID
	 *
	 * @return array
	 */
	public static function returnSeoValues($IBLOCK_ID, $ID): array
	{
		$ipropValues = new ElementValues((int)$IBLOCK_ID, (int)$ID);
		return $ipropValues->getValues();
	}

	/**
	 * Установить новое сео у элементов
	 * @param int   $IBLOCK_ID
	 * @param int   $ID
	 * @param array $arTemplates (обычно в ключах ELEMENT_META_DESCRIPTION, ELEMENT_META_TITLE, ELEMENT_META_KEYWORDS)
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function setSeoValues($IBLOCK_ID, $ID, $arTemplates = []): void
	{
		$ipropTemplates = new ElementTemplates((int)$IBLOCK_ID, (int)$ID);
		$ipropTemplates->set($arTemplates);
	}

	/**
	 * Установить новое сео у секций
	 * @param int   $IBLOCK_ID
	 * @param int   $ID
	 * @param array $arTemplates (обычно в ключах SECTION_META_DESCRIPTION, SECTION_META_TITLE, SECTION_META_KEYWORDS)
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function setSectionSeoValues($IBLOCK_ID, $ID, $arTemplates = []): void
	{
		$ipropTemplates = new SectionTemplates((int)$IBLOCK_ID, (int)$ID);
		$ipropTemplates->set($arTemplates);
	}

	/**
	 * Получить сео-поля секций
	 * @param int $IBLOCK_ID
	 * @param int $ID
	 * @return array
	 */
	public static function returnSectionSeoValues($IBLOCK_ID, $ID): array
	{
		$ipropValues = new SectionValues((int)$IBLOCK_ID, (int)$ID);
		return $ipropValues->getValues();
	}

	/**
	 * Установить секцию у элемента
	 *
	 * @param int $elId
	 * @param int $sectionId
	 *
	 * @return bool
	 */
	public static function setGroupToElement($elId, $sectionId): bool
	{
		$elId = (int)$elId;
		$sectionId = (int)$sectionId;
		if ($elId <= 0 || $sectionId <= 0) {
			return false;
		}

		$db_old_groups = \CIBlockElement::GetElementGroups($elId, true);
		$ar_new_groups = [$sectionId];
		while ($ar_group = $db_old_groups->Fetch()) {
			$ar_new_groups[] = $ar_group["ID"];
		}
		\CIBlockElement::SetElementSection($elId, $ar_new_groups);
		return true;
	}

} // end class

