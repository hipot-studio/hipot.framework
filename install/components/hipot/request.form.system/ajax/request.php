<?
/**
 * аякс пост формы, тут и выполняются действия по форме
 */

if ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest'
	|| trim($_POST['token']) != ''
) {
	exit;
}

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";


/**
 * Экранирует элементы массива
 *
 * @param array $array Сам массив.
 * @param bool  $orig = false Возвращать ли оригинальные элементы с '~'.
 * @return bool|array
 */
$escapeArray = function ($array, $orig = false) use (&$escapeArray) {
	$res = false;
	foreach ($array as $k => $v) {
		if (is_array($v)) {
			$o = ($orig) ? true : false;
			$res[$k] = $escapeArray($v, $o);
		} else {
			$res[$k] = htmlspecialcharsEx($v);
			if ($orig) {
				$res['~'.$k] = $v;
			}
		}
	}
	return $res;
};

$escPOST = $escapeArray($_POST);
if ($escPOST['__form__'] == 'recall') {
	if ((int)$escPOST[ $escPOST['__form__'] ]['good_id'] > 0) {
		\CModule::IncludeModule('iblock');
		$el = CIBlockElement::GetByID( (int)$escPOST[ $escPOST['__form__'] ]['good_id'] )->GetNext();
		if ($el['ID']) {
			$url = 'https://www.wellmood.ru' . $el['DETAIL_PAGE_URL'];
			$escPOST[ $escPOST['__form__'] ]['good_url'] = "<a href={$url}>{$url}</a>";
			$escPOST[ $escPOST['__form__'] ]['good_name'] = $el['NAME'];
		}
	}

	$APPLICATION->IncludeComponent("hipot:request.form.system", "recall_good_ajax", [
		"_POST"			=> $escPOST,
		'AJAX_CALL'     => 'Y',
		"POST_NAME"		=> "recall",
		"REQ_FIELDS"	=> array("phone"),
		"EVENT_TYPE"	=> "RECALL_REQUEST",
		"EVENT_ID"		=> 0,
		"ADD_ELEMENT"	=> array(),
		"NO_REDIRECT"	=> "Y"
	], false, ['HIDE_ICONS' => 'Y']);

} else {
	die("wtf?");
}

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php";
?>