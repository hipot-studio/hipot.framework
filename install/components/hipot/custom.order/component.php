<?
/**
 * Уникальный компонент корзины
 * работает в двух режимах:
 * быстрый заказ сохраняется в веб-форму
 * а большой - в инфоблок и через событие в заказ
 * @version 2.2
 * @author hipot studio
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var $this \CBitrixComponent */
/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 */

/*ini_set('display_errors' ,1);
error_reporting(E_ERROR);*/

use Hipot\BitrixUtils\PhpCacher as hiPhpCacher;

global $USER;

$arResult = [];
$arParams['IBLOCK_ID'] = 34;

\CModule::IncludeModule('iblock');
\CModule::IncludeModule('sale');


// post array $arResultRequest
$postKeyForm = ($_REQUEST['fast_form'] == 'Y') ? 'PROPS_fast' : 'PROPS';
if (trim($arParams['postKeyForm']) != '') {
	$postKeyForm = $arParams['postKeyForm'];
}
$arResultRequest = $_REQUEST[$postKeyForm];

$basket = [];
$rsBasketItems = \CSaleBasket::GetList(["NAME" => "ASC", "ID" => "ASC"], ["FUSER_ID" => \CSaleBasket::GetBasketUserID(), "LID" => SITE_ID, "ORDER_ID" => "NULL"],
	false, false, ['*']
);
while ($arBasketItem = $rsBasketItems->Fetch()) {
	$basket[] = $arBasketItem;
}
$arResult['BASKET'] = $basket;

