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
 * @version 1.86
 * @author hipot studio
 */
class SaleUtils
{
	/**
	 * должен быть создан в базе
	 */
	const ANONIM_ORDER_USER_EMAIL = 'anonimus@supersite.ru';

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
	public static function getCurrentBasketItems($arSelect = [], $needBasketProps = false, $orderId = 'NULL')
	{
		\CModule::IncludeModule('sale');

		$arBasketItems = [];

		$arSel = [
			"ID", "NAME",
			"PRODUCT_ID", "QUANTITY",
			"PRICE", "CURRENCY",
			"CAN_BUY"
		];

		if (is_array($arSelect) && !empty($arSelect)) {
			$arSel = array_merge($arSel, $arSelect);
		}

		if ((int)$orderId > 0 && $orderId != 'NULL') {
			$arF = [
				"ORDER_ID" => (int)$orderId,
			];
		} else {
			$arF = [
				"FUSER_ID" => \CSaleBasket::GetBasketUserID(),
				"ORDER_ID" => $orderId
			];
		}

		$dbBasketItems = \CSaleBasket::GetList(
			["ID" => "ASC"],
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
				["ID" => "ASC"],
				["BASKET_ID" => array_keys($arBasketItems)]
			);
			$i = 0;
			while ($ar = $res->Fetch()) {
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

		if (!isset($_cache)) {
			$_cache = [];

			\CModule::IncludeModule('sale');
			$dbOrderProperties = \CSaleOrderProps::GetList(
				array("ID" => "ASC"),
				[],
				false,
				false,
				[]
			);
			while ($arOrderProperties = $dbOrderProperties->Fetch()) {
				$_cache[$arOrderProperties['CODE']] = $arOrderProperties;
			}
		}

		return $_cache;
	}

	/**
	 * Обновление цены у товара
	 *
	 * @param int $PRODUCT_ID код товара
	 * @param int $PRICE_TYPE_ID код цены товара
	 * @param float $PRICE цена
	 * @param string $CURRENCY = 'RUB' валюта цены
	 * @param array $additionFields можно переопределить поля перед добалением/обновлением корзины
	 *
	 * @return bool успешно или нет обновлена цена $PRICE_TYPE_ID у товара $PRODUCT_ID
	 */
	public static function updatePrice($PRODUCT_ID, $PRICE_TYPE_ID, $PRICE, $CURRENCY = 'RUB', $additionFields = [])
	{
		global $APPLICATION;

		$arFields = [
			"PRODUCT_ID" => (int)$PRODUCT_ID,
			"CATALOG_GROUP_ID" => (int)$PRICE_TYPE_ID,
			"PRICE" => (float)$PRICE,
			"CURRENCY" => $CURRENCY
		];

		foreach ($additionFields as $k => $v) {
			$arFields[$k] = $v;
		}

		$res = \CPrice::GetList([], [
			"PRODUCT_ID" => $PRODUCT_ID,
			"CATALOG_GROUP_ID" => $PRICE_TYPE_ID
		]);
		if ($arr = $res->Fetch()) {
			if ($PRICE == 0) {
				\CPrice::Delete($arr["ID"]);
			} else {
				if (! \CPrice::Update($arr["ID"], $arFields)) {
					echo('Error update price ' . $APPLICATION->GetException()->GetString());
					return false;
				}
			}
		} else {
			\CPrice::Add($arFields);
		}
		return true;
	}

	/**
	 * Добавление заказа по-старинке со всеми параметрами (корзина, свойства заказа и т.д.)
	 *
	 * @param      $orderProps
	 * @param      $goodIds
	 * @param null $goodsListCallback
	 * @param int  $personTypeId
	 * @param null $paySystemId
	 * @param null $deliveryId
	 * @param null $currencyCode
	 *
	 * @return int
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function addOrder($orderProps, $goodIds, $goodsListCallback = null, $personTypeId = 1, $paySystemId = null, $deliveryId = null, $currencyCode = null)
	{
		global $USER;

		if (!Loader::IncludeModule('sale')) {
			throw new \Exception('Module sale not installed');
		}

		if ($currencyCode === null) {
			$currencyCode = \COption::GetOptionString('sale', 'default_currency', 'RUB');
		}

		DiscountCouponsManager::init();

		$siteId = \Bitrix\Main\Context::getCurrent()->getSite();
		$deliveryPrice = false;

		$anonimUser = $USER->GetByLogin( self::ANONIM_ORDER_USER_EMAIL )->Fetch();

		$arOrderAdd = [
			'LID'               =>  $siteId,
			'PERSON_TYPE_ID'    => $personTypeId,
			'PAYED'             => 'N',
			'DATE_PAYED'        => false,
			'EMP_PAYED_ID'      => false,
			'CANCELED'          => 'N',
			'DATE_CANCELED'     => false,
			'EMP_CANCELED_ID'   => false,
			'REASON_CANCELED'   => '',
			'STATUS_ID' => 'N',
			'EMP_STATUS_ID' => false,
			'PRICE_DELIVERY' => $deliveryPrice,
			'ALLOW_DELIVERY' => 'N',
			'DATE_ALLOW_DELIVERY'  => false,
			'EMP_ALLOW_DELIVERY_ID' => false,
			'PRICE' => $orderProps['PRICE'],
			'CURRENCY'  => $currencyCode,
			'DISCOUNT_VALUE' => false,
			'USER_ID'   => (int)$orderProps['USER_ID'] <= 0 ? $anonimUser['ID'] : $orderProps['USER_ID'],
			'PAY_SYSTEM_ID' => $paySystemId,
			'DELIVERY_ID' =>  $deliveryId,
			'USER_DESCRIPTION' - $orderProps['USER_DESCRIPTION'],
			'ADDITIONAL_INFO' => '',
			'COMMENTS' => '',
			'TAX_VALUE' => '',
			'AFFILIATE_ID' => false,
			'PS_STATUS' => 'N',
		];
		foreach ($orderProps as $prop => $propV) {
			if (trim($propV) == '' || in_array($prop, ['USER_ID'])) {
				continue;
			}
			$arOrderAdd[ $prop ] = $propV;
		}

		$ORDER_ID = \CSaleOrder::Add($arOrderAdd);
		if ($ORDER_ID) {
			self::setOrderProps($ORDER_ID, $orderProps);
			foreach ($goodIds as $count => $good) {

				$arBasketAdd = [
					'FUSER_ID' => \CSaleUser::GetAnonymousUserID(),
					'ORDER_ID' => $ORDER_ID,
					'QUANTITY' => $count,
					'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
					'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
					'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
					'PRODUCT_ID' => $good['ID'],
					"MODULE" => "catalog",
					'PRICE' => $good['PRICE'],
					'NAME' => $good['NAME'],
					'DETAIL_PAGE_URL' => $good['DETAIL_PAGE_URL'],
					'CUSTOM_PRICE' => 'Y'
				];
				if (is_callable($goodsListCallback)) {
					$arBasketAdd = $goodsListCallback($arBasketAdd);
				}
				\CSaleBasket::Add($arBasketAdd);
			}
		}

		return $ORDER_ID;
	}

	/**
	 * Получить полный адрес по местоположению, если он есть в базе
	 * @param int $CITY
	 * @return string
	 */
	public static function GetFullLocationNameById($CITY)
	{
		$pr['CITY']['VALUE_FULL'] = '';
		$cityLocation = \CSaleLocation::GetByID((int)$CITY);
		$pr['CITY']['VALUE_FULL'] = $cityLocation['COUNTRY_NAME_LANG'] . ' ' . $cityLocation['REGION_NAME_LANG'] . ' ' . $cityLocation['CITY_NAME_LANG'];
		unset($cityLocation);
		return $pr['CITY']['VALUE_FULL'];
	}



	/************************** d7 test cases, wont work **************************/


	/**
	 * TODO
	 *
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
	public static function addOrderD7($orderProps, $goodIds, $goodsListCallback = null, $personTypeId = 1, $paySystemId = null, $deliveryId = null, $currencyCode = null)
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
		$shipment->setField('DELIVERY_ID', $deliveryId);
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
				'DELIVERY_ID' => $deliveryId, // $deliveryObj->getId(),
				'DELIVERY_NAME' => $name,
				'CURRENCY' => $order->getCurrency(),
			));

			$shipmentCollection->calculateDelivery();
		}
		/**/



		/*Payment*/
		$arPaySystemServiceAll = [];
		$paySystemId = (int)$paySystemId;
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
			$propCode = str_replace([']', 'PROPS['], '', $propCode);
			$property = self::getPropertyByCode($propertyCollection, $propCode);
			/** @var Bitrix\Sale\PropertyValue $property */
			if (is_callable($property, 'setValue')) {
				$property->offsetSet($property->offsetGet($propCode), $propCode);
			}
		}
		$order->doFinalAction(true);
		$order->save();

