<?php

declare(strict_types=1);

namespace Bitrix\Main {
	class Loader
	{
		public static function includeModule(string $module): bool
		{
			$GLOBALS['result']['module'] = $module;
			return $GLOBALS['scenario'] !== 'no-module';
		}
	}

	class Application
	{
		public static function getConnection(): object
		{
			return $GLOBALS['connection'];
		}
	}
}

namespace {
	$scenario = $argv[1];
	$result = ['hits' => [], 'savedQueries' => [], 'started' => 0, 'stopped' => 0];
	$connection = new class {
		public ?object $tracker = null;

		public function getTracker(): ?object
		{
			return $this->tracker;
		}

		public function startTracker(): object
		{
			$GLOBALS['result']['started']++;
			return $this->tracker = new class {
				public function getQueries(): array
				{
					return [['QUERY' => 'SELECT 1', 'TIME' => 0.25], ['QUERY' => 'SELECT 2', 'TIME' => 0.5]];
				}
			};
		}

		public function stopTracker(): void
		{
			$GLOBALS['result']['stopped']++;
		}
	};
	$DB = new class {
		public function GetNowFunction(): string
		{
			return 'CURRENT_TIMESTAMP';
		}

		public function Add(string $table, array $fields): int|false
		{
			if ($GLOBALS['result']['stopped'] !== 1) {
				throw new \RuntimeException('Report SQL must not be tracked');
			}
			if ($GLOBALS['scenario'] === 'exception') {
				throw new \RuntimeException('Database unavailable');
			}
			$GLOBALS['result']['hits'][] = ['table' => $table, 'fields' => $fields];
			return $GLOBALS['scenario'] === 'insert-failed' ? false : 42;
		}
	};
	$APPLICATION = (object)['arIncludeDebug' => [], '_menu_recalc_counter' => 2];

	class CPerfomanceKeeper
	{
		public static function countComponents(&$count, &$time, array $debug): void
		{
			$count = 0;
			$time = 0.0;
		}

		public static function SetPageTimes(string $now, array &$fields): void
		{
			$fields['testTimeFormat'] = count(explode(' ', $now)) === 2;
		}

		public static function saveQueries($hitId, $componentId, array $queries, &$counter): void
		{
			$GLOBALS['result']['savedQueries'] = compact('hitId', 'componentId', 'queries');
		}
	}

	if ($scenario !== 'no-secret') {
		define('BITRIX_SQL_PROFILE_VALUE', $scenario === 'empty-secret' ? '' : 'test-secret');
	}
	define('ADMIN_SECTION', false);
	$_GET['BITRIX_SQL_PROFILE'] = match ($scenario) {
		'wrong-secret' => 'wrong',
		'array-trigger' => ['test-secret'],
		default => 'test-secret',
	};
	$_SERVER = [
		'SCRIPT_NAME' => '/bitrix/urlrewrite.php',
		'REAL_FILE_PATH' => '/catalog/index.php',
		'REQUEST_URI' => '/catalog/?BITRIX_SQL_PROFILE=test-secret',
		'REQUEST_METHOD' => 'GET',
	];
	if ($scenario === 'existing-tracker') {
		$connection->tracker = new \stdClass();
	}
	$script = dirname(__DIR__, 2) . '/src/lib/sql_profile.php';
	require_once $script;
	require_once $script;
	if ($scenario === 'replaced-tracker') {
		$connection->tracker = new \stdClass();
	}
	register_shutdown_function(static function (): void {
		echo json_encode($GLOBALS['result'], JSON_THROW_ON_ERROR);
	});
}
