<?php

namespace Hipot\BitrixUtils\Iblock;

use Bitrix\Iblock\InheritedProperty\SectionTemplates;
use Bitrix\Iblock\InheritedProperty\SectionValues;
use Hipot\Types\UpdateResult;

use CIBlockSection;

trait Section
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
	public static function addSectionToDb(array $arAddFields = [], bool $bResort = true, bool $bUpdateSearch = false): UpdateResult
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
	public static function updateSectionToDb(int $ID, array $arAddFields = [], bool $bResort = true, bool $bUpdateSearch = false): UpdateResult
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

	// region /*********************** seo values **************************/

	/**
	 * Установить новое сео у секций
	 *
	 * @param int   $IBLOCK_ID
	 * @param int   $ID
	 * @param array{'SECTION_META_DESCRIPTION':string, 'SECTION_META_TITLE':string, 'SECTION_META_KEYWORDS':string} $arTemplates
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function setSectionSeoValues(int $IBLOCK_ID, int $ID, array $arTemplates = []): void
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
}