//
// ajax here
//
if ($arParams['AJAX'] == 'Y' && count($_REQUEST) > 0) {
	$arResult['ERROR'] = '';
	$arResult['ANSWER'] = '';

	$arResult['USER_ID'] = (int)$USER->GetID();

	if (is_array($basket) && !empty($basket)) {
		$fullPrice = 0;
		foreach ($basket as $basketItem) {
			// this string parsed, not change!
			$basketItemStr = '[' . $basketItem['PRODUCT_ID'] . '] ' . $basketItem['NAME'] . ' ' . $basketItem['QUANTITY'] . ' шт. ' . ($basketItem['QUANTITY'] * $basketItem['PRICE']) . ' ' . $basketItem['CURRENCY'];
			$fullPrice += $basketItem['QUANTITY'] * $basketItem['PRICE'];
			$currency = $basketItem['CURRENCY'];
			$arResult['BASKET_ITEMS'][] = $basketItemStr;
		}

		$arResult['FULL_PRICE'] = $fullPrice;
		$arResult['CURRENCY'] = $currency;

		$el = new \CIBlockElement();
		$props = array();

		$props['FIO'] = $arResultRequest['FIO'];
		$props['PHONE'] = $arResultRequest['PHONE'];
		$props['EMAIL'] = $arResultRequest['EMAIL'];
		$props['CITY'] = $_SESSION['TF_LOCATION_SELECTED_CITY'] ?? $arResultRequest['CITY'];
		$props['ADDRESS'] = $arResultRequest['ADDRESS'];
		$props['COMMENT'] = $arResultRequest['USER_DESCRIPTION'];
		$props['USER_ID'] = $arResult['USER_ID'];
		$props['FULL_PRICE'] = $arResult['FULL_PRICE'];
		$props['AGREE_STATUS_SMS'] = $arResultRequest['ORDER_STATUS_SMS'] == "Y" ? 116 : false;
		$props['DELIVERY'] = $arResultRequest['DELIVERY'];
		$props['PAY']      = $arResultRequest['PAY'];
		$props['COMMENT']  = $arResultRequest['USER_DESCRIPTION'];
		// other postst
		foreach ($arResultRequest as $prop => $propValue) {
			$props[$prop] = $propValue;
		}

		foreach ($arResult['BASKET_ITEMS'] as $basketItem) {
			$props['GOODS'][] = array('VALUE' => $basketItem);
		}

		if ($postKeyForm == 'PROPS_fast') {
			// add to web form
			$r = \Hipot\Utils\UUtils::formResultAddSimple(3, [
				'form_text_18' => $props['FIO'],                                                // Имя
				'form_text_19' => $props['PHONE'],                                              // Телефон
				'form_text_21' => $props['EMAIL'],                                              // email
				'form_textarea_20' => implode("\n", $arResult['BASKET_ITEMS']),            // Корзина (многотекстом)
				'form_text_22'  => ($props['ORDER_STATUS_SMS'] > 0) ? 'Да' : '',                // Готовы ли получать SMS
				'form_text_23' => $props['FIO']                                                 // FIO_HIDDEN
			]);
			$orderId = $GLOBALS['last_order_id'] = (int)$r->RESULT;
			if (! $orderId) {
				$arResult['ERROR'] = 'Ошибка добавления быстрого заказа ' . $r->STATUS;
			}
		} else {

			require __DIR__ . '/order_handler.php';

			$arLoadArray = Array(
				"IBLOCK_SECTION_ID"         => false,
				"IBLOCK_ID"                 => $arParams['IBLOCK_ID'],
				"PROPERTY_VALUES"           => $props,
				"NAME"                      => $props['FIO'],
				"ACTIVE"                    => "Y",
			);
			// this element use handler to add order
			if ($orderId = $el->Add($arLoadArray, false, false)) {
				//\CIBlockElement::SetPropertyValuesEx($orderOriId, $arParams['IBLOCK_ID'], $props);
			} else {
				$arResult['ERROR'] = 'Ваш заказ не сохранился (мы исправляем ошибку, попробуйте позже): ' . $el->LAST_ERROR;
			}
		}
	} else if (empty($arResult['BASKET']) && !$orderId) {

		$arResult['ERROR'] = 'Ваша корзина пуста!';

	}

	// mails
	if (!empty($arResult['BASKET']) || $orderId) {
		if ($GLOBALS['last_order_id']) {
			$orderOriId = $orderId;
			$orderId = $GLOBALS['last_order_id'];
		}

		$arResult['ANSWER'] = 'Спасибо, Ваш заказ #' . $orderId . ' принят! Наши менеджеры в ближайшее время свяжутся с Вами.';
		$arResult['ORDER_ID'] = $orderId;

		$arEventFields = array();
		$arEventFields['ORDER_ID'] = $orderId;
		$arEventFields['ORDER_REAL_ID'] = $orderId;
		$arEventFields['ORDER_ACCOUNT_NUMBER_ENCODE'] = $orderId;
		$arEventFields['ORDER_DATE'] = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time());
		$arEventFields['ORDER_USER'] = $arResultRequest['FIO'];
		$arEventFields['PRICE'] = $arResultRequest['FULL_PRICE'] . ' ' . $arResultRequest['CURRENCY'];
		$arEventFields['EMAIL'] = trim($arResultRequest['EMAIL']);
		$arEventFields['BCC'] = 'info@wellmood.ru';
		$arEventFields['SALE_EMAIL'] = 'info@wellmood.ru';
		$arEventFields['ORDER_LIST'] = '';
		foreach ($arResult['BASKET_ITEMS'] as $basketItem) {
			$arEventFields['ORDER_LIST'] .= $basketItem . '<br>';
		}
		$arEventFields['DELIVERY_PRICE'] = '';

		// main good url
		foreach ($basket as &$bi) {
			\CSaleBasket::Update($bi['ID'], [
				'DETAIL_PAGE_URL' => $parentGood[ $bi['PRODUCT_ID'] ]['DETAIL_PAGE_URL']
			]);
		}
		unset($bi);

		\CEvent::Send("SALE_NEW_ORDER", 's1', $arEventFields);
		//\CSaleBasket::DeleteAll( \CSaleBasket::GetBasketUserID() );
		\CSaleBasket::OrderBasket($orderId, \CSaleBasket::GetBasketUserID());

		$APPLICATION->RestartBuffer();

		//echo json_encode($arResult, JSON_HEX_TAG);
		//echo \Bitrix\Main\Web\Json::encode($arResult);
		echo \CUtil::PhpToJSObject($arResult);
		exit;
	}
} else {


	//
	// выборки для первичного заказа
	//


	// cache 1
	$arResult["LOCATIONS"] = \Hipot\BitrixUtils\PhpCacher::returnCacheDataAndSave('order_v2_location_new_order', 3600 * 3 * 12, function () {
		$arResult["LOCATIONS"] = [];
		$locations = \Bitrix\Sale\Location\LocationTable::getList(array(
			'filter' => array('=NAME.LANGUAGE_ID' => LANGUAGE_ID, '!CITY_ID' => false),
			'select' => array('*', 'NAME_RU' => 'NAME.NAME', 'TYPE_CODE' => 'TYPE.CODE'),
			'order' => ['NAME_RU' => 'ASC']
		));
		while ($loc = $locations->fetch()) {
			$arResult["LOCATIONS"][] = $loc;
		}
		return $arResult["LOCATIONS"];
	});


	$arResult['PROPS']['DELIVERY'] = hiPhpCacher::returnCacheDataAndSave('order_v2_sale_delivery_list', false, function () use ($arResultRequest) {
		$dbRes = \Bitrix\Sale\Delivery\Services\Table::getList([
			'filter' => ['ACTIVE' => 'Y'],
			'select' => ['*'],
			'order' => ['sort'=> 'asc', 'name' => 'asc'],
			'group' => ['DESCRIPTION', 'NAME']
		])->fetchAll();

		$ids        = array_column($dbRes, 'PARENT_ID');
		$ids2       = array_column($dbRes, 'ID');

		$list = [
			'VARIANTS'  => \Bitrix\Sale\Delivery\Services\Manager::getActiveList(false, $ids2),
			'IDS' => $ids
		];
		/*usort($list['VARIANTS'], function ($a, $b) {
			return strcasecmp($a['NAME'], $b['NAME']);
		});*/

		foreach ($list['VARIANTS'] as $kk => &$l) {
			if ($l['PARENT_ID'] != 0) {
				unset($list['VARIANTS'][$kk]);
			}
		}
		unset($l);

		return $list;

		/*$filter = [];

		$filter["=CLASS_NAME"] = '\Bitrix\Sale\Delivery\Services\Group';
		$filter['!=CLASS_NAME'] = array(
			'\Bitrix\Sale\Delivery\Services\Group',
			'\Bitrix\Sale\Delivery\Services\EmptyDeliveryService'
		);

		$handlersList = \Bitrix\Sale\Delivery\Services\Manager::getHandlersList();

		/** @var \Bitrix\Sale\Delivery\Services\Base $handlerClass * /
		foreach($handlersList as $handlerClass) {
			if ($handlerClass::isProfile() && !in_array($handlerClass, $filter['!=CLASS_NAME'])) {
				$filter['!=CLASS_NAME'][] = $handlerClass;
			}
		}

		$siteId = SITE_ID;

		$glParams = array(
			'filter' => $filter,
			'order' => array('ID' => 'ASC')
		);
		$dbResultList = \Bitrix\Sale\Delivery\Services\Table::getList($glParams);
		$serviceS = [];
		while ($service = $dbResultList->fetch()) {
			if (
				strlen($siteId) > 0 && isset($service["SITES"]) &&
				!empty($service["SITES"]['SITE_ID']) && is_array($service["SITES"]['SITE_ID'])
			) {
				if (!in_array($siteId, $service["SITES"]['SITE_ID'])) {
					continue;
				}
			}
			$serviceS[] = $service;
		}
		return $serviceS;*/
	});


	$arResult['PROPS']['PAY'] = hiPhpCacher::returnCacheDataAndSave('order_v2_sale_pay_list', false, function () use ($arResultRequest) {

		$dbRes = \Bitrix\Sale\PaySystem\Manager::getList([
			'filter' => ['=ACTIVE' => 'Y', 'PERSON_TYPE_ID' => 1],
			'select' => ['*'],
			'order' => ['sort'=> 'asc', 'name' => 'asc']
		])->fetchAll();
		$dbRes['VARIANTS'] = $dbRes;

		usort($dbRes['VARIANTS'], function ($a, $b) {
			return strcasecmp($a['NAME'], $b['NAME']);
		});

		return $dbRes;

	});

	$arResult['arUser'] = \CUser::GetByID( (int)$USER->GetID() )->Fetch();


	$this->includeComponentTemplate();
}

?>