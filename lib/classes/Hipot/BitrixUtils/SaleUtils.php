<?
namespace Hipot\BitrixUtils;

use Bitrix\Main\Loader;
use Bitrix\Sale;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaySystem;

/**
 * Работа с заказами и корзиной
 *
 * @version 1.4
 */
class SaleUtils
{
	/**
	 * Получаем массив свойств заказа по ID
	 *
	 *
	 * @param int  $orderId - ID заказа
	 * @param bool $orderPropsIdInKey - в ключе ID свойства
	 *
	 * @return array - код свойства => значение
	 */
	static function getOrderProps($orderId, $orderPropsIdInKey = false)
	{
		if (($orderId = (int)$orderId) <= 0) {
			return false;
		}
		\CModule::IncludeModule('sale');

		$arProps = array();
		$res = \CSaleOrderPropsValue::GetOrderProps($orderId);
		while ($ar = $res->Fetch()) {
			if ($orderPropsIdInKey) {
				$hash = $ar['ORDER_PROPS_ID'];
			} else {
				$hash = $ar['CODE'];
			}
			$arProps [$hash] = $ar;
		}

		return $arProps;
	}

	/**
	 * Делаем справочники по свойствам-спискам заказа
	 *
	 * @param int $orderPropsId - ID свойства заказа
	 *
	 * @return array
	 */
	public static function getOrderPropsVariant($orderPropsId)
	{
		if (($orderPropsId = (int)$orderPropsId) <= 0) {
			return false;
		}

		static $_cache;

		if (!isset($_cache[$orderPropsId])) {
			$_cache[$orderPropsId] = array();

			\CModule::IncludeModule('sale');
			$res = \CSaleOrderPropsVariant::GetList(
				array("ID" => "ASC"),
				array("ORDER_PROPS_ID" => $orderPropsId)
			);
			while ($ar = $res->Fetch()) {
				$_cache[$orderPropsId][] = $ar;
			}
		}

		return (!empty($_cache[$orderPropsId])) ? $_cache[$orderPropsId] : false;
	}


	/**
	 * Устанавливает в заказ $orderId свойства $arProps
	 *
	 * @param int   $orderId - ID  заказа
	 * @param array $arProps - - массив в ключе код свойства, в значении его значения
	 *
	 * @return boolean
	 */
	public static function setOrderProps($orderId, $arProps)
	{
		if (
			($orderId = (int)$orderId) <= 0
			|| !is_array($arProps)
			|| empty($arProps)
		) {
			return false;
		}

		$ret = false;

		\CModule::IncludeModule('sale');

		$arOrderProps = self::getOrderProps($orderId, false, true);
		foreach ($arProps as $propCode => $propValue) {
			if ($arOrderProps[$propCode]) {
				\CSaleOrderPropsValue::Update($arOrderProps[$propCode]['ID'], array('VALUE' => $propValue));
				$ret = true;
				continue;
			}

			$orderPropsDir = self::getSaleOrderPropsDir();

			if (array_key_exists($propCode, $orderPropsDir)) {
				\CSaleOrderPropsValue::Add(array(
					"ORDER_ID" => $orderId,
					"ORDER_PROPS_ID" => $orderPropsDir[$propCode]["ID"],
					"NAME" => $orderPropsDir[$propCode]["NAME"],
					"CODE" => $orderPropsDir[$propCode]["CODE"],
					"VALUE" => $propValue
				));
				$ret = true;
			}
		}

		return $ret;
	}

