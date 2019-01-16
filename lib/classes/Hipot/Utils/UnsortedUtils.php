<?
namespace Hipot\Utils;

use \Bitrix\Main\Application,
	\Bitrix\Main\Grid\Declension;

/**
 * Различные утилиты
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
	 */
	public static function Suffix($n, $forms)
	{
		if (is_string($forms)) {
			$forms = explode('|', $forms);
		}
		$declens = new Declension($forms[0], $forms[1], $forms[2]);
		echo $declens->get($n);
	}

	/**
	 * Транслит в одну строку
	 * @param        $text
	 * @param string $lang
	 *
	 * @return string
	 */
	function TranslitText($text, $lang = 'ru')
	{
		return \CUtil::translit(trim($text), $lang, array(
			'max_len' => 100,
			'change_case' => "L",
			'replace_space' => '-',
			'replace_other' => '-',
			'delete_repeat_replace' => true
		));
	}

	/**
	 * Является ли текущая страница в данный момент страницей постраничной навигации
	 * @return bool
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function isPageNavigation()
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
	 * Возвращает элемент подмассива используя точечную нотацию item.sub_item
	 *
	 * @param array $array Массив
	 * @param string $key Ключ
	 * @param mixed $default Значение "по умалчанию"
	 *
	 * @return mixed
	 */
	function array_get($array, $key, $default = null)
	{
		if (is_null($key))
			return $array;

		if (isset($array[$key]))
			return $array[$key];

		foreach (explode('.', $key) as $segment) {
			if (!is_array($array) or !array_key_exists($segment, $array)) {
				return value($default);
			}

			$array = $array[$segment];
		}

		return $array;
	}


	/**
	 * Функция определения кодировки строки
	 * Удобно для автоматического определения кодировки csv-файла
	 *
	 * Почему не mb_detect_encoding()? Если кратко — он не работает.
	 *
	 * @param string $string строка в неизвестной кодировке
	 * @param number $pattern_size = 50
	 *        если строка больше этого размера, то определение кодировки будет
	 *        производиться по шаблону из $pattern_size символов, взятых из середины
	 *        переданной строки. Это сделано для увеличения производительности на больших текстах.
	 * @return string 'cp1251' 'utf-8' 'ascii' '855' 'KOI8R' 'ISO-IR-111' 'CP866' 'KOI8U'
	 *
	 * @see http://habrahabr.ru/post/107945/
	 * @see http://forum.dklab.ru/viewtopic.php?t=37833
	 * @see http://forum.dklab.ru/viewtopic.php?t=37830
	 */
	function detect_encoding($string, $pattern_size = 50)
	{
		$list = array(
			'cp1251',
			'utf-8',
			'ascii',
			'855',
			'KOI8R',
			'ISO-IR-111',
			'CP866',
			'KOI8U'
		);
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
	 * Выполняет команду в OS в фоне и без получения ответа
	 *
	 * @see exec()
	 * @return NULL
	 */
	function execInBackground($cmd)
	{
		if (substr(php_uname(), 0, 7) == "Windows") {
			pclose(popen("start /B " . $cmd, "r"));
		} else {
			exec($cmd . " > /dev/null &");
		}
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
	function IsIntervalsTsIncl($left1_ts, $right1_ts, $left2_ts, $right2_ts)
	{
		// echo $left1_ts . ' ' . $right1_ts . ' ' . $left2_ts . ' ' . $right2_ts . '<br />';
		if ($left1_ts <= $left2_ts) {
			return $right1_ts >= $left2_ts;
		} else {
			return $left1_ts <= $right2_ts;
		}
	}
	
	/**
	 * Получить содержимое по урлу
	 * @param $url
	 * @return bool|false|string
	 */
	static public function getPageContentByUrl($url)
	{
		/*$arUrl = parse_url($url);
		$cont = QueryGetData($arUrl['host'], 80, $arUrl['path'], $QUERY_STR, $errno, $errstr);
		return file_get_contents($url);*/

		$el = new \Bitrix\Main\Web\HttpClient();
		$cont = $el->get( $url );
		return $cont;
	}

	/**
	 * Получить список колонок SQL-запросом, либо если уже был получен, то просто вернуть
	 * @param string $tableName имя таблицы
	 * @return array
	 */
	static public function getTableFieldsFromDB($tableName)
	{
		$a = array();
		if (trim($tableName) != '') {
			$query	= "SHOW COLUMNS FROM " . $tableName;
			$res	= $GLOBALS['DB']->Query($query);

			while ($row = $res->Fetch()) {
				$a[] = $row['Field'];
			}
		}
		return $a;
	}


} // end class


