<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 30.12.2018 22:50
 * @version pre 1.0
 */

use Hipot\Services\BitrixEngine;

/**
 * Завершаем заснувшие запросы и запросы SELECT, длительность которых больше 600 секунд.
 * Установленная константа HI_NOT_REMOVE_LONG_SELECT = true позволяет отменить это действие.
 * @return string
 */
function RemoveLongSelectAgent(): string
{
	if (constant('HI_NOT_REMOVE_LONG_SELECT') === true) {
		return __FUNCTION__ . '();';
	}
	$timeout_s = 600;
	
	$l = BitrixEngine::getInstance()->connection->query('SHOW PROCESSLIST');
	while ($p = $l->Fetch()) {
		$sql		= trim($p['Info']);
		$procId		= (int)$p['Id'];
		
		if ((int)$p['Time'] >= $timeout_s &&
			(strpos($sql, 'SELECT') === 0 || $p['Command'] == 'Sleep')
		) {
			BitrixEngine::getInstance()->connection->query('KILL ' . $procId);
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
			BitrixEngine::getInstance()->app::getDocumentRoot() . '/agents_executions_points.log',
			
			date('d-m-Y H:i:s') . PHP_EOL .
			print_r($point, 1) . PHP_EOL .
			print_r($arAgent, 1) . PHP_EOL . PHP_EOL,
			
			FILE_APPEND
		);
	}
}
