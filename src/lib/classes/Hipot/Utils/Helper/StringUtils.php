<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 11.05.2023 17:03
 * @version pre 1.0
 */

namespace Hipot\Utils\Helper;

use Bitrix\Main\Grid\Declension;
use CUtil;

trait StringUtils
{
	/**
	 * Возвращает слово с правильным суффиксом
	 * @param int|numeric $n количество
	 * @param array|string $forms строка 'один|два|несколько' или 'слово|слова|слов' или массив с такой же историей
	 * @see https://localization-guide.readthedocs.io/en/latest/l10n/pluralforms.html
	 */
	public static function Suffix(int|float|string $n, array|string $forms): string
	{
		if (is_string($forms)) {
			$forms = explode('|', $forms);
		}
		return (new Declension($forms[0], $forms[1], $forms[2]))->get( (int)$n );
	}

	/**
	 * Транслит в один вызов
	 * @param mixed|string $text
	 */
	public static function TranslitText(?string $text, string $lang = 'ru', int $maxLen = 200): string
	{
		return CUtil::translit(trim((string)$text), $lang, [
			'max_len'               => $maxLen,
			'change_case'           => "L",
			'replace_space'         => '-',
			'replace_other'         => '-',
			'delete_repeat_replace' => true
		]);
	}

	/**
	 * Null-safe strip_tags to use in component templates
	 * @param mixed|string $value
	 * @param array $allowedTags
	 * @return string
	 */
	public static function getClearHtmlValue(
		?string $value,
		array $allowedTags = ['p', 'b', 'strong', 'em', 'i', 'br', 'a', 'ul', 'ol', 'li', 'span', 'img', 'div', 'h2', 'h3', 'table', 'thead', 'tbody', 'tr', 'td', 'th']
	): string
	{
		if ($value === null) {
			return '';
		}
		$value = strip_tags((string)$value, $allowedTags);
		if (class_exists(\tidy::class)) {
			$tidy = new \tidy();
			$value = $tidy::repairString($value, [
				'show-body-only' => true,
				'indent'         => true,
				'indent-spaces'  => 4,
				'vertical-space' => false,
				'wrap'           => 400
			]);
		}
		return $value;
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
}
