<?
namespace Hipot\BitrixUtils;

/**
 * Работа с заказами и корзиной, oldway bitrix
 *
 * @version 1.2
 */
class SaleUtils
{
	/**
	 * Получаем массив свойств заказа по ID
	 *
	 *
	 * @param int $orderId - ID заказа
	 * @param bool $orderPropsIdInKey - в ключе ID свойства
	 * @return array - код свойства => значение
	 */
	static function getOrderProps($orderId, $orderPropsIdInKey = false)
	{
		if (($orderId = (int)$orderId) <= 0)  {
			return false;
		}
		\CModule::IncludeModule('sale');

		$arProps = array();
		$res = \CSaleOrderPropsValue::GetOrderProps($orderId);
		while ( $ar = $res->Fetch() ) {
			if ($orderPropsIdInKey) {
				$hash = $ar['ORDER_PROPS_ID'];
			} else {
				$hash = $ar['CODE'];
			}
			$arProps [ $hash ] = $ar;
		}

		return $arProps;
	}

	/**
	 * Делаем справочники по свойствам-спискам заказа
	 *
	 * @param int $orderPropsId - ID свойства заказа
	 * @return array
	 */
	public static function getOrderPropsVariant($orderPropsId)
	{
		if (($orderPropsId = (int)$orderPropsId) <= 0)  {
			return false;
		}

		static $_cache;

		if (! isset($_cache[ $orderPropsId ])) {
			$_cache[ $orderPropsId ] = array();

			\CModule::IncludeModule('sale');
			$res = \CSaleOrderPropsVariant::GetList(
				array("ID" => "ASC"),
				array("ORDER_PROPS_ID" => $orderPropsId)
			);
			while ($ar = $res->Fetch()) {
				$_cache[ $orderPropsId ][] = $ar;
			}
		}

		return (! empty($_cache[ $orderPropsId ])) ? $_cache[ $orderPropsId ] : false;
	}


	/**
	 * Устанавливает в заказ $orderId свойства $arProps
	 *
	 * @param int $orderId - ID  заказа
	 * @param array $arProps - - массив в ключе код свойства, в значении его значения
	 * @return boolean
	 */
	public static function setOrderProps($orderId, $arProps)
	{
		if (
			($orderId = (int)$orderId) <= 0
			|| ! is_array($arProps)
			|| empty($arProps)
		) {
			return false;
		}

		$ret = false;

		\CModule::IncludeModule('sale');

		$arOrderProps = self::getOrderProps($orderId, false, true);
		foreach ($arProps as $propCode => $propValue) {
			if ($arOrderProps[ $propCode ]) {
				\CSaleOrderPropsValue::Update($arOrderProps[ $propCode ]['ID'], array('VALUE' => $propValue));
				$ret = true;
				continue;
			}

			$orderPropsDir = self::getSaleOrderPropsDir();

			if (array_key_exists($propCode, $orderPropsDir)) {
				\CSaleOrderPropsValue::Add(array(
					"ORDER_ID"			=> $orderId,
					"ORDER_PROPS_ID"	=> $orderPropsDir[ $propCode ]["ID"],
					"NAME"				=> $orderPropsDir[ $propCode ]["NAME"],
					"CODE"				=> $orderPropsDir[ $propCode ]["CODE"],
					"VALUE"				=> $propValue
				));
				$ret = true;
			}
		}

		return $ret;
	}

	/**
	 * Получить корзину текущего пользователя
	 *
	 * @param 	array	 	$arSelect - какие поля выбирать
	 * @param 	bool 		$needBasketProps 	= false - выбирать ли свойства корзины
	 * @param  	string|int 	$orderId 			= 'NULL' можно выбрать строки конкретного заказа
	 * @return array
	 */
	public static function getCurrentBasketItems($arSelect = array(), $needBasketProps = false, $orderId = 'NULL')
	{
		\CModule::IncludeModule('sale');

		$arBasketItems = array();

		$arSel = array(
			"ID",				"NAME",
			"PRODUCT_ID",		"QUANTITY",
			"PRICE",			"CURRENCY",
			"CAN_BUY"
		);

		if (is_array($arSelect) && ! empty($arSelect)) {
			$arSel = array_merge($arSel, $arSelect);
		}

		if ((int)$orderId > 0 && $orderId != 'NULL') {
			$arF = array(
				"ORDER_ID"	=> (int)$orderId,
			);
		} else {
			$arF = array(
				"FUSER_ID"	=> \CSaleBasket::GetBasketUserID(),
				"ORDER_ID"	=> $orderId
			);
		}

		$dbBasketItems = \CSaleBasket::GetList(
			array("ID" => "ASC"),
			$arF,
			false,
			false,
			$arSel
		);
		while ($arItems = $dbBasketItems->Fetch()) {
			$arBasketItems[ $arItems['ID'] ] = $arItems;
		}

		if (! empty($arBasketItems) && $needBasketProps) {
			$res = \CSaleBasket::GetPropsList(
				array("ID" => "ASC"),
				array("BASKET_ID" => array_keys($arBasketItems))
			);
			$i = 0;
			while ($ar = $res->Fetch())	{
				$k = (trim($ar['CODE']) != '') ? $ar['CODE'] : $i++;
				$arBasketItems[ $ar['BASKET_ID'] ]['PROPS'][ $k ] = $ar;
			}
		}

		return $arBasketItems;
	}


	/**
	 * Получить справочник свойств заказа
	 *
	 * @return array
	 */
	public static function getSaleOrderPropsDir()
	{
		static $_cache;

		if (! isset($_cache)) {
			$_cache = array();

			\CModule::IncludeModule('sale');
			$dbOrderProperties = \CSaleOrderProps::GetList(
				array("ID" => "ASC"),
				array(),
				false,
				false,
				array()
			);
			while ($arOrderProperties = $dbOrderProperties->Fetch()) {
				$_cache[ $arOrderProperties['CODE'] ] = $arOrderProperties;
			}
		}

		return $_cache;
	}
}

