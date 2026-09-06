<?
/**
 * Abstract Layer - Подсказки на выборки CIBlockElement::GetList()<br>
 * точка входа в данный функционал и максимальные утилиты для инфоблока
 *
 * @version 3.4 beta
 * @author hipot <hipot at ya dot ru>
 */
namespace Hipot\IbAbstractLayer;

use Hipot\IbAbstractLayer\Types\IblockElementItem,
	Hipot\BitrixUtils\Iblock;
use Hipot\Types\Collection\Collection;

/**
 * Класс для работы с получением цепочек связанных элементов (через свойства привязка к элементам),
 * закрывает кольцевые цепочки
 *
 * Также для всей магии фреймфорка hipot для работы с инфоблоками
 *
 * @example
 * if ($arProps['series']['PROPERTY_TYPE'] == "E") {
 *		$obChainBuilder = new IblockElemLinkedChains();
 *		$obChainBuilder->init(2);
 *		$arProps['series']['CHAIN'] = $obChainBuilder->getChains_r($arProps['series']['VALUE']);
 * }
 */
final class IblockElemLinkedChains extends Iblock
{
	/**
	 * Корень получаемой цепочки
	 * @var int|null
	 */
	private ?int $__topLevelId;

	/**
	 * Максимальный уровень вложенности
	 * @var int
	 */
	private int $__maxLevel;

	/**
	 * Текущий уровень, для итераций
	 * @var int
	 */
	private int $__level;
	
	/**
	 * Уже выбранные элементы, чтобы не выбирать их вновь (кеш)
	 * в ключе - ID элемента, в значении весь элемент с цепочкой ниже
	 * @var array
	 */
	private array $__cacheItems;

	public function __construct()
	{
		$this->__cacheItems = [];
	}

	/**
	 * Инициализация получения цепочки
	 * !! Нужно вызывать перед каждым вызовом getChains_r()
	 *
	 * @param int $maxLevel = 3 Максимальный уровень вложения (O)
	 */
	public function init(int $maxLevel = 3): void
	{
		$this->__topLevelId = NULL;
		$this->__maxLevel = (int)$maxLevel;
		$this->__level = 0;
	}

	/**
	 * Рекурсивный метод получения цепочек
	 *
	 * @param int   $elementId корневой элемент для получения цепочки
	 * @param array $arSelect = array() массив выбираемых полей, всегда выбираются "ID", "IBLOCK_ID", "DETAIL_PAGE_URL", "NAME"
	 * Возвращает цепочку уровнем, указанным в init()
	 *
	 * @return array|void
	 */
	public function getChains_r(int $elementId, array $arSelect = [])
	{
		$elementId = (int)$elementId;
		
		if ($this->__topLevelId == $elementId || $this->__maxLevel == $this->__level) {
			return;
		}
		if (! $this->__topLevelId) {
			$this->__topLevelId = $elementId;
		}
		$this->__level++;

		// если элемент еще не выбирался
		if (! isset($this->__cacheItems[ $elementId ])) {
		
			$arSelectDef = ["ID", "IBLOCK_ID", "DETAIL_PAGE_URL", "NAME"];
			$arSelect = array_merge($arSelect, $arSelectDef);
			$arFilter = ['ID' => $elementId];
			// QUERY 1
			$rsItems = self::selectElementsByFilter([], $arFilter, false, false, $arSelect);
	
			if ($arItem = $rsItems->GetNext()) {
				// QUERY 2
				$arItem['PROPERTIES'] = self::selectElementProperties(
					$arItem['ID'],
					$arItem["IBLOCK_ID"],
					false,
					["EMPTY" => "N"],
					$this
				);
			}
			$this->__cacheItems[ $elementId ] = $arItem;
			
		} else {
			$arItem = $this->__cacheItems[ $elementId ];
		}

		return $arItem;
	}
	
	/**
	 * Преобразование цепочки связанных элементов из массива в объекты абстрактного уровня
	 *
	 * @param array $arChain
	 *
	 * @return IblockElementItem
	 */
	public static function chainArrayToChainObject(array $arChain): IblockElementItem
	{
		return new IblockElementItem($arChain);
	}

	/**
	 * Выборка элементов инфоблока сразу в виде объектов Hipot\IbAbstractLayer\Types\IblockElementItem
	 *
	 * @param string[] $arOrder
	 * @param array    $arFilter
	 * @param false    $arGroupBy
	 * @param false    $arNavParams
	 * @param array    $arSelectFields
	 *
	 * @return array|mixed
	 */
	public static function getList(array $arOrder = ["SORT" => "ASC"], array $arFilter = [], $arGroupBy = false, $arNavParams = false, array $arSelectFields = [])
	{
		$arSelectFields[] = 'ID';
		$arSelectFields[] = 'IBLOCK_ID';

		$rsItems = \CIBlockElement::GetList($arOrder, $arFilter, $arGroupBy, $arNavParams, $arSelectFields);

		// fix select cnt by group by []
		if (!is_object($rsItems) || !method_exists($rsItems, 'GetNext')) {
			return $rsItems;
		}

		$arParams = $arResult = [];
		$arParams['SELECT_CHAINS_DEPTH'] = defined('ABSTRACT_LAYER_SELECT_CHAINS_DEPTH') ? ABSTRACT_LAYER_SELECT_CHAINS_DEPTH : 3;
		$arResult["ITEMS"] = [];

		$obChainBuilder = new self();

		while ($arItem = $rsItems->GetNext()) {
			// QUERY 2
			$arItem['PROPERTIES'] = self::selectElementProperties(
				(int)$arItem['ID'],
				(int)$arItem["IBLOCK_ID"],
				false,
				["EMPTY" => "N"],
				$obChainBuilder,
				(int)$arParams['SELECT_CHAINS_DEPTH']
			);

			$arResult["ITEMS"][] = new IblockElementItem($arItem);
		}

		// освобождаем память от цепочек
		if (isset($obChainBuilder)) {
			unset($obChainBuilder);
		}

		return new Collection($arResult["ITEMS"]);
	}
}
