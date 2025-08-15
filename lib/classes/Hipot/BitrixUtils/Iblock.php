<?php
namespace Hipot\BitrixUtils;

use Hipot\Services\BitrixEngine;
use Hipot\Types\UpdateResult,
	Hipot\Utils\UUtils,
	Hipot\Model\EntityHelper,
	Hipot\IbAbstractLayer\IblockElemLinkedChains;

use	Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	Bitrix\Iblock\InheritedProperty\ElementTemplates,
	Bitrix\Iblock\InheritedProperty\ElementValues,
	Bitrix\Iblock\InheritedProperty\SectionTemplates,
	Bitrix\Iblock\InheritedProperty\SectionValues,
	Bitrix\Iblock\PropertyIndex\Manager;

use CFile, CIblock, CIBlockElement, CIBlockProperty, CIBlockPropertyEnum, CIBlockSection, _CIBElement;
use function FormatText;

Loader::includeModule('iblock');

/**
 * Дополнительные утилиты для работы с инфоблоками
 *
 * IMPORTANT:
 * Некоторые методы выборки избыточны (лучше использовать bitrix api).
 * В основном необходимы для построения быстрых решений: к примеру, отчетов.
 *
 * @version 2.X
 */
class Iblock extends _CIBElement
{
	// region /*********************** iblock sections **************************/

	/**
	 * Добавление секции в инфоблок, возвращает ошибку либо ID результата, см. return
	 *
	 * @param array $arAddFields массив к добавлению
	 * @param bool $bResort = true перестроить ли дерево, можно отдельно вызывать CIBlockSection::Resort(int IBLOCK_ID);
	 * @param bool $bUpdateSearch = false обновить ли поиск
	 *
	 * @return UpdateResult
	 * @see \CIBlockSection::Add()
	 */
	public static function addSectionToDb($arAddFields = [], $bResort = true, $bUpdateSearch = false): UpdateResult
	{
		if (! is_array($arAddFields)) {
			$arAddFields = [];
		}

		$el = new CIBlockSection();
		$ID = $el->Add($arAddFields, $bResort, $bUpdateSearch);

		if ($ID) {
			return new UpdateResult(['RESULT' => $ID, 'STATUS' => UpdateResult::STATUS_OK]);
		}

		$errorTxt = method_exists($el, 'getLastError') ? $el->getLastError() : $el->LAST_ERROR;
		return new UpdateResult(['RESULT' => $errorTxt, 'STATUS' => UpdateResult::STATUS_ERROR]);
	}

