<?php

namespace Hipot\UserField;

use Bitrix\Main\Loader;
use Bitrix\Main\UserField\Types\StringType;

/** Пользовательское поле с HTML и визуальным редактором; хранит строку без сериализации. */
final class HtmlType
{
	public const USER_TYPE_ID = 'hipot_html';

	public static function getUserTypeDescription(): array
	{
		return [
			'USER_TYPE_ID' => self::USER_TYPE_ID,
			'CLASS_NAME' => self::class,
			'DESCRIPTION' => 'HTML / Визуальный редактор (Hipot)',
			'BASE_TYPE' => 'string',
			'EDIT_CALLBACK' => [self::class, 'getPublicEdit'],
			'VIEW_CALLBACK' => [self::class, 'getPublicView'],
		];
	}

	public static function getDbColumnType($userField): string
	{
		return StringType::getDbColumnType();
	}

	public static function prepareSettings($userField): array
	{
		$settings = StringType::prepareSettings($userField);
		$settings['ROWS'] = max(8, (int)($settings['ROWS'] ?? 8));
		return $settings;
	}

	public static function getSettingsHtml($userField = false, $htmlControl = [], $varsFromForm = false): string
	{
		return StringType::getSettingsHtml($userField, $htmlControl, $varsFromForm);
	}

	/** В старом API Bitrix значение контрола уже экранировано. */
	public static function getEditFormHtml($userField, $htmlControl): string
	{
		$value = html_entity_decode((string)($htmlControl['VALUE'] ?? ''), ENT_QUOTES | ENT_HTML5, LANG_CHARSET);
		if (empty($userField['ENTITY_VALUE_ID']) && $value === '') {
			$value = (string)($userField['SETTINGS']['DEFAULT_VALUE'] ?? '');
		}
		return self::renderEditor($userField, (string)$htmlControl['NAME'], $value);
	}

	public static function getAdminListEditHtml($userField, $htmlControl): string
	{
		return self::getEditFormHtml($userField, $htmlControl);
	}

	public static function getPublicEdit($userField, $additionalParameters = []): string
	{
		$name = (string)($additionalParameters['NAME'] ?? $userField['FIELD_NAME']);
		$value = $additionalParameters['VALUE'] ?? $userField['VALUE'] ?? '';
		if (($userField['MULTIPLE'] ?? 'N') !== 'Y') {
			return self::renderEditor($userField, $name, (string)$value);
		}
		$name = str_ends_with($name, '[]') ? substr($name, 0, -2) : $name;
		$values = array_values((array)$value);
		$values[] = '';
		$html = '';
		foreach ($values as $index => $item) {
			$html .= self::renderEditor($userField, $name . '[' . $index . ']', (string)$item);
		}
		return $html;
	}

	/** Возвращает сохранённый HTML для публичного шаблона без повторного экранирования. */
	public static function getPublicView($userField, $additionalParameters = []): string
	{
		$value = $additionalParameters['VALUE'] ?? $userField['VALUE'] ?? '';
		return implode("\n", (array)$value);
	}

	public static function getAdminListViewHtml($userField, $htmlControl): string
	{
		return htmlspecialcharsbx(strip_tags(html_entity_decode((string)($htmlControl['VALUE'] ?? ''), ENT_QUOTES, LANG_CHARSET)));
	}

	public static function checkFields($userField, $value): array
	{
		return StringType::checkFields($userField, $value);
	}

	public static function onBeforeSave($userField, $value): string
	{
		return (string)$value;
	}

	public static function onSearchIndex($userField): string
	{
		return strip_tags(implode(' ', (array)($userField['VALUE'] ?? '')));
	}

	/** Отдельный ID редактора позволяет сохранить исходное имя, включая индексы множественного поля. */
	private static function renderEditor(array $userField, string $name, string $value): string
	{
		$height = max(8, (int)($userField['SETTINGS']['ROWS'] ?? 8)) * 20;
		if (!Loader::includeModule('fileman')) {
			return '<textarea name="' . htmlspecialcharsbx($name) . '" rows="' . (int)($height / 20)
				. '">' . htmlspecialcharsbx($value) . '</textarea>';
		}

		static $editorIndex = 0;
		ob_start();
		try {
			(new \CHTMLEditor())->Show([
				'name' => $name,
				'id' => 'hipot_html_' . md5($name) . '_' . ++$editorIndex,
				'content' => $value,
				'width' => '100%',
				'height' => $height,
				'bAllowPhp' => false,
			]);
			return (string)ob_get_contents();
		} finally {
			ob_end_clean();
		}
	}
}
