<?php
/**
 * @see http://dev.1c-bitrix.ru/community/webdev/user/17890/blog/8910/?commentId=49110#com49110
 *
 * в Битриксе есть метод CIBlockProperty::GetPropertyArray. Это служебный метод, который принимает в качестве параметров
 * идентификаторы информационного блока и свойства и возвращает информацию об указанном свойстве из таблицы b_iblock_property.
 *
 * Характерная особенность этого метода заключается в том, что он вызывается очень часто. Практически любое действие,
 * которое хоть как-то затрагивает свойства инфоблока приводит к вызову этого метода и запросу к базе данных.
 *
 * Для большинства проектов это не является проблемой: у таблицы b_iblock_property есть все необходимые индексы
 * и запросы получаются очень быстрыми. Однако, когда на проекте заведены сотни информационных блоков,
 * а посещаемость измеряется миллионами хитов в сутки, начинает сказываться количество этих запросов.
 * И не просто сказываться — база начинает задыхаться.
 *
 * При этом, в коде метода нетрудно заметить так называемый "виртуальный кеш": полученные данные сохраняются в
 * глобальном массиве $IBLOCK_CACHE_PROPERTY и при следующем вызове метода для того же
 * свойства, данные возвращаются уже не из базы, а из этого глобального массива.
 *
 * Проблема заключается в том, что время жизни "виртуального кеша" не превышает
 * времени жизни скрипта — долей секунды. При этом, обновление записей в
 * таблице b_iblock_property происходит очень-очень редко.
 *
 * Отсюда возникает очевидная задача: требуется продлить время жизни "виртуального кеша", не затрагивая при этом код ядра Битрикс.
 *
 * Решение следующее:
 *  * Разрабатывается класс, реализующий интерфейс ArrayAccess. В классе обеспечивается работа с подходящим
 *  in-memory key-value store (Memcached, Redis или любой другой). В случае, если сервис недоступен, объект ведет себя как обычный массив.
 *
 *  * В php_interface/init.php производится подключение модуля информационных блоков и создается объект CIBlockProperty.
 *  Создание объекта приводит к однократной инициализации глобального массива $IBLOCK_CACHE_PROPERTY. После этого созданный объект можно уничтожить, выполнив unset.
 *
 *  * В php_interface/init.php глобальный массив $IBLOCK_CACHE_PROPERTY переопределяется объектом класса,
 *  созданного на этапе 1. Переопределение производится ниже места, описанного на этапе 2.
 *
 *
 * Задача решена: теперь данные кешируются в быстром key-value store,
 * а при вызове метода GetPropertyArray обращения к базе данных больше не происходит.
 * На реализацию потрачен час времени, добавлено около 50 строк понятного кода, ядро Битрикс осталось нетронутым.
 *
 * К сожалению, я не имею права показывать графики. Могу лишь сказать, что производительность выросла до удивительных значений.
 *
 * Идея http://dev.1c-bitrix.ru/community/webdev/user/23242/
 *
 * @use init.php
 * require __DIR__ . '/include/ib_props_memcache.php';
 * !!! Обязательно перезапускать memcached при изменении инфоблоков в режим хранения свойств 2.0 и обратно
 *
 * TODO need patch file bitrix/modules/iblock/classes/general/iblockproperty.php
 * bitrix is_set function to isset-construction
 *
 * @version 1.5.1
 * @author hipot, 2022
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader,
	Hipot\Utils\MemcacheWrapper,
	Hipot\Param\MemcacheServerConfig,
	Hipot\Utils\MemcacheWrapperError,
	Hipot\Utils\UUtils;

if (!class_exists('Memcache') || !class_exists(MemcacheWrapper::class)) {
	return;
}

Loader::includeModule('iblock');

$pr = new CIBlockProperty();
unset($pr);

try {
	$GLOBALS['IBLOCK_CACHE_PROPERTY'] = new MemcacheWrapper(
		'IBLOCK_CACHE_PROPERTY_',
		MemcacheServerConfig::create(/** $socket = */!defined('PHP_WINDOWS_VERSION_BUILD'))
	);
} catch (MemcacheWrapperError $e) {
	UUtils::logException($e);
}

// _tests:
/*if (isset($_REQUEST['IBLOCK_CACHE_PROPERTY'])) {
	// var_dump( $GLOBALS['IBLOCK_CACHE_PROPERTY']->getMc()->getExtendedStats() );

	$keys = $GLOBALS['IBLOCK_CACHE_PROPERTY']->getMemcachedKeys();
	foreach ($keys as $k) {
		echo $k;
		\Bitrix\Main\Diag\Debug::dump($GLOBALS['IBLOCK_CACHE_PROPERTY'][$k]);
	}
	exit;
}*/
