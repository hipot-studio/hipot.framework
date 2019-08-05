<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * Файл дополнительных обработчиков, напр. если нужны еще какие-либо действия
 * после получения результата. Допустим, у нас форма  $arParams["POST_NAME"] == subscribe
 * чтобы сделать еще каких-либо действий после успешной отправки формы, делаем:
 *

if (! function_exists('CustomRequestHandler_subscribe')) {
	/**
	 * Дополнительный обработчик для формы $arParams["POST_NAME"] == subscribe
	 * @param array $_post массив после успешного выполнения $arParams[_POST]
	 * @param array $mailVars массив $mailVars
	 * @param array $arParams - весь массив $arParams
	 * /
	function CustomRequestHandler_subscribe($_post, $mailVars, $arParams)
	{
		echo '<pre>';
		print_r($_post);
		print_r($mailVars);
		print_r($arParams);
		echo '</pre>';
		exit;
	}
}
*/


if (! function_exists('CustomRequestHandler_order')) {
	/**
	 * Дополнительный обработчик для формы $arParams["POST_NAME"] == order
	 * @param array $_post массив после успешного выполнения $arParams[_POST]
	 * @param array $mailVars массив $mailVars
	 * @param array $arParams - весь массив $arParams
	 */
	function CustomRequestHandler_order($_post, $mailVars, $arParams)
	{
		require_once $_SERVER["DOCUMENT_ROOT"] . '/bitrix/php_interface/include/lib/classes/bmailer/attaches_bmailer.php';
		
		if ($_FILES['file']['size'] > 0 && intval($_FILES['file']['error']) == 0) {
			$mailVars['file_descr'] = 'Пользователь прикрепил файл с именем: "' . $_FILES['file']['name'] . "\"\n"
									. 'Внимание! Файлы, пришедшие по почте могут содержать вирусы, проверяйте их антивирусами.';
			$mailVars['FILES'] = array(
				array(
					'SRC'   => $_FILES['file']['tmp_name'],
					'NAME'  => $_FILES['file']['name']
				)
			);
		} else {
			$mailVars['file_descr'] = '';
		}
		
		$mailer = new AttachesBmailer();
		if ($mailer->sendMessage($arParams['EVENT_TYPE'], $mailVars)) {
			//echo 'SENDED!';
		} else {
			//echo 'ERROR SENDING!';
		}
	}
}
?>