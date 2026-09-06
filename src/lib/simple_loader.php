<?php
/**
 * Very tiny simple autoloader with support of classes
 *
 * @author hipot, 2025
 * @version 3.0
 *
 * HELP:
 * <classes root> is:
 * - /local/php_interface/include/lib/classes/
 * - /bitrix/php_interface/include/lib/classes/
 *
 * Use classes with namespaces:
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

	// region apcu-cache like in composer
	$apcuPrefix = function_exists('apcu_fetch') && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN)
		? md5(__FILE__) : null;

	if (null !== $apcuPrefix) {
		$classFile = apcu_fetch($apcuPrefix . $className, $hit);
		if ($hit) {
			/** @noinspection PhpIncludeInspection */
			require $classFile;
			return;
		}
	}
	// endregion

	/** @noinspection GlobalVariableUsageInspection */
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
	if (class_exists(\Hipot\Utils\UUtils::class)) {
		\Hipot\Utils\UUtils::loadHipotComponentClass($className);
		if (class_exists($className, false)) {
			return;
		}
	}
	// endregion

	foreach ($libDirs as $libDir) {
		if (! is_dir($libDir)) {
			continue;
		}
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

				// region apcu-cache like in composer
				if (null !== $apcuPrefix) {
					apcu_add($apcuPrefix . $className, $classFile);
				}
				// endregion
				return;
			}
		}
	}
});
//\Bitrix\Main\Diag\Debug::dump( spl_autoload_functions() );
