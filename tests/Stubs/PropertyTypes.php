<?php

/** Минимальные заместители вывода Bitrix для тестирования типов полей без ядра. */
defined('LANG_CHARSET') || define('LANG_CHARSET', 'UTF-8');
defined('LANGUAGE_ID') || define('LANGUAGE_ID', 'ru');

function htmlspecialcharsbx($value): string
{
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, LANG_CHARSET);
}

class CHTMLEditor
{
	public static array $calls = [];

	public function Show(array $parameters): void
	{
		self::$calls[] = $parameters;
		echo '<textarea name="' . htmlspecialcharsbx($parameters['name']) . '">'
			. htmlspecialcharsbx($parameters['content']) . '</textarea>';
	}
}

class CUtil
{
	public static function PhpToJSObject($value): string
	{
		return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
	}
}
