<? defined('B_PROLOG_INCLUDED') || die();
// region var_templ
/**
 * @var array $arParams
 * @var array $arResult
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $componentPath
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var array $templateData
 * @var CBitrixComponent $component
 */
// endregion
$this->setFrameMode(true);

// echo '<pre>'; print_r($arResult); echo '</pre>';
foreach ($arResult["ITEMS"] as $arItem) {
	echo '<pre>'; print_r($arItem['PRE']); echo '</pre>';
}
?>
<?php echo $arResult["NAV_STRING"]?>