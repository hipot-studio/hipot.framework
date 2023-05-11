<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 11.05.2023 17:03
 * @version pre 1.0
 */
namespace Hipot\Utils;

use Bitrix\Main\Grid\Declension;
use CUtil;

trait StringUtils
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
	public static function TranslitText($text, string $lang = 'ru', int $maxLen = 100): string
	{
		return CUtil::translit(trim($text), $lang, [
			'max_len' => $maxLen,
			'change_case' => "L",
			'replace_space' => '-',
			'replace_other' => '-',
			'delete_repeat_replace' => true
		]);
	}
}