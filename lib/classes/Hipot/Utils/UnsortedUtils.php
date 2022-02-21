<?
namespace Hipot\Utils;

use \Bitrix\Main\Application,
	\Bitrix\Main\Grid\Declension,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Web\HttpClient;
use CUtil;

/**
 * Различные не-структурированные утилиты
 *
 * @version 1.0
 * @author hipot studio
 */
class UnsortedUtils
{
	/**
	 * Возвращает слово с правильным суффиксом
	 *
	 * @param (int) $n - количество
	 * @param (array|string) $str - строка 'один|два|несколько' или 'слово|слова|слов'
	 *      или массив с такой же историей
	 * @return string
	 * @see https://localization-guide.readthedocs.io/en/latest/l10n/pluralforms.html
	 */
	public static function Suffix($n, $forms): string
	{
		if (is_string($forms)) {
			$forms = explode('|', $forms);
		}
		return ( new Declension($forms[0], $forms[1], $forms[2]) )->get($n);
	}

	/**
	 * Транслит в одну строку
	 * @param        $text
	 * @param string $lang
	 *
	 * @return string
	 */
	public static function TranslitText($text, $lang = 'ru'): string
	{
		return CUtil::translit(trim($text), $lang, array(
			'max_len' => 100,
			'change_case' => "L",
			'replace_space' => '-',
			'replace_other' => '-',
			'delete_repeat_replace' => true
		));
	}

	/**
	 * Функция определения кодировки строки
	 * Удобно для автоматического определения кодировки csv-файла
	 *
	 * Почему не mb_detect_encoding()? Если кратко — он не работает.
	 *
	 * @param string $string строка в неизвестной кодировке
	 * @param int $pattern_size = 50
	 *        если строка больше этого размера, то определение кодировки будет
	 *        производиться по шаблону из $pattern_size символов, взятых из середины
	 *        переданной строки. Это сделано для увеличения производительности на больших текстах.
	 * @return string 'cp1251' 'utf-8' 'ascii' '855' 'KOI8R' 'ISO-IR-111' 'CP866' 'KOI8U'
	 *
	 * @see http://habrahabr.ru/post/107945/
	 * @see http://forum.dklab.ru/viewtopic.php?t=37833
	 * @see http://forum.dklab.ru/viewtopic.php?t=37830
	 * @use iconv
	 */
	public static function detect_encoding($string, $pattern_size = 50): string
	{
		$list = [
			'cp1251',
			'utf-8',
			'ascii',
			'855',
			'KOI8R',
			'ISO-IR-111',
			'CP866',
			'KOI8U'
		];
		$c = strlen($string);
		if ($c > $pattern_size) {
			$string = substr($string, floor(($c - $pattern_size) / 2), $pattern_size);
			$c = $pattern_size;
		}

		$reg1 = '/(\xE0|\xE5|\xE8|\xEE|\xF3|\xFB|\xFD|\xFE|\xFF)/i';
		$reg2 = '/(\xE1|\xE2|\xE3|\xE4|\xE6|\xE7|\xE9|\xEA|\xEB|\xEC|\xED|\xEF|\xF0|\xF1|\xF2|\xF4|\xF5|\xF6|\xF7|\xF8|\xF9|\xFA|\xFC)/i';

		$mk = 10000;
		$enc = 'ascii';
		foreach ($list as $item) {
			$sample1 = @iconv($item, 'cp1251', $string);
			$gl = @preg_match_all($reg1, $sample1, $arr);
			$sl = @preg_match_all($reg2, $sample1, $arr);
			if (!$gl || !$sl) {
				continue;
			}
			$k = abs(3 - ($sl / $gl));
			$k += $c - $gl - $sl;
			if ($k < $mk) {
				$enc = $item;
				$mk = $k;
			}
		}
		return $enc;
	}

	/**
	 * Является ли текущая страница в данный момент страницей постраничной навигации
	 * @return bool
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function isPageNavigation(): bool
	{
		$request = Application::getInstance()->getContext()->getRequest();
		foreach ([1, 2, 3] as $pnCheck) {
			$req_name = 'PAGEN_' . $pnCheck;
			if ((int)$request->getPost($req_name) > 1 || (int)$request->getQuery($req_name) > 1) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Выполняет команду в OS в фоне и без получения ответа
	 *
	 * @param string $cmd команда на выполнение
	 * @see exec()
	 * @deprecated
	 */
	public static function execInBackground($cmd): void
	{
		if (strpos(php_uname(), "Windows") === 0) {
			pclose(popen("start /B " . $cmd, "r"));
		} else {
			exec($cmd . " > /dev/null &");
		}
	}

	/**
	 * Получить путь к php с учетом особенностей utf8 битрикс
	 * @return string
	 */
	public static function getPhpPath(): string
	{
		$phpPath = 'php';
		if (! defined('BX_UTF')) {
			$phpPath .= ' -d mbstring.func_overload=0 -d mbstring.internal_encoding=CP1251 ';
		} else {
			$phpPath .= ' -d mbstring.func_overload=2 -d mbstring.internal_encoding=UTF-8 ';
		}
		return $phpPath;
	}

