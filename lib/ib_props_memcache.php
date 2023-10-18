<?php
/**
 * @deprecated from iblock 23.200.0
 * https://dev.1c-bitrix.ru/docs/versions.php?lang=ru&module=iblock
 */

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
 * @use
 * 1/ add connection config in .settings_extra
 * 'connections' => ['value' => [
 *      'default' => $defaultSettings['connections']['value']['default'],
 *      'memcache' => [
 *              'className' => \Bitrix\Main\Data\MemcacheConnection::class,
 *              'port' => 0,
 *              'host' => 'unix:///home/bitrix/memcached.sock'
 *      ],
 * ]],
 * 2/ add need classes within autoloader (MemcacheWrapper, MemcacheWrapperError, UUtils)
 * 3/ require file in init.php
 * require __DIR__ . '/include/ib_props_memcache.php';
 *
 * !!! Обязательно перезапускать memcached при изменении инфоблоков в режим хранения свойств 2.0 и обратно
 *
 * 4/ TODO need patch file bitrix/modules/iblock/classes/general/iblockproperty.php
 * bitrix is_set function to isset-construction
 *
 * @version 1.5.2
 * @author hipot, 2022
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader,
	Bitrix\Main\Application,
	Bitrix\Main\Data\MemcacheConnection,
	Bitrix\Iblock\IblockTable,
	Hipot\Services\MemcacheWrapper,
	Hipot\Utils\UUtils;

if (!class_exists('Memcache') || !class_exists(MemcacheWrapper::class)) {
	UUtils::logException(new \Bitrix\Main\SystemException('no memcache classes to ' . basename(__FILE__)));
	return;
}

Loader::includeModule('iblock');

// init global $IBLOCK_CACHE_PROPERTY in iblock/classes/general/iblockproperty.php by autoload
$pr = new CIBlockProperty();
unset($pr);

// relay cache to iblock property types, avoid iblock2.0 not found tables error
$arIblockVersions = [];
$rs = IblockTable::query()->setSelect(['ID', 'VERSION'])->setOrder(['ID' => 'ASC'])->setCacheTtl(3600 * 24 * 3)->exec();
while ($ar = $rs->fetch()) {
	$arIblockVersions[ $ar['ID'] ] = $ar['VERSION'];
}

try {
	/** @var MemcacheConnection $mc */
	$mc = Application::getConnection('memcache');

	$GLOBALS['IBLOCK_CACHE_PROPERTY'] = new MemcacheWrapper(
		'IBLOCK_CACHE_PROPERTY_' . md5(serialize($arIblockVersions)),
		$mc->getResource()
	);
} catch (Error $e) {
	UUtils::logException($e);
}
unset($arIblockVersions);

// _tests:
/*
if (isset($_REQUEST['IBLOCK_CACHE_PROPERTY'])) {
	// var_dump( $GLOBALS['IBLOCK_CACHE_PROPERTY']->getMc()->getExtendedStats() );

	$keys = $GLOBALS['IBLOCK_CACHE_PROPERTY']->getMemcachedKeys( $mc->getConfiguration() );
	foreach ($keys as $k) {
		echo $k;
		\Bitrix\Main\Diag\Debug::dump($GLOBALS['IBLOCK_CACHE_PROPERTY'][$k]);
	}
	exit;
}
*/