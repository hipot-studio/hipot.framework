<?
namespace Hipot\Utils;

use Bitrix\Main\Loader;
use Hipot\Types\UpdateResult;
use Hipot\Utils\Helper\ArrayTools;
use Hipot\Utils\Helper\ObjectTools;
use Hipot\Utils\Helper\StringUtils;
use Hipot\Utils\Helper\ContextUtils;
use Hipot\Utils\Helper\ComponentUtils;
use Hipot\Utils\Helper\DateTimeUtils;
use Hipot\Utils\Helper\UserFieldUtils;

/**
 * Различные не-структурированные утилиты
 *
 * @version 1.0
 * @author hipot studio
 */
class UUtils
{
	// text utils
	use StringUtils;

	// environment utils
	use ContextUtils;

	// bx components utils
	use ComponentUtils;

	// calendar utils
	use DateTimeUtils;

	// UF_-field utils
	use UserFieldUtils;

	// misc collection utils
	use ArrayTools;

	// misc object utils
	use ObjectTools;

	/**
	 * Добавить в модуль веб-формы в форму данные
	 *
	 * @param int   $WEB_FORM_ID id формы, для которой пришел ответ
	 * @param array $arrVALUES = <pre>array (
	 * [WEB_FORM_ID] => 3
	 * [web_form_submit] => Отправить
	 *
	 * [form_text_18] => aafafsfasdf
	 * [form_text_19] => q1241431342
	 * [form_text_21] => afsafasdfdsaf
	 * [form_textarea_20] =>
	 * [form_text_22] => fasfdfasdf
	 * [form_text_23] => 31243123412впывапвыапывпыв аывпывпыв
	 *
	 * 18, 19, 21 - ID ответов у вопросов https://yadi.sk/i/_9fwfZMvO2kblA
	 * )</pre>
	 *
	 * @return bool | UpdateResult
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function formResultAddSimple($WEB_FORM_ID, $arrVALUES = [])
	{
		if (! Loader::includeModule('form')) {
			return false;
		}

		// add result like bitrix:form.result.new
		$arrVALUES['WEB_FORM_ID'] = (int)$WEB_FORM_ID;
		if ($arrVALUES['WEB_FORM_ID'] <= 0) {
			return false;
		}
		$arrVALUES["web_form_submit"] = "Отправить";

		if ($RESULT_ID = \CFormResult::Add($WEB_FORM_ID, $arrVALUES)) {
			if ($RESULT_ID) {
				// send email notifications
				\CFormCRM::onResultAdded($WEB_FORM_ID, $RESULT_ID);
				\CFormResult::SetEvent($RESULT_ID);
				\CFormResult::Mail($RESULT_ID);

				return new UpdateResult(['RESULT' => $RESULT_ID,    'STATUS' => UpdateResult::STATUS_OK]);
			}

			global $strError;
			return new UpdateResult(['RESULT' => $strError,     'STATUS' => UpdateResult::STATUS_ERROR]);
		}
		return false;
	}

	/**
	 * To validate a RegExp just run it against null (no need to know the data you want to
	 * test against upfront). If it returns explicit false (=== false), it's broken.
	 * Otherwise it's valid though it need not match anything.
	 *
	 * @param string $regx
	 * @return bool
	 */
	public static function isValidRegx(string $regx): bool
	{
		return preg_match($regx, null) !== false;
	}

} // end class


