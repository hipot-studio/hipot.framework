<?php

namespace Hipot\BitrixUtils\Iblock;

use Bitrix\Main\Loader;

/** Привязка к элементу инфоблока с отдельным описанием каждой связи. */
final class ElementDescriptionType
{
	public const USER_TYPE_ID = 'hipot_element_description';

	public static function getUserTypeDescription(): array
	{
		return [
			'PROPERTY_TYPE' => 'E',
			'USER_TYPE' => self::USER_TYPE_ID,
			'DESCRIPTION' => 'Привязка к элементам с описанием (Hipot)',
			'GetPropertyFieldHtml' => [self::class, 'getPropertyFieldHtml'],
		];
	}

	public static function getPropertyFieldHtml($property, $value, $htmlControl): string
	{
		$id = (int)($value['VALUE'] ?? 0);
		$iblockId = (int)($property['LINK_IBLOCK_ID'] ?? 0);
		$name = (string)$htmlControl['VALUE'];
		$element = false;
		if ($id > 0 && Loader::includeModule('iblock')) {
			$filter = ['ID' => $id];
			if ($iblockId > 0) {
				$filter['IBLOCK_ID'] = $iblockId;
			}
			$element = \CIBlockElement::GetList([], $filter, false, false, ['ID', 'NAME'])->Fetch();
		}

		$url = '/bitrix/admin/iblock_element_search.php?' . http_build_query([
			'lang' => LANGUAGE_ID,
			'IBLOCK_ID' => $iblockId,
			'n' => $name,
			'k' => '0',
		]);
		$onclick = 'jsUtils.OpenWindow(' . \CUtil::PhpToJSObject($url) . ', 600, 500);';
		return '<input type="text" size="5" name="' . htmlspecialcharsbx($name) . '" id="'
			. htmlspecialcharsbx($name) . '" value="' . ($id > 0 ? $id : '') . '">'
			. '<input type="button" value="..." onclick="' . htmlspecialcharsbx($onclick) . '">'
			. '&nbsp;<input type="text" name="' . htmlspecialcharsbx((string)$htmlControl['DESCRIPTION'])
			. '" value="' . htmlspecialcharsbx((string)($value['DESCRIPTION'] ?? '')) . '">'
			. '&nbsp;<span id="sp_' . md5($name) . '_0">' . htmlspecialcharsbx((string)($element['NAME'] ?? '')) . '</span>';
	}
}