	/**
	 * Обновление секции в инфоблоке, возвращает ошибку либо ID результата, см. return
	 *
	 * @param int   $ID код секции
	 * @param array $arAddFields массив к добавлению
	 * @param bool  $bResort = true перестроить ли дерево, можно отдельно вызывать CIBlockSection::Resort(int IBLOCK_ID);
	 * @param bool  $bUpdateSearch = false обновить ли поиск
	 *
	 * @return bool | UpdateResult
	 * @see \CIBlockSection::Add()
	 */
	public static function updateSectionToDb(int $ID, $arAddFields = [], $bResort = true, $bUpdateSearch = false)
	{
		if (! is_array($arAddFields)) {
			$arAddFields = [];
		}

		if ($ID <= 0) {
			return false;
		}

		$el		= new CIBlockSection();
		$res	= $el->Update($ID, $arAddFields, $bResort, $bUpdateSearch);
		if ($res) {
			return new UpdateResult(['RESULT' => $ID, 'STATUS' => UpdateResult::STATUS_OK]);
		}

		$errorTxt = method_exists($el, 'getLastError') ? $el->getLastError() : $el->LAST_ERROR;
		return new UpdateResult(['RESULT' => $errorTxt,	'STATUS' => UpdateResult::STATUS_ERROR]);
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
	public static function selectSectionsByFilter($arOrder, $arFilter, bool $bIncCnt = false, $arSelect = [], $arNavStartParams = false)
	{
		/** @noinspection TypeUnsafeArraySearchInspection */
		if (! in_array('ID', $arSelect)) {
			$arSelect[] = 'ID';
		}
		/** @noinspection TypeUnsafeArraySearchInspection */
		if (! in_array('IBLOCK_ID', $arSelect)) {
			$arSelect[] = 'IBLOCK_ID';
		}
		return CIBlockSection::GetList($arOrder, $arFilter, $bIncCnt, $arSelect, $arNavStartParams);
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
	public static function selectSectionsByFilterArray($arOrder, $arFilter, bool $bIncCnt = false,
	                                                   $arSelect = [], $arNavStartParams = false): array
	{
		$arResult = [];
		$rsSect = self::selectSectionsByFilter($arOrder, $arFilter, $bIncCnt, $arSelect, $arNavStartParams);
		while ($arSect = $rsSect->GetNext()) {
			$arResult[] = $arSect;
		}
		if (count($arResult) == 1) {
			$arResult = current($arResult);
		}
		return $arResult;
	}

	/**
	 * Выбор вложенных секций в родительскую, выбранную фильтром $parentSectionFilter
	 *
	 * @param array $parentSectionFilter [] фильтр для выбора секции-родителя
	 * @param array $arSelect ["ID"] поля дочерних выбираемых секций
	 */
	public static function selectSubsectionByParentSection(array $parentSectionFilter = [], array $arSelect = ["ID"], array $filter = ["ACTIVE" => "Y"]): array
	{
		if (count($parentSectionFilter) <= 0) {
			throw new \RuntimeException('parent section must be selected by $parentSectionFilter');
		}

		$arParentSection = CIBlockSection::GetList(["SORT" => "ASC"], $parentSectionFilter, false,
			['ID', 'IBLOCK_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'IBLOCK_ID']
		)->Fetch();

		$rsSections = CIBlockSection::GetList(['LEFT_MARGIN' => 'ASC'], array_merge($filter, [
			"IBLOCK_ID"     => $arParentSection['IBLOCK_ID'],
			">LEFT_MARGIN"  => $arParentSection["LEFT_MARGIN"] + 1,     // see "bitrix:catalog.section.list"-component
			"<RIGHT_MARGIN" => $arParentSection["RIGHT_MARGIN"],
			'>DEPTH_LEVEL'  => $arParentSection['DEPTH_LEVEL']
		]), false, $arSelect);
		$sections = [];
		while ($Section = $rsSections->GetNext()) {
			$sections[] = $Section;
		}
		return $sections;
	}

	/**
	 * Проверить наличие секции с именем или симв. кодом $field в инфоблоке $iblockId.
	 * Удобно для импортеров.
	 *
	 * !!! Крайне желательно иметь двойной индекс $field / $iblockId в таблице b_iblock_section
	 *
	 * @param string $fieldVal по какому значению ищем
	 * @param int    $iblockId
	 * @param string $fieldType = 'name' тип поля: name|code|xml_id
	 *
	 * @return boolean|integer
	 */
	public static function checkSectionExistsByNameOrCode($fieldVal, int $iblockId, $fieldType = 'name')
	{
		return self::checkExistsByNameOrCode($fieldVal, $iblockId, $fieldType, 'b_iblock_section');
	}

	// endregion

	// region /*********************** iblock elements **************************/

	/**
	 * Добавление элемента в инфоблок, возвращает ошибку либо ID результата, см. return
	 *
	 * @param array $arAddFields массив к добавлению. Свойства добавлять через ключ 'PROPERTY_VALUES'
	 * @param bool $bUpdateSearch = false обновить ли поиск
	 *
	 * @return UpdateResult
	 * @see \CIBlockElement::Add()
	 */
	public static function addElementToDb($arAddFields = [], bool $bUpdateSearch = false): UpdateResult
	{
		if (! is_array($arAddFields)) {
			$arAddFields = [];
		}

		$el = new CIBlockElement();
		$ID = $el->Add($arAddFields, false, $bUpdateSearch);

		if ($ID) {
			return new UpdateResult(['RESULT' => $ID, 'STATUS' => UpdateResult::STATUS_OK]);
		}

		$errorTxt = method_exists($el, 'getLastError') ? $el->getLastError() : $el->LAST_ERROR;
		return new UpdateResult(['RESULT' => $errorTxt,	'STATUS' => UpdateResult::STATUS_ERROR]);
	}

	/**
	 * Обновление элемента в инфоблоке, возвращает ошибку либо ID результата, см. return
	 *
	 * @param int   $ID массив к добавлению
	 * @param array $arAddFields массив к добавлению
	 * @param bool  $bUpdateSearch = false обновить ли поиск
	 *
	 * @return bool | \Hipot\Types\UpdateResult
	 * @see \CIBlockElement::Update()
	 */
	public static function updateElementToDb(int $ID, $arAddFields = [], $bUpdateSearch = false)
	{
		if (! is_array($arAddFields)) {
			$arAddFields = [];
		}

		if ($ID <= 0) {
			return false;
		}

		if (isset($arAddFields["PROPERTY_VALUES"])) {
			$PROPS = $arAddFields["PROPERTY_VALUES"];
			unset($arAddFields["PROPERTY_VALUES"]);
		}

		$el 	= new CIBlockElement();
		$bUpd 	= $el->Update($ID, $arAddFields, false, $bUpdateSearch);

		if (isset($PROPS) && $bUpd) {
			$iblockId = self::getElementIblockId($ID);
			CIBlockElement::SetPropertyValuesEx($ID, $iblockId, $PROPS);
			Manager::updateElementIndex($iblockId, $ID);
		}

		if ($bUpd) {
			return new UpdateResult(['RESULT' => $ID, 'STATUS' => UpdateResult::STATUS_OK]);
		}

		$errorTxt = method_exists($el, 'getLastError') ? $el->getLastError() : $el->LAST_ERROR;
		return new UpdateResult(['RESULT' => $errorTxt,	'STATUS' => UpdateResult::STATUS_ERROR]);
	}

	/**
	 * По имени либо получает значение, либо если его нет - добавляет его в справочник.
	 *
	 * @param string $fieldVal = Значение, не может быть пустым (Dr.Pepper)
	 * @param int $iblockId = инентификатор справочника
	 *
	 * @retrun возвращается значение ID значения в справочнике
	 * @return bool|int
	 */
	public static function addToHelperAndReturnElementId(string $fieldVal, int $iblockId)
	{
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
				'CODE'          => UUtils::TranslitText($fieldVal) . '-' . randString(3)
			]);
			if ($r->STATUS != UpdateResult::STATUS_OK) {
				return false;
			}
			$cacheProcess[$cacheKey] = (int)$r->RESULT;
		}
		return $cacheProcess[$cacheKey];
	}

