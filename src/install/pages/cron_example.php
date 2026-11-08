<?
// @see echo $_SERVER["DOCUMENT_ROOT"]
$_SERVER["DOCUMENT_ROOT"] = "/home/bitrix/www";
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

// performance fixs
define("STOP_STATISTICS",       true);
define("NO_KEEP_STATISTIC",     true);
define("NO_AGENT_STATISTIC",    "Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck",    true);
define("BX_SECURITY_SHOW_MESSAGE", true);
// Виртуальная сессия
define('BX_SECURITY_SESSION_VIRTUAL', true);

// not checked
define("BX_BUFFER_USED",            true);
define('BX_NO_ACCELERATOR_RESET',   true);

define('BX_FORCE_DISABLE_SEPARATED_SESSION_MODE', true);

//ajax check
/*if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || empty($_SERVER['HTTP_X_REQUESTED_WITH'])
	|| strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest'
) {
	return;
}*/

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

// subbuf reset
set_time_limit(0);
while (ob_get_level()) {
	ob_end_flush();
}

// code

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php";
?>