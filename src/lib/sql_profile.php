<?php /** @noinspection GlobalVariableUsageInspection */

declare(strict_types=1);

/**
 * Точечное SQL-профилирование через штатный perfmon без внешних зависимостей.
 * Подключать через require_once из after_connect_d7.php; см. docs/sql_profile.md.
 * Самостоятельный ранний скрипт: сервисы Hipot здесь ещё не инициализированы.
 */
(static function (): void {
	$secret = defined('BITRIX_SQL_PROFILE_VALUE') ? constant('BITRIX_SQL_PROFILE_VALUE') : null;
	$trigger = $_GET['BITRIX_SQL_PROFILE'] ?? null;

	if (!is_string($secret) || $secret === '' || !is_string($trigger) || !hash_equals($secret, $trigger)) {
		return;
	}

	if (!\Bitrix\Main\Loader::includeModule('perfmon')) {
		return;
	}

	$logError = static function (string $message): void {
		/** @noinspection ForgottenDebugOutputInspection */
		error_log('hipot SQL profile: ' . $message);
	};

	$connection = \Bitrix\Main\Application::getConnection();
	if ($connection->getTracker() !== null) {
		$logError('замер пропущен, SQL tracker уже используется.');
		return;
	}

	$tracker = $connection->startTracker();
	register_shutdown_function(static function () use ($connection, $tracker, $logError): void {
		try {
			// Другой профайлер мог заменить трекер во время выполнения страницы.
			if ($connection->getTracker() !== $tracker) {
				$logError('SQL tracker заменён, отчёт не сохранён.');
				return;
			}

			$connection->stopTracker();
			$queries = $tracker->getQueries();
			global $DB, $APPLICATION;
			if (!is_object($DB) || !is_object($APPLICATION)) {
				$logError('окружение Bitrix не инициализировано, отчёт не сохранён.');
				return;
			}

			$queryTime = 0.0;
			foreach ($queries as $query) {
				$queryTime += $query['TIME'];
			}
			$componentCount = 0;
			$componentTime = 0.0;
			\CPerfomanceKeeper::countComponents(
				$componentCount, $componentTime, $APPLICATION->arIncludeDebug ?? [],
			);
			$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
			if (in_array($scriptName, ['/bitrix/urlrewrite.php', '/404.php'], true)) {
				$scriptName = $_SERVER['REAL_FILE_PATH'] ?? $scriptName;
			}
			$fields = [
				'~DATE_HIT'         => $DB->GetNowFunction(),
				'IS_ADMIN'          => defined('ADMIN_SECTION') && ADMIN_SECTION ? 'Y' : 'N',
				'REQUEST_METHOD'    => $_SERVER['REQUEST_METHOD'] ?? '',
				'SERVER_NAME'       => $_SERVER['SERVER_NAME'] ?? '',
				'SERVER_PORT'       => $_SERVER['SERVER_PORT'] ?? '',
				'SCRIPT_NAME'       => $scriptName,
				'REQUEST_URI'       => $_SERVER['REQUEST_URI'] ?? '',
				'INCLUDED_FILES'    => count(get_included_files()),
				'MEMORY_PEAK_USAGE' => memory_get_peak_usage(true),
				'QUERIES'           => count($queries),
				'~QUERIES_TIME'     => $queryTime,
				'SQL_LOG'           => 'Y',
				'COMPONENTS'        => $componentCount,
				'~COMPONENTS_TIME'  => $componentTime,
				'~MENU_RECALC'      => (int)($APPLICATION->_menu_recalc_counter ?? 0),
			];
			// API ожидает текущее время в строковом формате microtime(), а не момент запуска.
			\CPerfomanceKeeper::SetPageTimes(microtime(), $fields);
			$hitId = $DB->Add('b_perf_hit', $fields);
			if (!$hitId) {
				$logError('не удалось сохранить хит.');
				return;
			}
			$counter = 0;
			\CPerfomanceKeeper::saveQueries($hitId, false, $queries, $counter);
		} catch (\Throwable $exception) {
			// Диагностика не должна обрывать завершение запроса сайта.
			$logError('ошибка сохранения отчёта (' . get_class($exception) . ').');
		}
	});
})();