	/**
	 * Проверить наличие элемента с именем или симв. кодом $field в инфоблоке $iblockId.
	 * Удобно для импортеров.
	 *
	 * !!! Крайне желательно иметь двойной индекс $field / $iblockId в таблице b_iblock_element
	 *
	 * @param string $fieldVal по какому значению ищем
	 * @param int    $iblockId
	 * @param string $fieldType = 'name' тип поля: name|code|xml_id
	 *
	 * @return boolean|integer
	 */
	public static function checkElementExistsByNameOrCode($fieldVal, int $iblockId, $fieldType = 'name')
	{
		return self::checkExistsByNameOrCode($fieldVal, $iblockId, $fieldType, 'b_iblock_element');
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
	 * {@inheritdoc}
	 */
	public static function selectElementsByFilter($arOrder, $arFilter, $arGroupBy = false, $arNavParams = false, $arSelect = [])
	{
		return CIBlockElement::GetList($arOrder, $arFilter, $arGroupBy, $arNavParams, $arSelect);
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
	 * @return array|int
	 */
	public static function selectElementsByFilterArray($arOrder, $arFilter, $arGroupBy = false, $arNavParams = false,
	                                                   $arSelect = [], $SelectAllProps = false, $OnlyPropsValue = true,
	                                                   $bSelectChains = false, $selectChainsDepth = 3, bool $returnOneIfOnlyOneSelected = true)
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
			$obChainBuilder = new IblockElemLinkedChains();
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
		if ($returnOneIfOnlyOneSelected && count($arResult) == 1) {
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
	public static function selectElementProperties($ID, $IBLOCK_ID = 0, $onlyValue = false, $exFilter = [], $obChainBuilder = null, $selectChainsDepth = 3)
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
		$db_props = CIBlockElement::GetProperty($IBLOCK_ID, $ID, ["sort" => "asc"], $arFilter);
		while ($ar_props = $db_props->Fetch()) {

			// довыборка цепочек глубиной 3, магия чепочек в ключе CHAIN
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
				$ar_props['VALUE']['TEXT'] = FormatText($ar_props['VALUE']['TEXT'], $ar_props['VALUE']['TYPE']);
			}
			if ($ar_props['PROPERTY_TYPE'] == 'F') {
				$ar_props['FILE_PARAMS'] = CFile::GetFileArray($ar_props['VALUE']);
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
	 *
	 * @param int $elementId код элемента
	 *
	 * @return bool|integer
	 */
	public static function getElementIblockId(int $elementId): int
	{
		return (int)CIBlockElement::GetIBlockByID($elementId);
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
	public static function getNextPrevElementsByElementId(int $ELEMENT_ID, $rowSort = [], $prevNextSelect = [], $dopFilter = ['ACTIVE' => 'Y'], $cntSelect = 1)
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

		$rsList = CIBlockElement::GetList($arSort, $arFilter, false, $arNavStartParams, $arFields);

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
	 * Установить секцию у элемента
	 *
	 * @param int $elId
	 * @param int $sectionId
	 *
	 * @return bool
	 */
	public static function setGroupToElement(int $elId, int $sectionId): bool
	{
		if ($elId <= 0 || $sectionId <= 0) {
			return false;
		}

		$db_old_groups = CIBlockElement::GetElementGroups($elId, true);
		$ar_new_groups = [$sectionId];
		while ($ar_group = $db_old_groups->Fetch()) {
			$ar_new_groups[] = $ar_group["ID"];
		}
		CIBlockElement::SetElementSection($elId, $ar_new_groups);
		return true;
	}

	// endregion

	// region /*********************** iblock elements properties **************************/

	/**
	 * Получить варианты значения выпадающего списка
	 *
	 * @param string|int $propCode
	 * @param array      $arFilterEx [] фильтр по выборке вариантов
	 * @param array      $aSort ["DEF" => "DESC", "SORT" => "ASC"]
	 * @param bool       $indexed
	 *
	 * @return array
	 */
	public static function selectPropertyEnumArray(string|int $propCode, array $arFilterEx = [], array $aSort = ["DEF" => "DESC", "SORT" => "ASC"], bool $indexed = true): array
	{
		if (trim($propCode) == '') {
			return [];
		}

		$arFilter = [];
		if (is_numeric($propCode)) {
			$arFilter['PROPERTY_ID']    = (int)$propCode;
		} else {
			$arFilter['PROPERTY_CODE']	= $propCode;
		}
		foreach ($arFilterEx as $f => $filter) {
			$arFilter[ $f ] = $filter;
		}

		$arRes = [];
		$property_enums = CIBlockPropertyEnum::GetList($aSort, $arFilter);
		while ($enum_fields = $property_enums->GetNext()) {
			if ($indexed) {
				$arRes[ $enum_fields['ID'] ] = $enum_fields;
			} else {
				$arRes[] = $enum_fields;
			}
		}
		return $arRes;
	}

	/**
	 * Вернуть атрибуты товара из множественного свойства 1с
	 * @param array $propCml2Value
	 * @return array
	 */
	public static function returnCml2AttributesFromPropVal(array $propCml2Value): array
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
	 *
	 * @param string $propCode код списочного свойства
	 * @param int    $iblockId
	 * @param string $val искомое значение в списочном свойстве
	 *
	 * @return false|int
	 */
	public static function checkExistsEnumByVal($propCode, int $iblockId, $val)
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
			$arProp = CIBlockProperty::GetList([], ['IBLOCK_ID' => (int)$iblockId, 'CODE' => $propCode])->Fetch();
			$PROPERTY_ID = (int)$arProp['ID'];
		}

		if (! isset($existsValues[ $val ])) {
			$ibpenum = new CIBlockPropertyEnum();
			$propID = $ibpenum->Add(['PROPERTY_ID' => $PROPERTY_ID, 'VALUE' => $val, 'SORT' => 10 + count($existsValues) * 10]);
			$existsValues[ $val ] = $propID;
		}
		return $existsValues[ $val ];
	}

	/**
	 * Добавляет фотки из ссылок, во множественное свойство типа файл
	 *
	 * @param int    $ID
	 * @param int    $IBLOCK_ID ID
	 * @param array  $arFiles Массив файлов в виде ['link.png'=>'description']
	 * @param string $prop_code Код свойства
	 *
	 * @return bool|string
	 */
	public function addMultipleFileValue(int $ID, int $IBLOCK_ID, $arFiles = [], string $prop_code)
	{
		$result = true;
		$ar = [];

		$rsPr = CIBlockElement::GetProperty($IBLOCK_ID, $ID, ['sort', 'asc'], [
			'CODE' => $prop_code,
			'EMPTY' => 'N'
		]);
		while ($arPr = $rsPr->GetNext()) {
			$far = CFile::GetFileArray($arPr['VALUE']);
			$far = CFile::MakeFileArray($far['SRC']);
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
				$art = CFile::MakeFileArray($l);
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
		CIBlockElement::SetPropertyValuesEx($ID, $IBLOCK_ID, [
			$prop_code => $ar
		]);
		return $result;
	}

	public static function checkPropertyExistsByNameOrCode($fieldVal, int $iblockId, $fieldType = 'name')
	{
		return self::checkExistsByNameOrCode($fieldVal, $iblockId, $fieldType, 'b_iblock_property');
	}
	// endregion

	// region /*********************** seo values **************************/

	/**
	 * Получить новое сео у элемента
	 *
	 * @param int $IBLOCK_ID
	 * @param int $ID
	 *
	 * @return array
	 */
	public static function returnSeoValues(int $IBLOCK_ID, int $ID): array
	{
		return (new ElementValues($IBLOCK_ID, $ID))->getValues();
	}

	/**
	 * Установить новое сео у элементов
	 *
	 * @param int   $IBLOCK_ID
	 * @param int   $ID
	 * @param array $arTemplates (обычно в ключах ELEMENT_META_DESCRIPTION, ELEMENT_META_TITLE, ELEMENT_META_KEYWORDS)
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function setSeoValues(int $IBLOCK_ID, int $ID, $arTemplates = []): void
	{
		$ipropTemplates = new ElementTemplates($IBLOCK_ID, $ID);
		$ipropTemplates->set($arTemplates);
	}

	/**
	 * Установить новое сео у секций
	 *
	 * @param int   $IBLOCK_ID
	 * @param int   $ID
	 * @param array $arTemplates (обычно в ключах SECTION_META_DESCRIPTION, SECTION_META_TITLE, SECTION_META_KEYWORDS)
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function setSectionSeoValues(int $IBLOCK_ID, int $ID, $arTemplates = []): void
	{
		$ipropTemplates = new SectionTemplates($IBLOCK_ID, $ID);
		$ipropTemplates->set($arTemplates);
	}

	/**
	 * Получить сео-поля секций
	 *
	 * @param int $IBLOCK_ID
	 * @param int $ID
	 *
	 * @return array
	 */
	public static function returnSectionSeoValues(int $IBLOCK_ID, int $ID): array
	{
		return (new SectionValues($IBLOCK_ID, $ID))->getValues();
	}

	// endregion

	// region /*********************** cache and tag cache performance tweaks **************************/

	/**
	 * Отключает сброс тэгированного кэша инфоблока
	 * @return bool
	 */
	public static function disableIblockCacheClear(): bool
	{
		while (CIblock::isEnabledClearTagCache()) {
			CIblock::disableClearTagCache();
		}
		return true;
	}

	/**
	 * Включает сброс тэгированного кэша инфоблока
	 * @return bool
	 */
	public static function enableIblockCacheClear(): bool
	{
		while (!CIblock::isEnabledClearTagCache()) {
			CIblock::enableClearTagCache();
		}
		return true;
	}

	/**
	 * run update sets of elements between startMultipleElemsUpdate() and endMultipleElemsUpdate()
	 * @param int $iblockId
	 * @return void
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function startMultipleElemsUpdate(int $iblockId): void
	{
		Manager::enableDeferredIndexing();
		if (Loader::includeModule('catalog')) {
			\Bitrix\Catalog\Product\Sku::enableDeferredCalculation();
		}
		\CAllIBlock::disableTagCache($iblockId);
	}

	/**
	 * run update sets of elements between startMultipleElemsUpdate() and endMultipleElemsUpdate()
	 * @param int $iblockId
	 * @return void
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function endMultipleElemsUpdate(int $iblockId): void
	{
		\CAllIBlock::enableTagCache($iblockId);
		\CAllIBlock::clearIblockTagCache($iblockId);

		if (Loader::includeModule('catalog')) {
			\Bitrix\Catalog\Product\Sku::disableDeferredCalculation();
			\Bitrix\Catalog\Product\Sku::calculate();
		}

		Manager::disableDeferredIndexing();
		Manager::runDeferredIndexing($iblockId);
	}

	// endregion

	/**
	 * Проверить наличие элемента/секции с именем или симв. кодом $field в инфоблоке $iblockId.
	 *
	 * @param string $field
	 * @param int    $iblockId
	 * @param string $fieldType
	 * @param string $table = b_iblock_element | b_iblock_section
	 *
	 * @return boolean | int возвращает ID найденного элемента
	 */
	public static function checkExistsByNameOrCode(string $field, int $iblockId, string $fieldType = 'name', string $table = 'b_iblock_element'): bool|int
	{
		global $DB;

		$fieldType = ToLower($fieldType);

		$iblockFieldsBase = ['code', 'xml_id', 'name', 'preview_text'];

		$fields = EntityHelper::getTableFields($table);
		foreach ($fields as &$f) {
			$f = ToLower($f);
			$iblockFields[] = $f;
		}
		unset($f);

		/** @noinspection TypeUnsafeArraySearchInspection */
		if ($iblockId == 0 || trim($field) == ''
			|| !in_array(trim($fieldType, '?=%!<> '), $iblockFields)
			|| !in_array(ToLower($table), ['b_iblock_element', 'b_iblock_section', 'b_iblock_property'])
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
		$sqlCheck = 'SELECT ID FROM ' . $table . ' WHERE ' . $fw . ' AND IBLOCK_ID = ' . $iblockId;
		$el = $DB->Query($sqlCheck)->Fetch();

		if ((int)$el['ID'] > 0) {
			return (int)$el['ID'];
		}

		return false;
	}

	/**
	 * Получить массив ids элементов инфоблока $iblockId по запросу $query
	 *
	 * @param string $query строка запроса
	 * @param int    $iblockId код инфоблока
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function queryIblockItemsSearch(string $query, $iblockId): array
	{
		$r = [];
		if (!Loader::includeModule('search')) {
			return $r;
		}

		// TODO Can't group on 'RANK' (400) bitrix
		$query = str_replace(['"', '\''], '', $query);
		$query = trim($query);

		$obSearch = new \CSearch();
		$obSearch->Search([
			'QUERY'         => $query,
			//'SITE_ID'     => SITE_ID,
			'MODULE_ID'     => 'iblock',
		], ["RANK" => "DESC"], [
			"=MODULE_ID" => "iblock",
			"!ITEM_ID" => "S%",
			"=PARAM2" => [(int)$iblockId],
		]);
		while ($arResult = $obSearch->GetNext()) {
			$r[] = $arResult['ITEM_ID'];
		}
		return $r;
	}

	/**
	 * Add Hermitage button links for element.
	 *
	 * @param array &$section Section data
	 *
	 * @return void
	 */
	public static function setSectionPanelButtons(array &$section): void
	{
		$buttons = \CIBlock::GetPanelButtons(
			$section['IBLOCK_ID'],
			0,
			$section['ID'],
			['SESSID' => false, 'CATALOG' => true]
		);
		$section['EDIT_LINK'] = $buttons['edit']['edit_section']['ACTION_URL'];
		$section['DELETE_LINK'] = $buttons['edit']['delete_section']['ACTION_URL'];
	}

	/**
	 * Add Hermitage button links for element.
	 *
	 * @param array &$element			Element data.
	 *
	 * @return void
	 */
	public static function setElementPanelButtons(array &$element): void
	{
		$buttons = \CIBlock::GetPanelButtons(
			$element['IBLOCK_ID'],
			$element['ID'],
			$element['IBLOCK_SECTION_ID'],
			['SECTION_BUTTONS' => false, 'SESSID' => false, 'CATALOG' => true]
		);
		$element['EDIT_LINK'] = $buttons['edit']['edit_element']['ACTION_URL'];
		$element['DELETE_LINK'] = $buttons['edit']['delete_element']['ACTION_URL'];
	}

	/**
	 * @param \CBitrixComponent $component
	 * @param array{'IBLOCK_ID':int, 'SECTION_ID':int} $arResult
	 *
	 * @return void
	 */
	public static function addComponentPanelButtons(\CBitrixComponent $component, array $arResult): void
	{
		UUtils::setComponentEdit($component, $arResult, ['SECTION_BUTTONS' => false]);
	}

	/**
	 * Получить минимальную цену из $priceCodes у элемента $element.
	 * Чтобы максимально сократить число запросов - передавать уже выбранный элемент с полями: ID, IBLOCK_ID, 'CATALOG_GROUP_*'
	 *
	 * @param array{'ID': int} &$element У элемента заполняются массивы с ценами 'PRICES', 'MIN_PRICE' и данными по каталогу 'CATALOG_GROUP_*'
	 * @param array             $priceCodes = ['BASE']
	 * @param bool              $bVATInclude = true
	 *
	 * @return array{'DISCOUNT_VALUE':float, 'VALUE':float, 'PRINT_VALUE':string, 'PRINT_DISCOUNT_VALUE':string, 'CURRENCY':string}
	 */
	public static function getElementMinPrice(array &$element, array $priceCodes = ['BASE'], bool $bVATInclude = true): array
	{
		$element['ID'] = (int)$element['ID'];

		if ($element['ID'] <= 0) {
			throw new \InvalidArgumentException("Hasn't ID on element param: " . print_r($element, true));
		}
		if (count($priceCodes) <= 0) {
			throw new \InvalidArgumentException("Hasn't priceCodes param: " . print_r($priceCodes, true));
		}

		$result = [];

		if ((int)$element['IBLOCK_ID'] <= 0) {
			$element['IBLOCK_ID'] = self::getElementIblockId($element['ID']);
		}

		static $prices;
		if ($prices === null) {
			$prices = \CIBlockPriceTools::GetCatalogPrices($element['IBLOCK_ID'], $priceCodes);
		}

		$bNeedSelect = true;
		$arSelect = ['ID', 'IBLOCK_ID'];
		foreach ($prices as $value) {
			$arSelect[] = $value['SELECT'];
			$catalogPriceValue = 'CATALOG_PRICE_'.$value['ID'];
			$catalogCurrencyValue = 'CATALOG_CURRENCY_'.$value['ID'];
			if (isset($element[$catalogPriceValue], $element[$catalogCurrencyValue])) {
				$bNeedSelect = false;
			}
		}

		if ($bNeedSelect) {
			$element = self::selectElementsByFilterArray(['ID' => 'ASC'], [
				'ID' => $element['ID'],
				'IBLOCK_ID' => $element['IBLOCK_ID']
			], false, false, $arSelect);
		}

		$element['PRICES'] = \CIBlockPriceTools::GetItemPrices(
			$element['IBLOCK_ID'],
			$prices,
			$element,
			$bVATInclude,
			['CURRENCY_ID' => Option::get("sale", "default_currency", "RUB")]
		);
		if (!empty($element['PRICES'])) {
			$element['MIN_PRICE'] = $result = \CIBlockPriceTools::getMinPriceFromList($element['PRICES']);
		}

		return $result;
	}

} // end class