	/**
	 * Пересекаются ли времена заданные unix-таймштампами.
	 *
	 * Решение сводится к проверке границ одного отрезка на принадлежность другому отрезку
	 * и наоборот. Достаточно попадания одной точки.
	 *
	 * @param int $left1_ts
	 * @param int $right1_ts
	 * @param int $left2_ts
	 * @param int $right2_ts
	 * @return boolean
	 */
	public static function IsIntervalsTsIncl($left1_ts, $right1_ts, $left2_ts, $right2_ts): bool
	{
		if ($left1_ts <= $left2_ts) {
			return $right1_ts >= $left2_ts;
		} else {
			return $left1_ts <= $right2_ts;
		}
	}

	/**
	 * Получить список колонок SQL-запросом, либо если уже был получен, то просто вернуть
	 * @param string $tableName имя таблицы
	 * @return array
	 */
	public static function getTableFieldsFromDB(string $tableName): array
	{
		$a = [];
		if (trim($tableName) != '') {
			$query	= "SHOW COLUMNS FROM " . $tableName;
			$res	= $GLOBALS['DB']->Query($query);

			while ($row = $res->Fetch()) {
				$a[] = $row['Field'];
			}
		}
		return $a;
	}

	/**
	 * Получить содержимое по урлу
	 * @param $url
	 * @return bool|false|string
	 */
	public static function getPageContentByUrl($url)
	{
		$el = new HttpClient();
		return $el->get( $url );
	}

	public static function getHttpHeadersByUrl($url): array
	{
		$el = new HttpClient([
			'redirect'                  => false,
			'disableSslVerification'    => true
		]);
		$headers = $el->head( $url )->toArray();
		$headers['status'] = $el->getStatus();
		return $headers;
	}

	/**
	 * Возвращает размер удаленного файла
	 *
	 * @param $path Путь к удаленному файлу
	 * @return int | bool
	 */
	public static function remote_filesize($path)
	{
		preg_match('#(ht|f)tp(s)?://(?P<host>[a-zA-Z-_]+.[a-zA-Z]{2,4})(?P<name>/[\S]+)#', $path, $m);
		$x = 0;
		$stop = false;
		$fp = fsockopen($m['host'], 80, $errno, $errstr, 30);
		fwrite($fp, "HEAD $m[name] HTTP/1.0\nHOST: $m[host]\n\n");
		while (!feof($fp) && !$stop) {
			$y = fgets($fp, 2048);
			if ($y == "\r\n") {
				$stop = true;
			}
			$x .= $y;
		}
		fclose($fp);

		if (preg_match("#Content-Length:\s*([\d]+)#i", $x, $size)) {
			return $size[1];
		} else {
			return false;
		}
	}

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
			} else {
				global $strError;
				return new UpdateResult(['RESULT' => $strError,     'STATUS' => UpdateResult::STATUS_ERROR]);
			}
		}
	}

	/**
	 * Рекурсивное преобразование объекта в массив
	 *
	 * @param object|array|mixed $obj объект для преобразования
	 * @return array|mixed
	 */
	public static function objToAr_r($obj)
	{
		if (!is_object($obj) && !is_array($obj)) {
			return $obj;
		}
		if (is_object($obj)) {
			$obj = get_object_vars($obj);
		}
		if (is_array($obj)) {
			foreach ($obj as $key => $val) {
				$obj[$key] = self::objToAr_r($val);
			}
		}
		return $obj;
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

	/**
	 * Возвращает код, сгенерированный компонентом Битрикс
	 * @param string $name Имя компонента
	 * @param string $template Шаблон компонента
	 * @param array $params Параметры компонента
	 * @param mixed $componentResult Данные, возвращаемые компонентом
	 * @return string
	 * @see \CMain::IncludeComponent()
	 */
	public static function getComponent($name, $template = '', $params = [], &$componentResult = null): string
	{
		ob_start();
		$componentResult = $GLOBALS['APPLICATION']->IncludeComponent($name, $template, $params);
		return ob_get_clean();
	}

	/**
	 * Возвращает код, сгенерированный включаемой областью Битрикс
	 * @param string $path Путь до включаемой области
	 * @param array $params Массив параметров для подключаемого файла
	 * @param array $functionParams Массив настроек данного метода
	 * @return string
	 * @see \CMain::IncludeFile()
	 */
	public static function getIncludeArea($path, $params = [], $functionParams = []): string
	{
		ob_start();
		$GLOBALS['APPLICATION']->IncludeFile($path, $params, $functionParams);
		return ob_get_clean();
	}

	/**
	 * стартует по классике сессию
	 */
	public static function sessionStart(): void
	{
		//session initialization
		if (session_status() !== PHP_SESSION_ACTIVE) {
			ini_set("session.cookie_httponly", "1");
			ini_set("session.use_strict_mode", "On");

			session_start();
		}
	}

	/**
	 * вернуть время года по таймстампу
	 * @param \DateTimeImmutable | \DateTime $dateTime
	 * @return string Winter|Spring|Summer|Fall
	 */
	public static function timestampToSeason($dateTime): string
	{
		$dayOfTheYear = $dateTime->format('z');
		if ($dayOfTheYear < 80 || $dayOfTheYear > 356) {
			return 'Winter';
		}
		if ($dayOfTheYear < 173) {
			return 'Spring';
		}
		if ($dayOfTheYear < 266) {
			return 'Summer';
		}
		return 'Fall';
	}

	/**
	 * write exception to log
	 * @param \Exception|\Bitrix\Main\SystemException $exception
	 * @return void
	 */
	public static function logException($exception): void
	{
		$application = Application::getInstance();
		$exceptionHandler = $application->getExceptionHandler();
		$exceptionHandler->writeToLog($exception);
	}

} // end class


