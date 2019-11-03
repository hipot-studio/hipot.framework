<?
// autoloader
require __DIR__ . '/lib/simple_loader.php';

// iblock props in memcache
if (file_exists(__DIR__ . '/lib/ib_props_memcache.php')) {
	require __DIR__ . '/lib/ib_props_memcache.php';
}

// Abstract Iblock Elements Layer
if (file_exists(__DIR__ . '/lib/iblock_layer_model.php')) {
	require __DIR__ . '/lib/iblock_layer_model.php';
}

// плавающие функции
require __DIR__ . '/lib/functions.php';

// добавление обработчиков (без определения, определения писать лучше в отдельном классе)
require __DIR__ . '/lib/handlers_add.php';

?>