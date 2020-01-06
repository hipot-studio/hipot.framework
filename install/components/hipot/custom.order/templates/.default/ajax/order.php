<?
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
	!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
	strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
) {

	define("NO_KEEP_STATISTIC", true);
	define("NOT_CHECK_PERMISSIONS", true);

	require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

	// return json
	$APPLICATION->IncludeComponent(
		"wellmood:custom.order",
		"",
		['AJAX' => 'Y'],
		false
	);
}
?>