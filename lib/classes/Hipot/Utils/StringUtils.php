<?
namespace Hipot\Utils;

/**
 * Утилиты для работы со строками
 */
class StringUtils
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
		if ((int)$n != $n) {
			return $forms[1];
		}
		$n = abs($n) % 100;
		$n1 = $n % 10;
		if ($n > 10 && $n < 20) {
			return $forms[2];
		}
		if ($n1 > 1 && $n1 < 5) {
			return $forms[1];
		}
		if ($n1 == 1) {
			return $forms[0];
		}
		return $forms[2];
	}


} // end class


