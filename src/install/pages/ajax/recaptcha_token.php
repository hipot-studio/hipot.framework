<?php
// performance fixs
define('BX_SKIP_SESSION_EXPAND', true);
define('BX_SESSION_ID_CHANGE', false);
define('BX_SKIP_POST_UNQUOTE', true);
define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('STATISTIC_SKIP_ACTIVITY_CHECK', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('PERFMON_STOP', true);

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

if (!IS_AJAX) {
	exit;
}

use Bitrix\Main\Web\Json;
use Hipot\Services\BitrixEngine;
use Hipot\Services\Recaptcha3;

$request = BitrixEngine::getInstance()->request;
$recaptcha = new Recaptcha3($request);
$responseKeys = $recaptcha->sendRequestToCaptchaServer();
die(Json::encode($responseKeys));

?>