	/**
	 * Получить корзину текущего пользователя
	 *
	 * @param    array      $arSelect - какие поля выбирать
	 * @param    bool       $needBasketProps = false - выбирать ли свойства корзины
	 * @param    string|int $orderId = 'NULL' можно выбрать строки конкретного заказа
	 *
	 * @return array
	 */
	public static function getCurrentBasketItems($arSelect = array(), $needBasketProps = false, $orderId = 'NULL')
	{
		\CModule::IncludeModule('sale');

		$arBasketItems = array();

		$arSel = array(
			"ID", "NAME",
			"PRODUCT_ID", "QUANTITY",
			"PRICE", "CURRENCY",
			"CAN_BUY"
		);

		if (is_array($arSelect) && !empty($arSelect)) {
			$arSel = array_merge($arSel, $arSelect);
		}

		if ((int)$orderId > 0 && $orderId != 'NULL') {
			$arF = array(
				"ORDER_ID" => (int)$orderId,
			);
		} else {
			$arF = array(
				"FUSER_ID" => \CSaleBasket::GetBasketUserID(),
				"ORDER_ID" => $orderId
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
			$arBasketItems[$arItems['ID']] = $arItems;
		}

		if (!empty($arBasketItems) && $needBasketProps) {
			$res = \CSaleBasket::GetPropsList(
				array("ID" => "ASC"),
				array("BASKET_ID" => array_keys($arBasketItems))
			);
			$i = 0;
			while ($ar = $res->Fetch()) {
				$k = (trim($ar['CODE']) != '') ? $ar['CODE'] : $i++;
				$arBasketItems[$ar['BASKET_ID']]['PROPS'][$k] = $ar;
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

		if (!isset($_cache)) {
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
				$_cache[$arOrderProperties['CODE']] = $arOrderProperties;
			}
		}

		return $_cache;
	}

	/**
	 * @param array $orderProps свойства заказа, которые надо проставить (символьный код свойства 1 => значение 1, ..., также обрабатываются USER_DESCRIPTION -
	 *                          комментарий пользователя, MANAGER_DESCRIPTION - комментарий администратора
	 * @param array $goodIds массив, в ключах ID товаров, а в значении
	 * @param null $goodsListCallback кел-бек функция, позволяет менять товары перед сохранением
	 * <code>
	 *      function ($basket) {
	 *          $basketItems = $basket->getBasketItems();
	 *			foreach ($basketItems as $basketItem) {
	 *			}
	 *          return $basket;
	 *      }
	 * </code>
	 * @param null $currencyCode = если null - берется с опции 'sale::default_currency'
	 * @param int  $personTypeId = 1 обычно обычный физик, но завел на всякий
	 *
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\NotSupportedException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function addOrderD7($orderProps, $goodIds, $goodsListCallback = null, $currencyCode = null, $personTypeId = 1)
	{
		if (!Loader::IncludeModule('sale')) {
			throw new \Exception('Module sale not installed');
		}

		if ($currencyCode === null) {
			$currencyCode = \COption::GetOptionString('sale', 'default_currency', 'RUB');
		}

		DiscountCouponsManager::init();

		$siteId = \Bitrix\Main\Context::getCurrent()->getSite();
		$order = Order::create($siteId, \CSaleUser::GetAnonymousUserID());

		$order->setPersonTypeId($personTypeId);

		/* basket */
		$basket = Sale\Basket::create($siteId);
		$order->setBasket($basket);

		if (is_callable($goodsListCallback)) {
			$basket = $goodsListCallback($basket);
		}

		foreach ($goodIds as $count => $good) {
			self::updateBasketd7($basket, $good['ID'], $count);
		}

		/*Shipment*/
		$shipmentCollection = $order->getShipmentCollection();
		$shipment = $shipmentCollection->createItem();
		$shipmentItemCollection = $shipment->getShipmentItemCollection();
		$shipment->setField('CURRENCY', $order->getCurrency());
		foreach ($order->getBasket() as $item) {
			$shipmentItem = $shipmentItemCollection->createItem($item);
			$shipmentItem->setQuantity($item->getQuantity());
		}
		$arDeliveryServiceAll = Delivery\Services\Manager::getRestrictedObjectsList($shipment);
		$shipmentCollection = $shipment->getCollection();

		if (!empty($arDeliveryServiceAll)) {
			reset($arDeliveryServiceAll);
			$deliveryObj = current($arDeliveryServiceAll);

			if ($deliveryObj->isProfile()) {
				$name = $deliveryObj->getNameWithParent();
			} else {
				$name = $deliveryObj->getName();
			}

			$shipment->setFields(array(
				'DELIVERY_ID' => $deliveryObj->getId(),
				'DELIVERY_NAME' => $name,
				'CURRENCY' => $order->getCurrency(),
			));

			$shipmentCollection->calculateDelivery();
		}
		/**/

		/*Payment*/
		$arPaySystemServiceAll = [];
		$paySystemId = 1;
		$paymentCollection = $order->getPaymentCollection();

		$remainingSum = $order->getPrice() - $paymentCollection->getSum();
		if ($remainingSum > 0 || $order->getPrice() == 0) {
			$extPayment = $paymentCollection->createItem();
			$extPayment->setField('SUM', $remainingSum);
			$arPaySystemServices = PaySystem\Manager::getListWithRestrictions($extPayment);

			$arPaySystemServiceAll += $arPaySystemServices;

			if (array_key_exists($paySystemId, $arPaySystemServiceAll)) {
				$arPaySystem = $arPaySystemServiceAll[$paySystemId];
			} else {
				reset($arPaySystemServiceAll);

				$arPaySystem = current($arPaySystemServiceAll);
			}

			if (!empty($arPaySystem)) {
				$extPayment->setFields(array(
					'PAY_SYSTEM_ID' => $arPaySystem["ID"],
					'PAY_SYSTEM_NAME' => $arPaySystem["NAME"]
				));
			} else {
				$extPayment->delete();
			}
		}
		/**/

		$propertyCollection = $order->getPropertyCollection();
		foreach ($orderProps as $propCode => $value) {
			$property = self::getPropertyByCode($propertyCollection, $propCode);
			/** @var Bitrix\Sale\PropertyValue $property* /
			if (is_callable($property, 'setValue')) {
			$property->setValue($value);
			}*/
		}

		$order->setField('CURRENCY',                $currencyCode);
		$order->setField('USER_DESCRIPTION',        $orderProps['USER_DESCRIPTION']);
		$order->setField('COMMENTS',                $orderProps['MANAGER_DESCRIPTION']);

		$order->doFinalAction(true);

		$order->save();

		$orderId = $order->GetId();

		/*if ($orderId) {
			self::setOrderProps($orderId, $propCode);
		}*/

		return $orderId;
	}

	/**
	 * @param   $propertyCollection
	 * @param $code
	 *
	 * @return \Bitrix\Sale\PropertyValueCollection
	 */
	static function getPropertyByCode($propertyCollection, $code)  {
		foreach ($propertyCollection as $property) {
			if ($property->getField('CODE') == $code) {
				return $property;
			}
		}
		return false;
	}

	/**
	 * @param \Bitrix\Sale\BasketBase $basket
	 * @param int                     $productId ID товара
	 * @param int                     $quantity количество
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	static function updateBasketd7($basket, $productId, $quantity)
	{
		if ($item = $basket->getExistsItem('catalog', $productId)) {
			$item->setField('QUANTITY', $item->getQuantity() + $quantity);
		} else {
			$item = $basket->createItem('catalog', $productId);
			$item->setFields(array(
				'QUANTITY' => $quantity,
				'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
				'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
				'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
			));
			/*
			Если вы хотите добавить товар с произвольной ценой, нужно сделать так:
			$item->setFields(array(
				'QUANTITY' => $quantity,
				'CURRENCY' => Bitrix\Currency\CurrencyManager::getBaseCurrency(),
				'LID' => Bitrix\Main\Context::getCurrent()->getSite(),
				'PRICE' => $customPrice,
				'CUSTOM_PRICE' => 'Y',
		   ));
		   */
		}
		$basket->save();
	}
} // \\ end class
