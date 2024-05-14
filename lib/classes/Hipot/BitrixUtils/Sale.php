<?php

namespace Hipot\BitrixUtils;

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Sale as bxSale;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketBase;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Discount;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Internals\DiscountTable;
use Bitrix\Sale\Order;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ReferenceField;

Loader::includeModule('sale');

final class Sale
{
	/**
	 * Retrieves the basket for the current front-end user.
	 *
	 * @return BasketBase|null The basket for the front-end user, or null if no basket is found.
	 */
	public static function getFUserBasket(): ?BasketBase
	{
		$basket = Basket::loadItemsForFUser(
			Fuser::getId(),
			Context::getCurrent()->getSite()
		);
		return $basket;
	}

	/**
	 * Retrieves the orderable items from the FUser basket.
	 *
	 * @return BasketBase The orderable items from the FUser basket.
	 */
	public static function getFUserBasketItems(): BasketBase
	{
		$basket = self::getFUserBasket();
		return $basket->getOrderableItems();
	}

	public static function getBasketItemProperties(BasketItem $basketItem, array $ignoreItemsCode = ['CATALOG.XML_ID', 'PRODUCT.XML_ID', 'SUM_OF_CHARGE']): array
	{
		$properties = [];
		/** @var bxSale\BasketPropertiesCollection $propertyCollection */
		$propertyCollection = $basketItem->getPropertyCollection();
		$basketId           = $basketItem->getBasketCode();

		foreach ($propertyCollection->getPropertyValues() as $property) {
			if (in_array($property['CODE'], $ignoreItemsCode, false)) {
				continue;
			}

			$property              = array_filter($property, [\CSaleBasketHelper::class, 'filterFields']);
			$property['BASKET_ID'] = $basketId;
			$properties[] = $property;
		}
		return $properties;
	}

	/**
	 * Checks if an order exists.
	 *
	 * @param int|string $orderId The ID of the order to check.
	 *
	 * @return bool Returns true if the order exists, false otherwise.
	 */
	public static function isOrderExists(int|string $orderId): bool
	{
		$result = false;
		if ($orderId) {
			if ($order = Order::load($orderId)) {
				$result = true;
			}
		}
		return $result;
	}

	/**
	 * Retrieves the store ID associated with the given order.
	 *
	 * @param Order $order The order for which to retrieve the store ID.
	 * @return int The store ID associated with the order. Returns -1 if no store ID is found.
	 */
	public static function getOrderStoreId(Order $order): int
	{
		$storeId = -1;
		$shipmentCollection = $order->getShipmentCollection();
		foreach ($shipmentCollection as $shipment) {
			/** @var \Bitrix\Sale\Shipment $shipment */
			$storeId = $shipment->getStoreId();
			if ($storeId > 0) {
				break;
			}
		}
		return $storeId;
	}

	/**
	 * Applies order discounts to a given order.
	 *
	 * @param Order|null $order The order to apply discounts to.
	 * @param array|null $result A reference to the variable where the result of applying discounts will be stored.
	 *
	 * @return array An array of error messages, if any.
	 */
	public static function applyOrderDiscounts(?Order $order = null, array &$result = null): array
	{
		$errors = [];

		if (is_null($order)) {
			return $errors;
		}

		$discounts = $order->getDiscount();
		$discounts->setOrderRefresh(true);
		$r = $discounts->calculate();

		if ($r->isSuccess()) {
			$result = $discounts->getApplyResult(true);
		} else {
			$errors[] = $r->getErrorMessages();
		}

		$order->doFinalAction(true);

		return $errors;
	}

	/**
	 * Applies basket discounts to a given basket.
	 *
	 * @param Basket|null $basket The basket to apply discounts to.
	 * @param array|null  $result A reference to the variable where the result of applying discounts will be stored.
	 *
	 * @return array An array of error messages, if any.
	 */
	public static function applyBasketDiscounts(?Basket $basket, array &$result = null): array
	{
		$errors = [];

		if (is_null($basket)) {
			return $errors;
		}

		if ($basket->getOrder() !== null) {
			$discounts = Discount::buildFromOrder($basket->getOrder());
		} else {
			$context = new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId());
			$discounts = Discount::buildFromBasket($basket, $context);
		}
		$r = $discounts->calculate();

		if ($r->isSuccess()) {
			$result = $discounts->getApplyResult(true);
		} else {
			$errors[] = $r->getErrorMessages();
		}

		return $errors;
	}

	/**
	 * Получить время изменения акций модуля sale
	 */
	public static function getSaleDiscountModTimestamp(): int
	{
		$getListParams = [
			'select' => ['TIMESTAMP_X'],
			'filter' => [],
			'order' => ['TIMESTAMP_X' => 'DESC']
		];
		if (Option::get('sale', 'use_sale_discount_only', false) === 'Y' && Loader::includeModule('catalog')) {
			$getListParams['runtime'] = [
				new ReferenceField(
					"CATALOG_DISCOUNT",
					'Bitrix\Catalog\DiscountTable',
					["=this.ID" => "ref.SALE_ID"]
				)
			];
			$getListParams['select']['CATALOG_DISCOUNT_ID'] = 'CATALOG_DISCOUNT.ID';
		}
		$getListParams['limit'] = 1;

		$discount   = DiscountTable::getList($getListParams)->fetch();
		$discountTs = $discount['TIMESTAMP_X'];
		/* @var $discountTs \Bitrix\Main\Type\DateTime */
		return $discountTs->format('U');
	}

	/**
	 * @param \Bitrix\Sale\Order $order
	 *
	 * @return array{\Bitrix\Sale\PaySystem\Service, \Bitrix\Sale\Payment}
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function getPaySystemServiceByOrder(Order $order): array
	{
		$paymentCollection = $order->getPaymentCollection();
		/** @var $payment \Bitrix\Sale\Payment */
		$payment = $paymentCollection->getIterator()->current();

		$paySystemAction = \Bitrix\Sale\PaySystem\Manager::getList([
			'filter' => ['PAY_SYSTEM_ID' => $payment->getPaymentSystemId()],
			'select' => ['*'],
		])->fetch();
		$service         = new \Bitrix\Sale\PaySystem\Service($paySystemAction);
		return [$service, $payment];
	}
}