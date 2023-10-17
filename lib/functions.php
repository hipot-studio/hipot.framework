<?
/**
 * Функции для первого уровня. Наиболее удобные в работе.
 *
 * @version 1.0
 * @author hipot Framework
 */

use Hipot\Utils\UUtils;

if (! function_exists('my_print_r')) {
	/**
	 * Дампит переменную в браузер
	 *
	 * @param 	mixed 	$what переменная для дампа
	 * @param 	bool 	$in_browser = true выводить ли результат на экран,
	 * 					либо скрыть в HTML-комментарий
	 * @param 	bool 	$check_admin = true проверять на админа, если вывод прямо в браузер
	 *
	 * @return 	void
	 *
	 * @example
	 * <pre>my_print_r($ar); //выведет только для админа, для всех остальных HTML-комментарий-заглушка
	 * my_print_r($ar, false); //выведет всем в виде HTML-комментария
	 * my_print_r($ar, true, false); //выведет всем на экран (не рекомендуется)</pre>
	 * @noinspection ForgottenDebugOutputInspection*/
	function my_print_r($what, bool $in_browser = true, bool $check_admin = true, bool $backtrace = false): void
	{
		global $USER;

		$bSapi = PHP_SAPI === 'cli';
		if ($bSapi) {
			$check_admin = false;
		}

		$bAdmin = (method_exists($USER, 'IsAdmin') && $USER->IsAdmin()) || (defined('IS_BETA_TESTER') && IS_BETA_TESTER);
		if ($in_browser && $check_admin && !$bAdmin) {
			echo "<!-- my_print_r admin need! -->";
			return;
		}

		if (function_exists('d')) {
			d($what);
		} else if (method_exists(\Bitrix\Main\Diag\Debug::class, 'dump')) {
			\Bitrix\Main\Diag\Debug::dump($what);
		} else {
			if (! $bSapi) {
				echo $in_browser ? "<pre>" : "<!--";
			}
			if (is_array($what))  {
				print_r($what);
			} else {
				var_dump($what);
			}
			if (! $bSapi) {
				echo $in_browser ? "</pre>" : "-->";
			}
		}

		if ($backtrace) {
			$arBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			echo '<h4>' . $arBacktrace[0]["file"] . ', ' . $arBacktrace[0]["line"] . '</h4>';
		}
	}
}

if (! function_exists('__hiCs')) {
	/**
	 * Удобная обертка для получения в разных местах параметров в произвольных настройках сайта
	 *
	 * Важно! Сперва необходимо установить параметры через
	 * Hipot\BitrixUtils\HiBlock::installCustomSettingsHiBlock();
	 *
	 * @param string $paramCode
	 * @param string $defaultValue = '' значение по-умолчанию, если нет значения в базе
	 *
	 * @return mixed
	 */
	function __hiCs(string $paramCode, string $defaultValue = '')
	{
		static $params;
		try {
			if (! isset($params)) {
				$params = Hipot\BitrixUtils\HiBlockApps::getCustomSettingsList();
			}
			if (empty($params[$paramCode]) && trim($defaultValue) != '') {
				return $defaultValue;
			}
			return $params[$paramCode];
		} catch (Exception $e) {
			UUtils::logException($e);
		}
		return false;
	}
}

if (! function_exists('__getHl')) {
	/**
	 * Получить сущность DataManager HL-инфоблока
	 *
	 * @param int|string $hiBlockName числовой или символьный код HL-инфоблока
	 * @param bool       $staticCache = true сохранять в локальном кеше функции возвращаемые сущности
	 *
	 * @return bool|Bitrix\Main\ORM\Data\DataManager
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	function __getHl(string|int $hiBlockName, bool $staticCache = true)
	{
		static $addedBlocks;

		$hiBlockName = trim((string)$hiBlockName);
		if ($hiBlockName == '') {
			return false;
		}

		if (!isset($addedBlocks[$hiBlockName]) || !$staticCache) {
			if (is_numeric($hiBlockName)) {
				$addedBlocks[$hiBlockName] = Hipot\BitrixUtils\HiBlock::getDataManagerByHiId($hiBlockName);
			} else {
				$addedBlocks[$hiBlockName] = Hipot\BitrixUtils\HiBlock::getDataManagerByHiCode($hiBlockName);
			}
		}

		return $addedBlocks[$hiBlockName];
	}
}

if (! function_exists('array_trim_r')) {
	/**
	 * Удаляет из массива пустые элементы с пустыми значениями рекурсивно.
	 *
	 * @param array $ar
	 * @return array
	 */
	function array_trim_r(array $ar): array
	{
		$arRes = [];
		foreach ($ar as $k => $v) {
			if (is_array($v)) {
				$arRes[$k] = array_trim_r($v);
			} else {
				if (trim((string)$v) != '') {
					$arRes[$k] = $v;
				}
			}
		}
		return $arRes;
	}
}

if (! function_exists('array_get')) {
	/**
	 * Возвращает элемент подмассива используя точечную нотацию item.sub_item
	 *
	 * @param array|mixed $array Массив
	 * @param ?string $key Путь ключей
	 * @param mixed $default Значение "по умалчанию"
	 *
	 * @return mixed
	 */
	function array_get(array $array, ?string $key, $default = null)
	{
		if (is_null($key)) {
			return $array;
		}
		if (isset($array[$key])) {
			return $array[$key];
		}
		foreach (explode('.', $key) as $segment) {
			if (!is_array($array) || !array_key_exists($segment, $array)) {
				return $default;
			}
			$array = $array[$segment];
		}
		return $array;
	}
}

/*
if (! function_exists('bx_js_encode')) {
	/**
	 * Если определить глобальную функцию с секретным именем bx_js_encode,
	 * то при работе CUtil::PhpToJSObject() будет вызываться именно она и сразу возвращаться ее результат
	 *
	 * @param mixed $arData
	 * @param $bWS
	 * @param $bSkipTilda
	 * @param $bExtType
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 *
	 * HAS BUGS ON bitrix24
	 * /
	function bx_js_encode($arData, $bWS, $bSkipTilda, $bExtType)
	{
		return Bitrix\Main\Web\Json::encode($arData);
	}
}
*/

if (! function_exists('str_contains')) {
	/**
	 * TODO php7.4 rudiment
	 * @param $haystack
	 * @param $needle
	 * @return bool
	 */
	function str_contains($haystack, $needle): bool
	{
		return $needle !== '' && mb_strpos($haystack, $needle) !== false;
	}
}

// \/EOF