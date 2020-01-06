<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<div class="action-wrap">
	<?
	foreach ($arResult["ITEMS"] as $arItem): ?>
		<a href="<?= $arItem["UF_URL"] ?>">
			<? if (!empty($arItem["~UF_TEXT"])): ?>
				<?= $arItem["~UF_TEXT"]; ?>
			<? else: ?>
				<img src="<?=CFile::GetPath($arItem["~UF_IMAGE"])?>" >
			<? endif; ?>
		</a>
	<? endforeach; ?>
</div>
<?php echo $arResult["NAV_STRING"]?>