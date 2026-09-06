<?php
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

$curDir = $APPLICATION->GetCurPage(false);
?>
<ul>
	<?foreach ($arResult as $arItem):?>
		<li>
			<?if ($curDir === $arItem["LINK"]):?>
				<?=$arItem["TEXT"]?>
			<?elseif ($arItem["SELECTED"]):?>
				<a href="<?=$arItem["LINK"];?>"><b><?=$arItem["TEXT"]?></b></a>
			<?else:?>
				<a href="<?=$arItem["LINK"];?>"><?=$arItem["TEXT"]?></a>
			<?endif?>
		</li>
	<?endforeach;?>
</ul>
