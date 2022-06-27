<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 23.03.2022 14:59
 * @version pre 1.0
 */

/**
 * usage:
 *
 * require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/lib/xhprof.php';
 * define('ENABLE_XHPROF', true);
 *
 * on top of file and see https://www.domain.ru/local/xhprof_html/
 */

$xhprofStart = static function () {
	global $getMicrotime;

	$getMicrotime = static function () {
		list($usec, $sec) = explode(' ', microtime());
		return (float)$usec + (float)$sec;
	};
	xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
	$GLOBALS['xhprof_start_time'] = $getMicrotime();
};

$xhprofEnd = static function ($minDiffTime = null) {
	global $getMicrotime;
	$xhprof_data = xhprof_disable();
	$tStart      = $GLOBALS['xhprof_start_time'];
	$diffTime    = $getMicrotime() - $tStart;

	// limit 1 secs to write logs
	if (is_null($minDiffTime) || $diffTime > (int)$minDiffTime) {
		$timeF      = round($diffTime, 3) . "";
		$timeF      = str_replace('.', '-', $timeF);
		$client     = PHP_SAPI == 'cli' ? 'shell' : 'http';
		$uri        = $_SERVER['REQUEST_URI'] ? $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
		$uri        = explode('/', $uri);
		$uri        = implode('|', $uri);
		$xhprofCode = $client . '__' . $uri . '__' . $timeF . 's';
		$xhprofCode = preg_replace('#[^a-z0-9_|-]#i', '_', $xhprofCode);
		$xhprofCode = substr($xhprofCode, 0, 200);  // linux max filename

		$pathXhprof = $_SERVER['DOCUMENT_ROOT'] . '/local';

		require_once $pathXhprof . '/xhprof_lib/utils/xhprof_lib.php';
		require_once $pathXhprof . '/xhprof_lib/utils/xhprof_runs.php';

		/** @noinspection PhpUndefinedClassInspection */
		$xhprof_runs = new XHProfRuns_Default();
		$run_id      = $xhprof_runs->save_run($xhprof_data, $xhprofCode);
	}
};

// define('ENABLE_XHPROF', true);
define('XHPROF_MIN_TIME_SEC', 1);
if ((ENABLE_XHPROF || isset($_REQUEST['ENABLE_XHPROF'])) && function_exists('xhprof_enable')) {
	$xhprofStart();
	register_shutdown_function(static function () use ($xhprofEnd) {
		$xhprofEnd(defined('XHPROF_MIN_TIME_SEC') ? XHPROF_MIN_TIME_SEC : 3);
	});
}