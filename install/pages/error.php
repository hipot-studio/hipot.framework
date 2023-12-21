<?php
/**
 * error page with managed errors.
 * You can define your own function debug_string_backtrace() to generate stack-error-message
 *
 * @version 2.0
 * @author hipot-studio.com
 * @see \Bitrix\Main\Diag\HttpExceptionHandlerOutput::renderExceptionMessage()
 */
defined('B_PROLOG_INCLUDED') || die();

/**  @var \Throwable $exception */

use Bitrix\Main\Diag\ExceptionHandlerFormatter,
	Bitrix\Main\Application,
	Bitrix\Main\Engine\CurrentUser,
	Bitrix\Main\Mail\Event;

$developerEmail = 'info@hipot-studio.com';
$request        = Application::getInstance()?->getContext()?->getRequest();

// to copy and one-time-run in admin PHP Command line instrument...
$installEmailType       = static function ($typeId = 'DEBUG_MESSAGE'): bool {
	$arSites = \Bitrix\Main\SiteTable::getList([
		'filter' => [],
		'select' => ["LID", "NAME", "LANGUAGE_ID"],
	])->fetchAll();

	if (!is_countable($arSites) || !class_exists(\CEventType::class)) {
		return false;
	}

	$rsT = \CEventType::GetList(['TYPE_ID' => $typeId]);
	if ($rsT->SelectedRowsCount() >= 1) {
		return true;
	}

	$typeIdInputs = [];
	foreach (array_column($arSites, 'LANGUAGE_ID') as $langId) {
		$et   = new \CEventType();
		/** @noinspection StaticInvocationViaThisInspection */
		$typeIdInputs[] = (int)$et->Add([
			"LID"         => $langId,
			"EVENT_NAME"  => $typeId,
			"NAME"        => 'Debug message to developer',
			"DESCRIPTION" => '#EMAIL# - developers email' . PHP_EOL
				. '#SUBJECT# - email subject' . PHP_EOL
				. '#HTML# - email body'
		]);
	}
	if (count(array_filter($typeIdInputs)) > 0) {
		$arNewM = [
			'ACTIVE'     => 'Y',
			'EVENT_NAME' => $typeId,
			'LID'        => array_column($arSites, 'LID'),
			'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
			'EMAIL_TO'   => '#EMAIL#',
			'SUBJECT'    => '#SUBJECT#',
			'BODY_TYPE'  => 'html',
			'MESSAGE'    => '#HTML#',
		];
		$mess = new \CEventMessage();
		$mId = $mess->Add($arNewM);
		return (int)$mId > 0;
	}
	return false;
};
$sendEmailToSupport     = static function () use ($exception, $developerEmail, $request, $argv, $installEmailType) {
	$html = 'Данные об ошибке:' . "\n";
	$html .= ExceptionHandlerFormatter::format($exception, true);
	if (function_exists('debug_string_backtrace')) {
		$html .= '<pre>'. debug_string_backtrace() . "</pre>\n";
	}

	$html .= "\n\nДополнительные переменные:\n" . "\n";
	/** @noinspection GlobalVariableUsageInspection */
	$html .= '<pre>'. wordwrap(print_r([
			'request'   => is_null($request) ? $_REQUEST    : $request->toArray(),
			'cookie'    => is_null($request) ? $_COOKIE     : $request->getCookieList()->toArray(),
			'session'   => $_SESSION,
			'server'    => is_null($request) ? $_SERVER     : $request->getServer()->toArray(),
			'PHP_SAPI'  => PHP_SAPI
		] , true), 100) . "</pre>\n". "\n";

	$subject = 'Ошибка PHP ';
	if (PHP_SAPI == 'cli') {
		$subject .= $argv[0];
	} else {
		/** @noinspection GlobalVariableUsageInspection */
		$subject .= (is_null($request) ? $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']
			: $request->getServer()->getServerName() . $request->getRequestUri());
	}

	$installEmailType();
	Event::sendImmediate([
		"EVENT_NAME" => "DEBUG_MESSAGE",
		"LID" => defined('SITE_ID') ? SITE_ID : Application::getInstance()?->getContext()?->getLanguage(),
		"DUPLICATE" => "N",
		"C_FIELDS" => [
			"EMAIL"         => $developerEmail,
			"SUBJECT"       => $subject,
			"HTML"          => $html
		],
	]);
};
$isAdmin                = static function (): bool {
	/**
	 * CurrentUser::get()->isAdmin() вызывает ошибку Uncaught Error: Call to a member function isAdmin() on null, когда нет $USER
	 * (везде в порядке выполнения страницы https://dev.1c-bitrix.ru/api_help/main/general/pageplan.php до п.1.9) и агентах.
	 *
	 * геттера к внутреннему приватному полю нет, чтобы можно было проверять так:
	 *
	 * CurrentUser::getCUser() !== null && CurrentUser::get()->isAdmin()
	 *
	 * а по хорошему сделать проверку на инварианты: не создавать объект CurrentUser в методе get(), если global $USER === null
	 * тогда можно было бы использовать nullsafe-operator:
	 *
	 * CurrentUser::get()?->isAdmin()
	 */
	$bInternalUserExists = ( (function () {
			return $this->cuser;
		})->bindTo(CurrentUser::get(), CurrentUser::get()) )() !== null;
	return $bInternalUserExists && CurrentUser::get()->isAdmin();
};
$getExceptionStack      = static function (bool $htmlMode = false) use ($exception): string {
	$result = ExceptionHandlerFormatter::format($exception, $htmlMode);
	if (function_exists('debug_string_backtrace')) {
		$bt = debug_string_backtrace();
		if ($htmlMode) {
			$result .= '<pre>' . $bt . '</pre>';
		} else {
			$result .= $bt;
		}
	}
	return $result;
};

