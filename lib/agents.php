<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 30.12.2018 22:50
 * @version pre 1.0
 */

/**
 * Удаляем заснувшие запросы и запросы SELECT, длительность которых больше 200 секунд
 *
 * Константа HI_NOT_REMOVE_LONG_SELECT позволяет отменить выполнение
 *
 * @return string
 */
function RemoveLongSelectAgent()
{
	if (constant('HI_NOT_REMOVE_LONG_SELECT') === true) {
		return __FUNCTION__ . '();';
	}
	$timeout_s = 200;

	global $DB;
	$r = $DB->Query('SHOW PROCESSLIST');

	while ($p = $r->Fetch()) {
		$sql		= trim($p['Info']);
		$procId		= (int)$p['Id'];

		if ((int)$p['Time'] >= $timeout_s &&
			(substr($sql, 0, 6) == 'SELECT' || $p['Command'] == 'Sleep')
		) {
			$DB->Query('KILL ' . $procId);
		}
	}
	return __FUNCTION__ . '();';
}

// check agents run log
if (! defined('BX_AGENTS_LOG_FUNCTION')) {
	define('BX_AGENTS_LOG_FUNCTION', 'HipotAgentsLogFunction');

	function HipotAgentsLogFunction($arAgent, $point)
	{
		@file_put_contents(
			$_SERVER["DOCUMENT_ROOT"] . '/agents_executions_points.log',

			date('d-m-Y H:i:s') . PHP_EOL .
			print_r($point, 1) . PHP_EOL .
			print_r($arAgent, 1) . PHP_EOL . PHP_EOL,

			FILE_APPEND
		);
	}
}
