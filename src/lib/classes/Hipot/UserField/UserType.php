<?php

namespace Hipot\UserField;

use Bitrix\Main\UserField\Types\IntegerType;

/** Пользовательское поле: привязка к пользователю (одиночная или множественная). */
final class UserType
{
	public const USER_TYPE_ID = 'hipot_user';

	public static function getUserTypeDescription(): array
	{
		return [
			'USER_TYPE_ID' => self::USER_TYPE_ID,
			'CLASS_NAME' => self::class,
			'DESCRIPTION' => 'Привязка к пользователю (Hipot)',
			'BASE_TYPE' => 'int',
			'EDIT_CALLBACK' => [self::class, 'getPublicEdit'],
			'VIEW_CALLBACK' => [self::class, 'getPublicView'],
		];
	}

	public static function getDbColumnType($userField): string
	{
		return IntegerType::getDbColumnType();
	}

	public static function prepareSettings($userField): array
	{
		return [];
	}

	public static function getSettingsHtml($userField = false, $htmlControl = [], $varsFromForm = false): string
	{
		return '';
	}

	public static function getEditFormHtml($userField, $htmlControl): string
	{
		// NAME/VALUE берём из контрола: для множественного поля они относятся к одному значению.
		return self::renderSelector($htmlControl);
	}

	public static function getAdminListEditHtml($userField, $htmlControl): string
	{
		return self::renderSelector($htmlControl);
	}

	public static function getPublicEdit($userField, $additionalParameters = []): string
	{
		$additionalParameters = $additionalParameters ?? [];
		$name = (string)($additionalParameters['NAME'] ?? $userField['FIELD_NAME']);
		$value = array_key_exists('VALUE', $additionalParameters)
			? $additionalParameters['VALUE'] : ($userField['VALUE'] ?? null);
		if (($userField['MULTIPLE'] ?? 'N') !== 'Y') {
			return self::renderSelector(['NAME' => $name, 'VALUE' => is_array($value) ? reset($value) : $value]);
		}

		if (!str_ends_with($name, '[]')) {
			$name .= '[]';
		}
		$values = array_values((array)$value);
		// Пустой контрол позволяет добавить ещё одну привязку при сохранении формы.
		$values[] = '';
		$html = '';
		foreach ($values as $item) {
			$html .= '<div>' . self::renderSelector(['NAME' => $name, 'VALUE' => $item]) . '</div>';
		}
		return $html;
	}

	public static function getPublicView($userField, $additionalParameters = []): string
	{
		$additionalParameters = $additionalParameters ?? [];
		$value = array_key_exists('VALUE', $additionalParameters)
			? $additionalParameters['VALUE'] : ($userField['VALUE'] ?? null);
		return implode(', ', array_map(static fn($id): string => self::renderUser((int)$id), (array)$value));
	}

	public static function getFilterHtml($userField, $htmlControl): string
	{
		return '<input type="text" name="' . htmlspecialcharsbx((string)$htmlControl['NAME'])
			. '" value="' . htmlspecialcharsbx((string)($htmlControl['VALUE'] ?? '')) . '" size="10">';
	}

	public static function getAdminListViewHtml($userField, $htmlControl): string
	{
		return self::renderUser((int)($htmlControl['VALUE'] ?? 0));
	}

	public static function checkFields($userField, $value): array
	{
		if (self::isEmpty($value)) {
			// Проверку обязательности выполняет менеджер пользовательских полей.
			return [];
		}
		if ((!is_int($value) && !is_string($value))
			|| !preg_match('/^[0-9]+$/D', (string)$value)
			|| (float)$value > 2147483647
			|| (int)$value <= 0
		) {
			return [['id' => $userField['FIELD_NAME'], 'text' => 'Укажите положительный целочисленный ID пользователя.']];
		}
		if (!\CUser::GetByID((int)$value)->Fetch()) {
			return [['id' => $userField['FIELD_NAME'], 'text' => 'Пользователь с указанным ID не найден.']];
		}
		return [];
	}

	public static function onBeforeSave($userField, $value): ?int
	{
		return self::isEmpty($value) ? null : (int)$value;
	}

	public static function onSearchIndex($userField): string
	{
		return '';
	}

	private static function isEmpty($value): bool
	{
		return $value === null || $value === false || $value === '' || $value === 0 || $value === '0';
	}

	private static function renderSelector(array $htmlControl): string
	{
		$userId = (int)($htmlControl['VALUE'] ?? 0);
		$html = \FindUserID(
			(string)$htmlControl['NAME'],
			$userId > 0 ? (string)$userId : '',
			self::renderUser($userId),
			'hipot_user_selector_form',
			'10'
		);

		// FindUserID требует имя формы. Определяем фактическую форму, чтобы работать
		// и в карточке пользователя, и в HL-блоках, и при редактировании списка.
		return '<span>' . $html . '</span>' . <<<'HTML'
<script>
(function () {
	var selector = document.currentScript.previousElementSibling;
	var input = selector.querySelector('input');
	if (!input || !input.form) { return; }
	var form = input.form;
	if (!form.name) { form.name = 'hipot_user_selector_form'; }
	selector.querySelectorAll('input[onclick]').forEach(function (button) {
		button.setAttribute('onclick', button.getAttribute('onclick').replace(
			/hipot_user_selector_form/g, encodeURIComponent(form.name)
		));
	});
})();
</script>
HTML;
	}

	private static function renderUser(int $userId): string
	{
		if ($userId <= 0) {
			return '&nbsp;';
		}
		$user = \CUser::GetByID($userId)->Fetch();
		if (!$user) {
			return '[' . $userId . ']';
		}
		$name = trim(($user['LAST_NAME'] ?? '') . ' ' . ($user['NAME'] ?? '') . ' ' . ($user['SECOND_NAME'] ?? ''));
		$label = '[' . $userId . '] (' . $user['LOGIN'] . ')' . ($name !== '' ? ' ' . $name : '');
		return '<a href="/bitrix/admin/user_edit.php?ID=' . $userId . '&amp;lang='
			. htmlspecialcharsbx(LANGUAGE_ID) . '">' . htmlspecialcharsbx($label) . '</a>';
	}
}
