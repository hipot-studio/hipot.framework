<?php
defined('B_PROLOG_INCLUDED') || die();
/**
 * Bitrix init.php entry point example
 */

// region better use composer 'vendor/autoload.php'

// autoloader (deprecated, better use composer PSR-4 autoloader)
require __DIR__ . '/lib/simple_loader.php';

// плавающие функции
require __DIR__ . '/lib/functions.php';

// endregion

// iblock props in memcache
if (file_exists(__DIR__ . '/lib/ib_props_memcache.php')) {
	require __DIR__ . '/lib/ib_props_memcache.php';
}

// Abstract Iblock Elements Layer (deprecated, better use d7 orm iblock api)
if (file_exists(__DIR__ . '/lib/iblock_layer_model.php')) {
	require __DIR__ . '/lib/iblock_layer_model.php';
}

// добавление обработчиков (без определения, определения писать лучше в отдельном классе)
require __DIR__ . '/lib/handlers_add.php';