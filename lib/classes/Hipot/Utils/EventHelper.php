<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 31.03.2023 15:41
 * @version pre 1.0
 */

namespace Hipot\Utils;

class EventHelper
{
	/**
	 * Удобно использовать в событиях инфоблока для получения значений без сдвига массива
	 * $arFields['PROPERTY_VALUES'][107][    <b>??? - 1259|0|n0</b>    ]['VALUE']
	 * <pre>
	 * getIbockEventPropValue($arFields['PROPERTY_VALUES'][107]) Iblock
	 * </pre>
	 *
	 * @param mixed $propIdx напр. $arFields['PROPERTY_VALUES'][107]
	 *
	 * @return bool | mixed
	 */
	public static function getIblockEventPropValue($propIdx)
	{
		if (is_array($propIdx)) {
			$k = array_keys($propIdx);
			return $propIdx[ $k[0] ]['VALUE'] ?? $propIdx[ $k[0] ];
		}
		return false;
	}

	/**
	 * Удобно использовать в событиях инфоблока для задания значений без сдвига массива
	 * $arFields['PROPERTY_VALUES'][107][    <b>??? - 1259|0|n0</b>    ]['VALUE'] = $newValue;
	 * <pre>
	 * setIblockEventPropValue($arFields['PROPERTY_VALUES'][107], 'test')
	 * </pre>
	 *
	 * @param array $propIdx The array index containing the property.
	 * @param mixed $newValue The new value to set.
	 *
	 * @return void
	 */
	public static function setIblockEventPropValue(&$propIdx, $newValue): void
	{
		if (is_array($propIdx)) {
			$k = array_keys($propIdx);
			if (isset($propIdx[ $k[0] ]['VALUE'])) {
				$propIdx[ $k[0] ]['VALUE'] = $newValue;
			} else {
				$propIdx[ $k[0] ] = $newValue;
			}
		} else {
			$propIdx = [
				'n0' => [
					'VALUE' => $newValue,
				]
			];
		}
	}
}