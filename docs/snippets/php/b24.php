<?php
/**
 * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=42&LESSON_ID=12834
 */

/*
Такая ситуация возникает если стандартные типы плательщиков в таблицах "b_sale_person_type" и "b_sale_person_type_site" имели некорректные привязки.
Были привязаны к сайту интернет-магазина, а не к сайту с CRM.
Можно вручную попробовать поправить (в самих таблицах) или кодом*/
CModule::IncludeModule('crm');
CCrmSaleHelper::divideInvoiceOrderPersonTypeAgent();

// tables b_sale_person_type_site AND b_sale_person_type change SITE_ID to b24-site
// sql: UPDATE b_sale_person_type_site SET SiTE_ID = 'b1' WHERE PERSON_TYPE_ID IN (3,4)
?>


<?php
/**
 * Как изменить каталог в CRM и сделках?
 * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=42&LESSON_ID=20556&LESSON_PATH=3912.12836.12914.20556
 */
COption::SetOptionString('crm', 'default_product_catalog_id', 26);
?>

<?php
/* отладка интеграции им с порталом
см
https://i-fun.ru/bitrix/admin/sale_crm.php?lang=ru
https://b24.i-fun.ru/crm/configs/external_sale/ */

// dbcon
define("LOG_FILENAME", $_SERVER['DOCUMENT_ROOT'] . '/__bx_log.txt');

// php code
\Bitrix\Main\Config\Option::get('crm', 'enable_sale_import_trace', 'Y');

// see log file
// need catalog_type
echo COption::GetOptionString('crm', 'product_catalog_type_id', '');

// iblock XML_ID need: crm_external_4

?>

<?php
/*cat /etc/push-server/push-server-sub-8010.json
находим параметр "key" и его значение записываем в настройки модуля push&pull*/
?>

<?php
use Bitrix\Sale\BasketBase;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\OrderBase;
use Bitrix\Sale\Registry;

// Получаем объект, заведующий реестрами
$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);

/**
 * @var $orderClass OrderBase
 * @var $basketClass BasketBase
 */

// Получаем класс работы с заказами
$orderClass = $registry->getOrderClassName();
// Получаем класс работы с корзинами
$basketClass = $registry->getBasketClassName();

// Читаем заказ
$order = $orderClass::load($orderId);
// Читаем корзину из объекта заказа
$basket = $order->getBasket();

// Читаем корзину через класс по объекту заказа
$basket = $basketClass::loadItemsForOrder($order);

// Читаем корзину для текущего покупателя
$currentFUser = Fuser::getId();
$basket = $basketClass::loadItemsForFUser($currentFUser, SITE_ID);

/**
 * @var \Bitrix\Sale\BasketItem $bi
 */

// Добываем данные по полям товаров в корзине
foreach ($basket->getBasketItems() as $bi) {
	$basketProductFields = $bi->getFieldValues();
}

// Переопределение класса-обработчика
// В методе \Bitrix\Sale\Registry::initRegistry результат обработчика будет с-array_merge-н поверх стандартного списка:

use Bitrix\Main\EventManager;

(EventManager::getInstance())
	->addEventHandler(
		'main',
		'OnInitRegistryList',
		[
			'\AlexeyGfi\BasketHelper',
			'registerCustomInstances'
		]
	);
?>

<?
// открыть обычную админку заказов для всех (убрать предупреждение)
if (\Bitrix\Sale\Update\CrmEntityCreatorStepper::isNeedStub()) {
	$APPLICATION->IncludeComponent("bitrix:sale.admin.page.stub", ".default");
}
// see ~IS_SALE_CRM_SITE_MASTER_STUB
COption::SetOptionString('sale', \Bitrix\Sale\Update\CrmEntityCreatorStepper::IS_SALE_CRM_SITE_MASTER_STUB, 'N');
?>