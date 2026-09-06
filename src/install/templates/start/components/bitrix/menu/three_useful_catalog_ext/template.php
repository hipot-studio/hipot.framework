<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

$this->setFrameMode(true);

if (! function_exists('showLevel')) {
	/**
	 * Рекурсивный вывод меню
	 *
	 * @param array $items
	 */
	function showLevel($items)
	{
		global $APPLICATION;
		$curPage = $APPLICATION->GetCurPage(false);

		if (empty($items)) {
			return;
		}
		echo "<ul>";
		foreach ($items as $item) {
			$cssClasses = [
				"level-{$item['DEPTH_LEVEL']}",
			];
			if ($item["PERMISSION"] <= "D") {
				continue;
			}
			if ($item["IS_PARENT"]) {
				$cssClasses[] = 'parent';
			}
			if ($item["SELECTED"]) {
				$cssClasses[] = 'active';
			}
			$strClasses = implode(' ', $cssClasses);
			echo "<li>";
			if ($curPage == $item['LINK']) {
				echo "<span class=\"{$strClasses}\">{$item['TEXT']}</span>";
			} else {
				echo "<a class=\"{$strClasses}\" href=\"{$item['LINK']}\">{$item['TEXT']}</a>";
			}
			if (!empty($item['SUB'])) {
				showLevel($item['SUB']);
			}
			echo "</li>";
		}
		echo "</ul>";
	}
}

echo '<div class="catalog_left_menu">';
showLevel($arResult);
echo '</div>';