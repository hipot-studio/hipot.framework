<?php
declare(strict_types=1);

$projectAutoloader = require dirname(__DIR__, 2) . '/vendor/autoload.php';
$initialOutputBufferLevel = ob_get_level();

/** @noinspection GlobalVariableUsageInspection */
$_SERVER["DOCUMENT_ROOT"] = getenv('LOCAL_BITRIX_DOCUMENT_ROOT');

const BX_SKIP_SESSION_EXPAND = true;
const BX_SESSION_ID_CHANGE   = false;
const BX_SKIP_POST_UNQUOTE   = true;
const STOP_STATISTICS        = true;
const NO_KEEP_STATISTIC      = 'Y';
const NO_AGENT_STATISTIC     = 'Y';
const STATISTIC_SKIP_ACTIVITY_CHECK = true;
const NO_AGENT_CHECK = true;
const NOT_CHECK_PERMISSIONS = true;
const DisableEventsCheck = true;
const BX_SECURITY_SHOW_MESSAGE = true;
const PERFMON_STOP = true;
const BX_SECURITY_SESSION_VIRTUAL = true;

/** @noinspection GlobalVariableUsageInspection */
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

// Bitrix can register the site's Composer loader before the test project's
// loader. PHPUnit classes must be resolved from this project's vendor.
$projectAutoloader->unregister();
$projectAutoloader->register(true);

// Do not let the buffer opened by the Bitrix prolog swallow Pest/TeamCity output.
while (ob_get_level() > $initialOutputBufferLevel) {
	ob_end_clean();
}

// for integration tests
const CATALOG_IBLOCK_ID = 2;
const OFFERS_IBLOCK_ID = 3;