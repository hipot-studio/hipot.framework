<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 01.01.2023 21:01
 * @version pre 2.0
 * @deprecated
 */

// region /*** Abstract Iblock Elements Layer ***/
use Bitrix\Main\Loader,
	Bitrix\Main\Application;
use Hipot\IbAbstractLayer\GenerateSxem\IblockGenerateSxemManager;

$serverName = Application::getInstance()->getContext()->getRequest()->getServer()->getServerName();

if (! defined('ABSTRACT_LAYER_SAULT')) {
	/**
	 * Соль в именах генерируемых классов, разрешены символы [0-9a-zA-Z_]
	 * по-умолчанию, устанавливается в трансформированное имя домена, напр.
	 * www.good-site.hipot.com --> GOOD_SITE_HIPOT_COM
	 * @var string
	 */
	define('ABSTRACT_LAYER_SAULT', ToUpper(str_replace(['www.', '.', '-', ':80', ':8080'], ['_', '_', '_', ''], $serverName)));
}

/**
 * Файл с сгенерированной схемой элементов инфоблоков
 * @var string
 * @global
 */
$dir = Loader::getDocumentRoot() . '/bitrix/modules/';
if (is_dir(Loader::getDocumentRoot() . '/local/php_interface/')) {
	$dir = Loader::getDocumentRoot() . '/local/php_interface/';
}
$fileToGenerateSchema = $dir . 'hipot_annotations.php';
if (! file_exists($fileToGenerateSchema)) {
	IblockGenerateSxemManager::updateSchema($fileToGenerateSchema);
}
// устанавливаем обработку событий
IblockGenerateSxemManager::setUpdateHandlers($fileToGenerateSchema);

// endregion /*** END Abstract Iblock Elements Layer ***/
