<?php
/**
 * Very tiny simple autoloader with support of classes
 *
 * @author hipot, 2022
 * @version 2.5
 *
 * HELP:
 * <classes root> is:
 * - /local/php_interface/include/lib/classes/
 * - /bitrix/php_interface/include/lib/classes/
 *
 * use classes with namespaces:
 * \Hipot\Db\AbstractBaseAdapter	-->  <classes root>/hipot/db/abstractbaseadapter.php
 * \CImg 							-->  <classes root>/cimg.php
 * ...
 */

/*
 * add custom __autoload in stack after \Bitrix\Main\Loader::autoLoad()
 * see: var_dump( spl_autoload_functions() );
 * TIP: use only in not-with-composer projects
 */
spl_autoload_register(static function ($className) {
	//echo $className; die();
	$libDirs = [
		__DIR__ . '/classes',
		$_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/lib/classes',
		$_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/lib/classes',
		$_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/lib/classes',
		$_SERVER['DOCUMENT_ROOT'] . '/_tests/classes',
		$_SERVER['DOCUMENT_ROOT'] . '/local/modules/hipot.framework/lib',
		$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/hipot.framework/lib'
		// ...
	];

	// region bitrix self-hosted composer
	/* @var SplFileInfo $fileinfo */
	/* @var SplFileInfo $fileinfoInside */
	/*
	static $mainVendorSrcDirs = [], $isMainVendorLoaded = false;
	if (! $isMainVendorLoaded) {
		$baseMainVendorSrcDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/vendor';
		$mainVendorSrcDirs[] = $baseMainVendorSrcDir;

		$iter = new DirectoryIterator($baseMainVendorSrcDir);
		foreach ($iter as $fileinfo) {
			if (! $fileinfo->isDir()) {
				continue;
			}
			$subDir = new DirectoryIterator($fileinfo->getRealPath());
			foreach ($subDir as $fileinfoInside) {
				if ($fileinfoInside->isDir() && is_dir($fileinfoInside->getRealPath() . '/src')) {
					$mainVendorSrcDirs[] = $fileinfoInside->getRealPath() . '/src';
				}
			}
		}
		$isMainVendorLoaded = true;
	}
	$libDirs = [...$libDirs, ...$mainVendorSrcDirs];
	*/
	// endregion

	// region hipot component classes
	\Hipot\Utils\UUtils::loadHipotComponentClass($className);
	if (class_exists($className, false)) {
		return;
	}
	// endregion

	foreach ($libDirs as $libDir) {
		// to work with other frameworks (psr-4), case-sensitive UNIX folders
		$checkPaths = [
			$libDir . '/' . str_replace('\\', '/', $className) . '.php',
			$libDir . '/' . str_replace('\\', '/', strtolower($className)) . '.php',
			$libDir . '/' . str_replace(['\\', ''], ['/', 'Table'], $className) . '.php',
			$libDir . '/' . str_replace(['\\', ''], ['/', 'table'], strtolower($className)) . '.php',
			// ...
		];
		foreach ($checkPaths as $classFile) {
			if (file_exists($classFile) && is_readable($classFile)) {
				/** @noinspection PhpIncludeInspection */
				require $classFile;
				return;
			}
		}
	}
});
//\Bitrix\Main\Diag\Debug::dump( spl_autoload_functions() );
