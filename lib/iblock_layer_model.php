<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 01.01.2023 21:01
 * @version pre 2.0
 * @deprecated
 */

use Bitrix\Main\Loader,
	Bitrix\Main\Application;
use Hipot\IbAbstractLayer\GenerateSxem\IblockGenerateSxemManager;

// configs:
// const ABSTRACT_LAYER_SAULT = 'SUPER_SITE';
// const ABSTRACT_LAYER_SELECT_CHAINS_DEPTH = 3;
// const ABSTRACT_LAYER_ANNOTATIONS_FILE = '/local/php_interface/hipot_annotations.php';

// region /*** Abstract Iblock Elements Layer init ***/

if (! defined('ABSTRACT_LAYER_SAULT')) {
	$serverName = Application::getInstance()->getContext()->getRequest()->getServer()->getServerName();
	/**
	 * Соль в именах генерируемых классов, разрешены символы [0-9a-zA-Z_]
	 * по-умолчанию, устанавливается в трансформированное имя домена, напр.
	 * www.good-site.hipot-studio.com --> GOOD_SITE_HIPOT_STUDIO_COM
	 */
	define('ABSTRACT_LAYER_SAULT', strtoupper(str_replace(['www.', '.', '-', ':80', ':8080'], ['_', '_', '_', ''], $serverName)));
}
if (! defined('ABSTRACT_LAYER_SELECT_CHAINS_DEPTH')) {
	/**
	 * Глубина выборки цепочек-связанных элементов по ключу PROPERTY->CHAIN
	 */
	define('ABSTRACT_LAYER_SELECT_CHAINS_DEPTH', 3);
}
if (! defined('ABSTRACT_LAYER_ANNOTATIONS_FILE')) {
	$dir = is_dir(Loader::getDocumentRoot() . '/local/php_interface/') ? '/local/php_interface/' : '/bitrix/modules/';
	/**
	 * Файл со сгенерированной схемой элементов инфоблоков от documentRoot
	 */
	define('ABSTRACT_LAYER_ANNOTATIONS_FILE', $dir . 'hipot_annotations.php');
}

$fileToGenerateSchema = Loader::getDocumentRoot() . ABSTRACT_LAYER_ANNOTATIONS_FILE;
if (! file_exists($fileToGenerateSchema)) {
	IblockGenerateSxemManager::updateSchema($fileToGenerateSchema);
}
// устанавливаем обработку событий
IblockGenerateSxemManager::setUpdateHandlers($fileToGenerateSchema);
unset($serverName, $dir, $fileToGenerateSchema);

// endregion
