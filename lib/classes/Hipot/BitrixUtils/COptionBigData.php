<?
/**
 *
 * Класс для сохранения больших объемов в таблице COption
 * Сохраняет по кускам в нескольких полях
 * Т.е. происходит фрагментированное хранение данных
 *
 * ПРИЧИНА: Как оказалось, COption::SetOptionString режет под 2000 символов
 *
 *
 */
class COptionBigData
{
	/**
	 * Максимальная длина хранения в одном COption-значении
	 * @var int
	 */
	const DATA_MAX_WIDTH = 1000;

	/**
	 * Записать значение константы
	 *
	 * @param string $constVar имя константы
	 * @param mixed  $ar переменная будет сериализована и сохранена
	 *
	 * @return boolean
	 */
	static function saveValue($constVar, $ar)
	{
		if (trim($constVar) == '' || !isset($ar)) {
			return false;
		}

		$seria = serialize($ar);
		$j = 0;
		for ($i = 0, $iMax = strlen($seria); $i <= $iMax; $i += self::DATA_MAX_WIDTH) {
			COption::SetOptionString("we_actions", $constVar . $j++, substr($seria, $i, self::DATA_MAX_WIDTH));
		}

		return true;
	}

	/**
	 * Вернуть значение константы
	 *
	 * @param string $constVar имя константы
	 *
	 * @return boolean|mixed
	 */
	static function getValue($constVar)
	{
		if (trim($constVar) == '') {
			return false;
		}

		$seria = '';
		$j = 0;
		while (($t = COption::GetOptionString("we_actions", $constVar . $j++, '')) != '') {
			$seria .= $t;
		}

		return unserialize($seria);
	}


	/**
	 * Удалить все значения константы
	 *
	 * @param string $constVar имя константы
	 *
	 * @return boolean
	 */
	static function deleteAllValues($constVar)
	{
		if (trim($constVar) == '') {
			return false;
		}

		$GLOBALS['DB']->Query('DELETE FROM `b_option` WHERE `NAME` LIKE "' . $constVar . '%"');

		return true;
	}
}

?>