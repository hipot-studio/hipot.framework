<?
defined('B_PROLOG_INCLUDED') || die();

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
?>
<div class="action-wrap">
	<?
	foreach ($arResult["ITEMS"] as $arItem): ?>
		<a href="<?= $arItem["UF_URL"] ?>">
			<? if (!empty($arItem["~UF_TEXT"])): ?>
				<?= $arItem["~UF_TEXT"]; ?>
			<? else: ?>
				<img src="<?=CFile::GetPath($arItem["~UF_IMAGE"])?>" alt="<?=htmlspecialcharsbx($arItem['UF_NAME'])?>" loading="lazy">
			<? endif; ?>
		</a>
	<? endforeach; ?>
</div>
<?php echo $arResult["NAV_STRING"]?>