		$orderId = $order->GetId();

		if ($orderId) {
			\CSaleOrder::Update($orderId, [
				'USER_ID'               => $orderProps['USER_ID'],
				'USER_DESCRIPTION'      => $orderProps['USER_DESCRIPTION'],
				'COMMENTS'              => $orderProps['MANAGER_DESCRIPTION'],
				'CURRENCY'              => $currencyCode
			]);
		}

		return $orderId;
	}

	/**
	 * @param   $propertyCollection
	 * @param $code
	 *
	 * @return \Bitrix\Sale\PropertyValueCollection
	 */
	static function getPropertyByCode($propertyCollection, $code)
	{
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
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public static function updateBasketD7($basket, $productId, $quantity)
	{
		if ($item = $basket->getExistsItem('catalog', $productId)) {
			$item->setField('QUANTITY', $item->getQuantity() + $quantity);
		} else {
			$item = $basket->createItem('catalog', $productId);
			$item->setFields([
				'QUANTITY' => $quantity,
				'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
				'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
				'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
				'PRODUCT_ID' => $productId
			]);
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

	/**
	 * Получить время изменения акций модуля sale
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @return int timestamp
	 */
	public static function getSaleDiscountModTs()
	{
		$getListParams = [
			'select' => ['TIMESTAMP_X'],
			'filter' => [],
			'order' => ['TIMESTAMP_X' => 'DESC']
		];
		if (\Bitrix\Main\Config\Option::get('sale', 'use_sale_discount_only', false) === 'Y'
			&& \Bitrix\Main\Loader::includeModule('catalog')) {
			$getListParams['runtime'] = [
				new \Bitrix\Main\Entity\ReferenceField(
					"CATALOG_DISCOUNT",
					'Bitrix\Catalog\DiscountTable',
					["=this.ID" => "ref.SALE_ID"]
				)
			];
			$getListParams['select']['CATALOG_DISCOUNT_ID'] = 'CATALOG_DISCOUNT.ID';
		}
		$getListParams['limit'] = 1;

		$discount = \Bitrix\Sale\Internals\DiscountTable::getList($getListParams)->fetch();
		$discountTs = $discount['TIMESTAMP_X'];
		/* @var $discountTs \Bitrix\Main\Type\DateTime */
		return $discountTs->format('U');
	}

} // end class