$isAjaxRequest = (defined('IS_AJAX') && IS_AJAX === true) || $request->isAjaxRequest();
$isCliTun = (PHP_SAPI == 'cli');
$isNotBetaTester = (!defined('IS_BETA_TESTER') || (defined('IS_BETA_TESTER') && IS_BETA_TESTER === false));

///////////////////////////////////////////////////////////////////////////////////////////////////

// try to send 503 error
if (!$isCliTun && !headers_sent()) {
	header('HTTP/1.1 503 Service Unavailable');
	header('Retry-After: 600');
}

if ($isCliTun) {
	$sendEmailToSupport();
	echo $getExceptionStack(false);
	return;
}

if ($isAjaxRequest) {
	if ($isNotBetaTester) {
		$sendEmailToSupport();
	}
	if ($isAdmin()) {
		echo $getExceptionStack(false);
	}
	return;
}
?>
<!--noindex-->
<style>
	.fatal-error {font-size:130%; font-family:Arial, "Helvetica Neue", Helvetica, sans-serif; padding:10px; background:#fff; color:#000; clear:both; display:flex}
	.fatal-error > div {}
	.fatal-error * {font-family:Arial, "Helvetica Neue", Helvetica, sans-serif; color:#000;}
	.fatal-error a {text-decoration:underline;}
	.fatal-error a:hover {color:#b24040;}
	.fatal-error .error-raw {background:#e8e7e7; padding:8px; font-size:11px; margin:5px 0;}
	.fatal-error .error-raw * {font-family:Consolas, 'Courier New', Courier, monospace !important;}
	.fatal-error .has-error {font-size:110%; padding:0 0 10px 0; font-weight:bold; color:#b24040;}
	.fatal-error .we-know {font-weight:bold;}
	.fatal-error img {float:left; margin:0 6px 6px 0; max-width:120px;}
	.clearer {clear:both; font-size:0; line-height:0; height:0;}
</style>
<div class="clearer"></div>
<div class="fatal-error">
	<div><img src="data:image/jpeg;base64,/9j/4QAYRXhpZgAASUkqAAgAAAAAAAAAAAAAAP/sABFEdWNreQABAAQAAAAoAAD/4QMvaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLwA8P3hwYWNrZXQgYmVnaW49Iu+7vyIgaWQ9Ilc1TTBNcENlaGlIenJlU3pOVGN6a2M5ZCI/PiA8eDp4bXBtZXRhIHhtbG5zOng9ImFkb2JlOm5zOm1ldGEvIiB4OnhtcHRrPSJBZG9iZSBYTVAgQ29yZSA5LjEtYzAwMSA3OS5hOGQ0NzUzNDksIDIwMjMvMDMvMjMtMTM6MDU6NDUgICAgICAgICI+IDxyZGY6UkRGIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyI+IDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bXA6Q3JlYXRvclRvb2w9IkFkb2JlIFBob3Rvc2hvcCAyNC42IChXaW5kb3dzKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpDMzdBMEE3NjlFQjUxMUVFODQ4N0U3QTNFOTJFQjcyNyIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpDMzdBMEE3NzlFQjUxMUVFODQ4N0U3QTNFOTJFQjcyNyI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOkMzN0EwQTc0OUVCNTExRUU4NDg3RTdBM0U5MkVCNzI3IiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOkMzN0EwQTc1OUVCNTExRUU4NDg3RTdBM0U5MkVCNzI3Ii8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+/+4AIUFkb2JlAGTAAAAAAQMAEAMDBgkAAAo/AAANwgAAFDr/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" alt="" /></div>
	<div>
		<div class="has-error">Упс…<br />
			Похоже возникла ошибка в нашем программном коде</div>
		<div class="we-know">Мы уже об этом знаем, и в ближайшее время все будет исправлено.</div>
		Просим прощения за неудобства.<br /><br />

		<small>Вы можете <a href="mailto:<?=$developerEmail?>?subject=Ошибка+PHP+<?=htmlspecialcharsbx($request->getServer()->getServerName() . $request->getRequestUri())?>">написать нам</a>, указав подробности,
			если вы не впервые видите данную ошибку</small><br />

		<?
		if ($isNotBetaTester) {
			$sendEmailToSupport();
		}
		if ($isAdmin()) {?>
			<div class="error-raw">
				<?
				echo $getExceptionStack(true);
				?>
			</div>
		<?}?>

		<br /><br />Пожалуйста, обновите страницу через минуту. Скоро все заработает.
	</div>
</div>
<div class="clearer"></div>
<!--/noindex-->