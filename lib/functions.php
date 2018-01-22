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

?>