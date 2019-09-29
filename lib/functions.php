<?
/**
 * Функции для первого уровня. Наиболее удобные в работе.
 *
 * @version 1.0
 * @author hipot Framework
 */

if (! function_exists('my_print_r')) {
	/**
	 * Дампит переменную в браузер
	 *
	 * @param 	mixed 	$what переменная для дампа
	 * @param 	bool 	$in_browser = true выводить ли результат на экран,
	 * 					либо скрыть в HTML-комментарий
	 * @param 	bool 	$check_admin = true проверять на админа, если вывод прямо в браузер
	 * @return 	void
	 *
	 * @example
	 * <pre>my_print_r($ar); //выведет только для админа, для всех остальных HTML-комментарий-заглушка
	 * my_print_r($ar, false); //выведет всем в виде HTML-комментария
	 * my_print_r($ar, true, false); //выведет всем на экран (не рекомендуется)</pre>
	 */
	function my_print_r($what, $in_browser = true, $check_admin = true)
	{
		if ($in_browser && $check_admin && !$GLOBALS['USER']->IsAdmin()) {
			echo "<!-- my_print_r admin need! -->";
			return;
		}

		/*$backtrace = debug_backtrace();
		echo '<h4>' . $backtrace[0]["file"] . ', ' . $backtrace[0]["line"] . '</h4>';*/

		echo $in_browser ? "<pre>" : "<!--";
		if ( is_array($what) )  {
			print_r($what);
		} else {
			var_dump($what);
		}
		echo $in_browser ? "</pre>" : "-->";
	}
}

if (! function_exists('__cs')) {
	/**
	 * Удобная обертка для получения в разных местах параметров в произвольных настройкаx сайта
	 *
	 * Важно! Сперва необходимо установить параметры через
	 * Hipot\BitrixUtils\HiBlock::installCustomSettingsHiBlock();
	 *
	 * @param string $paramCode
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 */
	function __cs($paramCode)
	{
		$params = Hipot\BitrixUtils\HiBlock::getCustomSettingsList();
		return $params[$paramCode];
	}
}

if (! function_exists('__getHl')) {
	/**
	 * Получить сущность DataManager HL-инфоблока
	 *
	 * @param int|string $hiBlockName числовой или символьный код HL-инфоблока
	 * @param bool       $staticCache = true сохранять в локальном кеше функции возвращаемые сущности
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	function __getHl($hiBlockName, $staticCache = true)
	{
		static $addedBlocks;

		$hiBlockName = trim($hiBlockName);
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

if (! function_exists('GetIbEventPropValue')) {
	/**
	 * Удобно использовать в событиях инфоблока для получения значений без сдвига массива
	 *
	 * @param mixed $propIdx
	 *
	 * @return bool | $arFields['PROPERTY_VALUES'][107][    <b>??? - 1259|0|n0</b>    ]['VALUE']
	 * @use GetIbEventPropValue($arFields['PROPERTY_VALUES'][107])
	 */
	function GetIbEventPropValue($propIdx)
	{
		$k = array_keys($propIdx);

		if (is_array($k) && is_array($propIdx)) {
			return $propIdx[ $k[0] ]['VALUE'];
		}
		return false;
	}
}

if (! function_exists('TransformImagesInHtml')) {
	/**
	 * трансформим картинки в html до требуемой ширины (регулярки)
	 *
	 * @param string $html
	 * @param int $maxImgWidth = 750
	 * @return string|mixed
	 */
	function TransformImagesInHtml($html, $maxImgWidth = 750, $linkToOrigClass = ' class="lightbox" target="_blank" ')
	{

		$htmlEx = preg_replace('#width\s*=(["\'])?([0-9]+)(["\'])?#is', '', $html);
		$htmlEx = preg_replace('#height\s*=(["\'])?([0-9]+)(["\'])?#is', '', $htmlEx);
		$htmlEx = preg_replace_callback('#<img(.*?)src=["\']?([^"\']+)["\']?(.*?)>#is', static function ($matches) use ($maxImgWidth, $linkToOrigClass) {
			$transformSrc = $matches[2];
			$origSrc = $matches[2];

			$transformSrc = CImg::Resize($transformSrc, $maxImgWidth, false, CImg::M_FULL);

			return "<a href=\"" . $origSrc . "\" " . $linkToOrigClass . "><img src=\"" . $transformSrc . "\" alt=\"\" border=\"0\" /></a>";
		}, $htmlEx);

		return $htmlEx;
	}
}

if (! function_exists('array_trim_r')) {
	/**
	 * Удаляет из массива пустые элементы с пустыми значениями рекурсивно.
	 *
	 * @param {array} $ar
	 *
	 * @return mixed
	 */
	function array_trim_r($ar)
	{
		foreach ($ar as $k => $v) {
			if (is_array($v)) {
				$res[$k] = array_trim_r($v);
			} else {
				if (trim($v) != '') {
					$res[$k] = $v;
				}
			}
		}
		return $res;
	}
}

if (! function_exists('array_get')) {
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

// \/EOF
?>