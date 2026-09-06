<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */

$parents = [];
foreach ($arResult as $index => $item) {
	if ($item['IS_PARENT']) {
		$parents[$item['DEPTH_LEVEL']] = &$arResult[$index];
	}
	if ($item['DEPTH_LEVEL'] > 1) {
		$parents[$item['DEPTH_LEVEL'] - 1]['SUB'][] = &$arResult[$index];
		unset($arResult[$index]);
	}
}
