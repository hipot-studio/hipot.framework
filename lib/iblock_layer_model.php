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
	 * www.good-site.hipot.ru --> GOOD_SITE_HIPOT_RU
	 * @var string
	 */
	define('ABSTRACT_LAYER_SAULT', ToUpper(str_replace(['www.', '.', '-', ':80', ':8080'], ['_', '_', '_', ''], $serverName)));
}

/**
 * Файл с сгенерированной схемой элементов инфоблоков
 * @var string
 * @global
 */
$fileToGenerateSxema = $GLOBALS['fileToGenerateSxema'] = Loader::getDocumentRoot() . '/bitrix/modules/generated_iblock_sxem.php';
if (! file_exists($fileToGenerateSxema)) {
	IblockGenerateSxemManager::updateSxem($fileToGenerateSxema);
}
// устанавливаем обработку событий
IblockGenerateSxemManager::setUpdateHandlers();

// endregion /*** END Abstract Iblock Elements Layer ***/
